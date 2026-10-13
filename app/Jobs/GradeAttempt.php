<?php

namespace App\Jobs;

use App\Enums\AttemptStatus;
use App\Models\Answer;
use App\Models\Attempt;
use App\Services\Grading\Grade;
use App\Services\Grading\GradingItem;
use App\Services\Grading\GradingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * 1回の挑戦を採点する(requirements.md 3.3、architecture.md 4.3)。
 * 回答の送信とは切り離してワーカーで動かすので、採点に時間がかかっても画面は待たされない。
 */
final class GradeAttempt implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    // API 起因の一時的な失敗を吸収するため、3回まで試す
    public int $tries = 3;

    // 2回目は15秒後、3回目は60秒後
    public array $backoff = [15, 60];

    // 1回の試行の上限。採点サービスの時間の上限(200秒)+ 余裕。キューの retry_after(300秒)より短くする
    public int $timeout = 240;

    // 採点を待つ間に挑戦が削除された(セクションの削除など)ら、Job も黙って捨てる
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Attempt $attempt) {}

    /**
     * 同じ挑戦の Job が二重に動かないようにする(ShouldBeUnique のキー)。
     */
    public function uniqueId(): string
    {
        return (string) $this->attempt->id;
    }

    public function handle(GradingService $grading): void
    {
        // 試行のたびに grading にし直す。すでに completed / failed なら何もしない(リロードなどで二重に採点しない)
        $started = Attempt::query()
            ->whereKey($this->attempt->id)
            ->whereIn('status', [AttemptStatus::Pending, AttemptStatus::Grading])
            ->update(['status' => AttemptStatus::Grading]);

        if ($started === 0) {
            return;
        }

        $answers = $this->attempt->answers()->get()->keyBy('position');

        $result = $grading->grade(
            $answers->map(fn (Answer $answer) => new GradingItem(
                position: $answer->position,
                // 回答したときの問題文の写しを使う。問題を消したり書き直したりしたあとの再採点でも、その時の問題で採点できる(architecture.md 2.5)
                question: $answer->question_body,
                answer: $answer->body,
            ))->values()->all(),
            $this->attempt->grading_level,
        );

        // 採点結果と「完了」の状態は、片方だけ保存されることがないよう1つのトランザクションで保存する
        DB::transaction(function () use ($answers, $result) {
            foreach ($result->grades as $grade) {
                /** @var Grade $grade */
                $answers[$grade->position]->score()->updateOrCreate([], [
                    'score' => $grade->score,
                    'good_points' => $grade->goodPoints,
                    'weak_points' => $grade->weakPoints,
                    'model_answer' => $grade->modelAnswer,
                ]);
            }

            $this->attempt->update([
                'status' => AttemptStatus::Completed,
                'graded_at' => now(),
                'model' => $result->model,
                'input_tokens' => $result->inputTokens,
                'output_tokens' => $result->outputTokens,
                'web_search_requests' => $result->webSearchRequests,
                'error_message' => null,
            ]);
        });
    }

    /**
     * 3回とも失敗したとき。画面には「Grading failed」と再採点のボタンを出す(screens.md 2.10 (c))。
     * 失敗の詳しい理由は画面に出さず、ログと error_message に残す。
     */
    public function failed(?Throwable $exception): void
    {
        $this->attempt->update([
            'status' => AttemptStatus::Failed,
            'error_message' => Str::limit($exception === null ? 'unknown' : $exception::class.': '.$exception->getMessage(), 1000),
        ]);

        Log::error('採点に失敗しました', ['attempt_id' => $this->attempt->id, 'exception' => $exception]);
    }
}
