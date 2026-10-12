<?php

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use App\Enums\GradingLevel;
use App\Services\Grading\ClaudeGradingService;
use App\Services\Grading\GradeResultParser;
use App\Services\Grading\GradingFailedException;
use App\Services\Grading\GradingItem;
use App\Services\Grading\GradingPrompt;
use App\Services\Grading\InvalidGradingResultException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

// 本物の SDK を使い、HTTP の通信部分だけを「決まった応答を返すモック」に差し替えてテストする。
// ネットワークにはつながらないので、API の料金はかからない(CLAUDE.md)。

/**
 * @return list<GradingItem>
 */
function claudeItems(): array
{
    return [
        new GradingItem(0, 'TCPとUDPの違いは?', 'TCPは信頼性が高い。'),
        new GradingItem(1, 'DNSとは?', '名前をIPアドレスに変える仕組み。'),
    ];
}

/**
 * Messages API の応答(JSON)を作る。
 */
function claudeResponse(array $content, string $stopReason = 'tool_use', array $usage = [], string $model = 'claude-sonnet-5-5'): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode([
        'id' => 'msg_test',
        'type' => 'message',
        'role' => 'assistant',
        'model' => $model,
        'content' => $content,
        'stop_reason' => $stopReason,
        'stop_sequence' => null,
        'usage' => [
            'input_tokens' => 100,
            'output_tokens' => 50,
            'server_tool_use' => ['web_search_requests' => 0, 'web_fetch_requests' => 0],
            ...$usage,
        ],
    ]));
}

function submitGradesBlock(array $grades): array
{
    return ['type' => 'tool_use', 'id' => 'toolu_test', 'name' => 'submit_grades', 'input' => ['grades' => $grades]];
}

function validGrades(): array
{
    return array_map(fn (int $position) => [
        'position' => $position,
        'score' => 80 - $position * 30,
        'good_points' => '良かった点',
        'weak_points' => '悪かった点',
        'model_answer' => '模範解答',
    ], [0, 1]);
}

/**
 * モックの応答を順に返す ClaudeGradingService を作る。送ったリクエストは $history に残る。
 *
 * @param  list<Response>  $responses
 * @param  array<int, array{request: RequestInterface}>  $history
 * @param  list<float>  $timeouts  呼び出しごとに渡されたタイムアウト
 */
function claudeService(array $responses, ?array &$history = null, ?array &$timeouts = null, float $timeLimit = 200.0): ClaudeGradingService
{
    $history = [];
    $timeouts = [];
    $stack = HandlerStack::create(new MockHandler($responses));
    $stack->push(Middleware::history($history));

    return new ClaudeGradingService(
        client: new Client(apiKey: 'test-key'),
        prompt: new GradingPrompt,
        parser: new GradeResultParser,
        model: 'claude-sonnet-5-5',
        timeLimitSeconds: $timeLimit,
        transport: function (float $timeout) use ($stack, &$timeouts) {
            $timeouts[] = $timeout;

            return new GuzzleClient(['handler' => $stack]);
        },
    );
}

function sentBody(array $history, int $index): array
{
    return json_decode((string) $history[$index]['request']->getBody(), true);
}

test('1回目で submit_grades が呼ばれれば、その結果を返す', function () {
    $service = claudeService([claudeResponse([submitGradesBlock(validGrades())])], $history);

    $result = $service->grade(claudeItems(), GradingLevel::Normal);

    expect($history)->toHaveCount(1)
        ->and(array_column($result->grades, 'score'))->toBe([80, 50])
        ->and($result->model)->toBe('claude-sonnet-5-5')
        ->and($result->inputTokens)->toBe(100)
        ->and($result->outputTokens)->toBe(50);
});

test('リクエストに、設計どおりのツール・指示文・フォールバックの指定が入る', function () {
    claudeService([claudeResponse([submitGradesBlock(validGrades())])], $history)
        ->grade(claudeItems(), GradingLevel::Strict);

    $body = sentBody($history, 0);
    $request = $history[0]['request'];

    expect($body['model'])->toBe('claude-sonnet-5-5')
        ->and($body['max_tokens'])->toBe(16000)
        ->and($body['tool_choice'])->toBe(['type' => 'auto'])
        ->and($body['fallbacks'])->toBe('default')
        ->and($request->getHeaderLine('anthropic-beta'))->toContain('server-side-fallback-2026-07-01')
        ->and($body['system'])->toContain(GradingLevel::Strict->policyText())
        ->and($body['messages'][0]['content'])->toContain('<item position="1">');

    // Web検索: 2問 × 2 = 4回まで
    expect($body['tools'][0])->toBe(['type' => 'web_search_20260209', 'name' => 'web_search', 'max_uses' => 4]);

    // submit_grades: strict で、JSON Schema のキー(additionalProperties)や項目名がそのまま送られる
    $submit = $body['tools'][1];
    expect($submit['name'])->toBe('submit_grades')
        ->and($submit['strict'])->toBeTrue()
        ->and($submit['input_schema']['additionalProperties'])->toBeFalse()
        ->and($submit['input_schema']['properties']['grades']['items']['required'])
        ->toBe(['position', 'score', 'good_points', 'weak_points', 'model_answer']);
});

test('1回目で提出されなければ、会話を引き継いで提出を頼み、利用量は合計する', function () {
    $service = claudeService([
        claudeResponse([['type' => 'text', 'text' => '調べました。']], 'end_turn', ['server_tool_use' => ['web_search_requests' => 2, 'web_fetch_requests' => 0]]),
        claudeResponse([submitGradesBlock(validGrades())]),
    ], $history);

    $result = $service->grade(claudeItems(), GradingLevel::Normal);

    $messages = sentBody($history, 1)['messages'];
    expect($messages)->toHaveCount(3)
        ->and($messages[1]['role'])->toBe('assistant')
        ->and($messages[2]['content'])->toBe('採点結果を submit_grades で提出してください。')
        ->and($result->inputTokens)->toBe(200)
        ->and($result->outputTokens)->toBe(100)
        ->and($result->webSearchRequests)->toBe(2);
});

test('2回目でも提出されなければ失敗にする', function () {
    claudeService([
        claudeResponse([['type' => 'text', 'text' => 'a']], 'end_turn'),
        claudeResponse([['type' => 'text', 'text' => 'b']], 'end_turn'),
    ])->grade(claudeItems(), GradingLevel::Normal);
})->throws(GradingFailedException::class, 'submit_grades');

test('途中で止まった(pause_turn)ら、応答を会話に足して続けさせる', function () {
    $service = claudeService([
        claudeResponse([['type' => 'text', 'text' => '検索中です。']], 'pause_turn'),
        claudeResponse([submitGradesBlock(validGrades())]),
    ], $history);

    $service->grade(claudeItems(), GradingLevel::Normal);

    $messages = sentBody($history, 1)['messages'];
    expect($messages)->toHaveCount(2)
        ->and($messages[1]['role'])->toBe('assistant');
});

test('pause_turn が続きすぎたら失敗にする', function () {
    // 最初の1回 + 続き3回 = 4回とも pause_turn
    $paused = fn () => claudeResponse([['type' => 'text', 'text' => '…']], 'pause_turn');

    claudeService([$paused(), $paused(), $paused(), $paused()])->grade(claudeItems(), GradingLevel::Normal);
})->throws(GradingFailedException::class, '途中で止まった');

test('フォールバックしても拒否されたら失敗にする', function () {
    $response = new Response(200, ['Content-Type' => 'application/json'], json_encode([
        'id' => 'msg_test', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-sonnet-5-5',
        'content' => [], 'stop_reason' => 'refusal', 'stop_sequence' => null,
        'stop_details' => ['type' => 'refusal', 'category' => 'cyber', 'explanation' => null],
        'usage' => ['input_tokens' => 10, 'output_tokens' => 0],
    ]));

    claudeService([$response])->grade(claudeItems(), GradingLevel::Normal);
})->throws(GradingFailedException::class, 'cyber');

test('別のモデルにフォールバックして採点されたら、そのモデル名を記録する', function () {
    $result = claudeService([claudeResponse([submitGradesBlock(validGrades())], model: 'claude-sonnet-5')])
        ->grade(claudeItems(), GradingLevel::Normal);

    expect($result->model)->toBe('claude-sonnet-5');
});

test('提出された結果が不正なら、検証の例外をそのまま投げる', function () {
    claudeService([claudeResponse([submitGradesBlock([validGrades()[0]])])])
        ->grade(claudeItems(), GradingLevel::Normal);
})->throws(InvalidGradingResultException::class);

test('各呼び出しには、残り時間をタイムアウトとして渡す', function () {
    claudeService([
        claudeResponse([['type' => 'text', 'text' => 'a']], 'end_turn'),
        claudeResponse([submitGradesBlock(validGrades())]),
    ], $history, $timeouts)->grade(claudeItems(), GradingLevel::Normal);

    expect($timeouts)->toHaveCount(2)
        ->and($timeouts[0])->toBeLessThanOrEqual(200.0)->toBeGreaterThan(199.0)
        ->and($timeouts[1])->toBeLessThanOrEqual($timeouts[0]);
});

test('時間の上限を過ぎていたら、API を呼ばずに失敗にする', function () {
    claudeService([], $history, timeLimit: 0.0)->grade(claudeItems(), GradingLevel::Normal);
})->throws(GradingFailedException::class, '時間の上限');

test('SDK 側では再試行しない', function () {
    // 500 エラーが1回返ったら、再試行せずにそのまま例外にする(再試行は Job が行う)
    $service = claudeService([new Response(500, [], '{}'), claudeResponse([submitGradesBlock(validGrades())])], $history);

    try {
        $service->grade(claudeItems(), GradingLevel::Normal);
    } catch (APIException) {
    }

    expect($history)->toHaveCount(1);
});
