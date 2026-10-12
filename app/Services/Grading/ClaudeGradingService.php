<?php

namespace App\Services\Grading;

use Anthropic\Beta\Messages\BetaMessage;
use Anthropic\Beta\Messages\BetaToolUseBlock;
use Anthropic\Client;
use App\Enums\GradingLevel;
use Closure;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Client\ClientInterface;

/**
 * Claude API で採点する(architecture.md 4.4)。
 *
 * 1回目: web_search と submit_grades を AI の判断で使わせる。
 * 2回目: 1回目で submit_grades が呼ばれなかったときだけ、会話を引き継いで提出を頼む。
 * ツールの呼び出しを強制する tool_choice は、Claude Sonnet 5.5 では使えない(400 になる)ため、
 * submit_grades を strict にして引数の形を保証し、プロンプトで提出を指示している。
 */
final class ClaudeGradingService implements GradingService
{
    private const SUBMIT_TOOL = 'submit_grades';

    private const MAX_TOKENS = 16000;

    // 応答が途中で止まった(pause_turn)ときに続けさせる回数の上限
    private const MAX_CONTINUATIONS = 3;

    // 拒否されたときに API 側で別のモデルへ回す(fallbacks: "default")ためのベータ
    private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    private const SUBMIT_REQUEST = '採点結果を submit_grades で提出してください。';

    /** @var Closure(float): ClientInterface */
    private Closure $transport;

    /**
     * @param  float  $timeLimitSeconds  1回の採点にかける時間の上限。Job の $timeout(240秒)に収まるようにする
     * @param  (Closure(float): ClientInterface)|null  $transport  タイムアウト秒数を受け取り、HTTP クライアントを返す
     */
    public function __construct(
        private readonly Client $client,
        private readonly GradingPrompt $prompt,
        private readonly GradeResultParser $parser,
        private readonly string $model,
        private readonly float $timeLimitSeconds = 200.0,
        ?Closure $transport = null,
    ) {
        // SDK はリクエストのタイムアウトを自分では強制しない(HTTP クライアントに任せている)。
        // そのため、呼び出しごとに残り時間をタイムアウトにした HTTP クライアントを作って渡す。
        // テストでは、決まった応答を返すクライアントに差し替える。
        $this->transport = $transport ?? fn (float $timeout) => new GuzzleClient(['timeout' => $timeout]);
    }

    public function grade(array $items, GradingLevel $level): GradingResult
    {
        $deadline = microtime(true) + $this->timeLimitSeconds;
        $usage = ['input' => 0, 'output' => 0, 'search' => 0];

        $messages = [['role' => 'user', 'content' => $this->prompt->userMessage($items)]];
        $response = $this->send($messages, $items, $level, $deadline, $usage);
        $toolInput = $this->findSubmittedGrades($response);

        if ($toolInput === null) {
            // 2回目: Web検索だけして提出せずに終わった場合など。会話を引き継いで提出を頼む
            $messages[] = ['role' => 'assistant', 'content' => $response->content];
            $messages[] = ['role' => 'user', 'content' => self::SUBMIT_REQUEST];
            $response = $this->send($messages, $items, $level, $deadline, $usage);
            $toolInput = $this->findSubmittedGrades($response);
        }

        if ($toolInput === null) {
            throw new GradingFailedException('submit_grades が呼ばれませんでした。');
        }

        return new GradingResult(
            grades: $this->parser->parse($toolInput, $items),
            // 拒否されて別のモデルが採点した場合は、そのモデル名になる
            model: $response->model,
            inputTokens: $usage['input'],
            outputTokens: $usage['output'],
            webSearchRequests: $usage['search'],
        );
    }

    /**
     * API を呼ぶ。途中で止まった(pause_turn)ときは、応答を会話に足して続けさせる。
     *
     * @param  list<array<string, mixed>>  $messages
     * @param  list<GradingItem>  $items
     * @param  array{input: int, output: int, search: int}  $usage  呼び出しごとの利用量を足し込む
     */
    private function send(array &$messages, array $items, GradingLevel $level, float $deadline, array &$usage): BetaMessage
    {
        for ($continuation = 0; ; $continuation++) {
            $response = $this->request($messages, $items, $level, $deadline);
            $this->addUsage($usage, $response);

            if ($response->stopReason === 'refusal') {
                // fallbacks で別のモデルに回しても拒否された
                throw new GradingFailedException('採点を拒否されました: '.($response->stopDetails?->category ?? 'unknown'));
            }

            if ($response->stopReason !== 'pause_turn') {
                return $response;
            }

            if ($continuation >= self::MAX_CONTINUATIONS) {
                throw new GradingFailedException('応答が途中で止まったまま終わりませんでした。');
            }

            $messages[] = ['role' => 'assistant', 'content' => $response->content];
        }
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<GradingItem>  $items
     */
    private function request(array $messages, array $items, GradingLevel $level, float $deadline): BetaMessage
    {
        $remaining = $deadline - microtime(true);

        if ($remaining <= 0) {
            throw new GradingFailedException('採点にかける時間の上限を超えました。');
        }

        return $this->client->beta->messages->create(
            maxTokens: self::MAX_TOKENS,
            messages: $messages,
            model: $this->model,
            fallbacks: 'default',
            system: $this->prompt->system($level),
            tools: [$this->webSearchTool(count($items)), $this->submitGradesTool()],
            toolChoice: ['type' => 'auto'],
            betas: [self::FALLBACK_BETA],
            requestOptions: [
                'transporter' => ($this->transport)($remaining),
                // 再試行は Job が行う(architecture.md 4.3)。SDK でも再試行すると、時間の上限を読めなくなる
                'maxRetries' => 0,
            ],
        );
    }

    /**
     * @return array<string, mixed>|null submit_grades の入力。呼ばれていなければ null
     */
    private function findSubmittedGrades(BetaMessage $response): ?array
    {
        foreach ($response->content as $block) {
            if ($block instanceof BetaToolUseBlock && $block->name === self::SUBMIT_TOOL) {
                return $block->input;
            }
        }

        return null;
    }

    /**
     * @param  array{input: int, output: int, search: int}  $usage
     */
    private function addUsage(array &$usage, BetaMessage $response): void
    {
        $usage['input'] += $response->usage->inputTokens;
        $usage['output'] += $response->usage->outputTokens;
        $usage['search'] += $response->usage->serverToolUse?->webSearchRequests ?? 0;
    }

    /**
     * 時間とともに変わる事実を確かめるための Web検索(サーバー側で実行される)。
     * 回数の上限は 問題数 × 2(最小2、最大10)。
     *
     * @return array<string, mixed>
     */
    private function webSearchTool(int $itemCount): array
    {
        return [
            'type' => 'web_search_20260209',
            'name' => 'web_search',
            'maxUses' => max(2, min(10, $itemCount * 2)),
        ];
    }

    /**
     * 採点結果を提出させるツール。strict にして、引数がスキーマどおりになることを保証する。
     *
     * @return array<string, mixed>
     */
    private function submitGradesTool(): array
    {
        return [
            'name' => self::SUBMIT_TOOL,
            'description' => 'すべての問題の採点結果を提出する',
            'strict' => true,
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'grades' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'position' => ['type' => 'integer', 'description' => '問題番号(0始まり)'],
                                'score' => ['type' => 'integer', 'description' => '0〜100点'],
                                'good_points' => ['type' => 'string', 'description' => '良かった点'],
                                'weak_points' => ['type' => 'string', 'description' => '悪かった点'],
                                'model_answer' => ['type' => 'string', 'description' => '模範解答'],
                            ],
                            'required' => ['position', 'score', 'good_points', 'weak_points', 'model_answer'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['grades'],
                'additionalProperties' => false,
            ],
        ];
    }
}
