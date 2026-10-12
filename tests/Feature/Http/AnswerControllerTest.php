<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Jobs\GradeAttempt;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

// 画面(Blade)はまだないので、ここでは送信の結果と、フォームを開けないときの移動だけを確かめる。画面の表示はフェーズ5で確かめる

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->section = Section::factory()->create();
    $this->questions = Question::factory()->count(2)->for($this->section)->create();
});

function submitAnswers(Section $section, iterable $questions, array $overrides = []): TestResponse
{
    return test()->post("/sections/{$section->id}/answers", [
        'grading_level' => 'strict',
        'mode' => 'all',
        'answers' => collect($questions)->map(fn (Question $q) => ['question_id' => $q->id, 'body' => "回答 {$q->id}"])->values()->all(),
        ...$overrides,
    ]);
}

test('回答を送信すると、待機中の挑戦と回答を保存し、採点 Job を入れて結果画面へ移る', function () {
    // architecture.md 4.2
    Queue::fake();

    $response = submitAnswers($this->section, $this->questions);

    $attempt = Attempt::sole();
    $response->assertRedirect(route('sections.history.show', [$this->section, $attempt]));
    expect($attempt->status)->toBe(AttemptStatus::Pending)
        ->and($attempt->grading_level)->toBe(GradingLevel::Strict)
        ->and($attempt->mode)->toBe(AttemptMode::All)
        ->and($attempt->section_id)->toBe($this->section->id);
    Queue::assertPushed(GradeAttempt::class, fn (GradeAttempt $job) => $job->attempt->is($attempt));
});

test('送られた順番が、挑戦の中での順番になる', function () {
    Queue::fake();
    [$q1, $q2] = $this->questions;

    submitAnswers($this->section, [$q2, $q1]);

    expect(Answer::orderBy('position')->get(['question_id', 'position', 'body'])->toArray())->toBe([
        ['question_id' => $q2->id, 'position' => 0, 'body' => "回答 {$q2->id}"],
        ['question_id' => $q1->id, 'position' => 1, 'body' => "回答 {$q1->id}"],
    ]);
});

test('入力に誤りがあれば、挑戦を作らず、Job も入れない', function () {
    Queue::fake();

    submitAnswers($this->section, $this->questions->take(1))->assertSessionHasErrors('answers');

    expect(Attempt::count())->toBe(0);
    Queue::assertNothingPushed();
});

test('送信したときに、古い回答を整理する', function () {
    // architecture.md 4.5。保持件数を1件にして確かめる
    Queue::fake();
    config(['quiz.answer_retention_limit' => 1]);
    $old = scoredAttempt($this->section, $this->questions->mapWithKeys(fn (Question $q) => [$q->id => 50])->all());

    submitAnswers($this->section, $this->questions);

    expect(Attempt::find($old->id))->toBeNull()
        ->and(Attempt::count())->toBe(1);
});

test('採点 Job は、トランザクションが確定してから入れる', function () {
    // 確定前に worker が動き出すと、まだない挑戦を読もうとしてしまう(architecture.md 4.2)
    Queue::fake();

    submitAnswers($this->section, $this->questions);

    Queue::assertPushed(GradeAttempt::class, fn (GradeAttempt $job) => $job->afterCommit === true);
});

test('採点まで通して動かすと、結果が保存される', function () {
    // テストのキューは同期実行なので、送信した時点でフェイクの採点まで終わる
    submitAnswers($this->section, $this->questions);

    expect(Attempt::sole()->status)->toBe(AttemptStatus::Completed)
        ->and(Answer::has('score')->count())->toBe(2);
});

test('苦手問題がないのに苦手モードの回答フォームを開くと、セクション詳細に戻す', function () {
    $this->get("/sections/{$this->section->id}/answers/create?mode=weak")
        ->assertRedirect(route('sections.show', $this->section))
        ->assertSessionHas('toast', ['type' => 'error', 'message' => 'No weak questions.']);
});

test('問題がないセクションの回答フォームを開くと、セクション詳細に戻す', function () {
    $empty = Section::factory()->create();

    $this->get("/sections/{$empty->id}/answers/create")
        ->assertRedirect(route('sections.show', $empty))
        ->assertSessionHas('toast', ['type' => 'error', 'message' => 'No questions yet.']);
});

test('存在しないセクションには回答できない', function () {
    $this->post('/sections/999999/answers', [])->assertNotFound();
});

test('ログインしていなければ、回答できない', function () {
    Queue::fake();
    auth()->logout();

    submitAnswers($this->section, $this->questions)->assertRedirect(route('login'));

    expect(Attempt::count())->toBe(0);
});
