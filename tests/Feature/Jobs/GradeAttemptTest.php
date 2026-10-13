<?php

use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Jobs\GradeAttempt;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Section;
use App\Services\Grading\FakeGradingService;
use App\Services\Grading\GradingFailedException;
use App\Services\Grading\GradingResult;
use App\Services\Grading\GradingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;

/**
 * 送信直後(pending)の挑戦を作る。問題2問に回答済み。
 */
function pendingAttempt(GradingLevel $level = GradingLevel::Normal): Attempt
{
    $section = Section::factory()->create();
    $questions = Question::factory()->count(2)->for($section)->create();
    $attempt = Attempt::factory()->for($section)->create(['grading_level' => $level]);

    foreach ($questions as $position => $question) {
        $attempt->answers()->create(['question_id' => $question->id, 'position' => $position, 'body' => "回答 {$position}"]);
    }

    return $attempt;
}

/**
 * 呼ばれたら失敗する採点サービス。「採点しないこと」を確かめるのに使う。
 */
function gradingMustNotBeCalled(): void
{
    app()->instance(GradingService::class, new class implements GradingService
    {
        public function grade(array $items, GradingLevel $level): GradingResult
        {
            throw new LogicException('採点サービスが呼ばれました');
        }
    });
}

test('採点して、点数と利用量を保存し、完了にする', function () {
    $attempt = pendingAttempt();

    GradeAttempt::dispatchSync($attempt);

    $attempt->refresh();
    expect($attempt->status)->toBe(AttemptStatus::Completed)
        ->and($attempt->graded_at)->not->toBeNull()
        ->and($attempt->model)->toBe('fake')
        ->and($attempt->input_tokens)->toBe(0)
        ->and($attempt->answers->map(fn (Answer $answer) => $answer->score->score)->all())
        ->toBe([FakeGradingService::scoreFor('回答 0'), FakeGradingService::scoreFor('回答 1')]);
});

test('採点サービスには、問題文と回答を出題順に、選んだ採点レベルで渡す', function () {
    $attempt = pendingAttempt(GradingLevel::Strict);
    $spy = new class implements GradingService
    {
        public array $items = [];

        public ?GradingLevel $level = null;

        public function grade(array $items, GradingLevel $level): GradingResult
        {
            [$this->items, $this->level] = [$items, $level];

            return (new FakeGradingService)->grade($items, $level);
        }
    };
    app()->instance(GradingService::class, $spy);

    GradeAttempt::dispatchSync($attempt);

    expect($spy->level)->toBe(GradingLevel::Strict)
        ->and(array_column($spy->items, 'position'))->toBe([0, 1])
        ->and(array_column($spy->items, 'answer'))->toBe(['回答 0', '回答 1'])
        ->and($spy->items[0]->question)->toBe($attempt->answers[0]->question_body);
});

test('採点には、回答したときの問題文の写しを渡す。問題を書き直したり消したりしたあとでも同じ', function () {
    // architecture.md 2.5(2026-10-13 決定)。失敗した挑戦を、あとで再採点するときなど
    $attempt = pendingAttempt();
    [$first, $second] = $attempt->answers;
    $originalBodies = [$first->question_body, $second->question_body];
    $first->question->update(['body' => '書き直した問題文']);
    $second->question->delete();

    $spy = new class implements GradingService
    {
        public array $items = [];

        public function grade(array $items, GradingLevel $level): GradingResult
        {
            $this->items = $items;

            return (new FakeGradingService)->grade($items, $level);
        }
    };
    app()->instance(GradingService::class, $spy);

    GradeAttempt::dispatchSync($attempt);

    expect(array_column($spy->items, 'question'))->toBe($originalBodies)
        ->and($attempt->refresh()->status)->toBe(AttemptStatus::Completed);
});

test('前の試行が途中で止まった挑戦(grading)は、もう一度採点する', function () {
    $attempt = pendingAttempt();
    $attempt->update(['status' => AttemptStatus::Grading]);

    GradeAttempt::dispatchSync($attempt);

    expect($attempt->refresh()->status)->toBe(AttemptStatus::Completed);
});

test('すでに完了・失敗している挑戦は、採点し直さない', function (AttemptStatus $status) {
    // 二重に採点しない(architecture.md 4.3)。失敗した挑戦は「再採点」で pending に戻してから採点する
    $attempt = pendingAttempt();
    $attempt->update(['status' => $status]);
    gradingMustNotBeCalled();

    GradeAttempt::dispatchSync($attempt);

    expect($attempt->refresh()->status)->toBe($status);
})->with([AttemptStatus::Completed, AttemptStatus::Failed]);

test('採点に失敗したら例外のまま終わり、点数も完了の状態も保存しない', function () {
    // 例外を投げれば、キューがリトライしてくれる
    $attempt = pendingAttempt();
    app()->instance(GradingService::class, new class implements GradingService
    {
        public function grade(array $items, GradingLevel $level): GradingResult
        {
            throw new GradingFailedException('API error');
        }
    });

    expect(fn () => (new GradeAttempt($attempt))->handle(app(GradingService::class)))
        ->toThrow(GradingFailedException::class);

    expect($attempt->refresh()->status)->toBe(AttemptStatus::Grading)
        ->and(Answer::has('score')->count())->toBe(0);
});

test('3回とも失敗したら、失敗の状態と理由を記録する', function () {
    Log::spy();
    $attempt = pendingAttempt();

    (new GradeAttempt($attempt))->failed(new GradingFailedException('API error'));

    $attempt->refresh();
    expect($attempt->status)->toBe(AttemptStatus::Failed)
        ->and($attempt->error_message)->toContain('GradingFailedException')->toContain('API error');
    Log::shouldHaveReceived('error')->once();
});

test('リトライ・時間の上限・二重実行の防止を設計どおりに設定している', function () {
    // architecture.md 4.3
    $job = new GradeAttempt(pendingAttempt());

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([15, 60])
        ->and($job->timeout)->toBe(240)
        ->and($job)->toBeInstanceOf(ShouldBeUnique::class)
        ->and($job->uniqueId())->toBe((string) $job->attempt->id)
        // キューの retry_after は、Job の時間の上限より長くなければならない(Laravel の決まり)
        ->and(config('queue.connections.database.retry_after'))->toBeGreaterThan($job->timeout);
});
