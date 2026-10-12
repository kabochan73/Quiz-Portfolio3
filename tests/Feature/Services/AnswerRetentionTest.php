<?php

use App\Enums\AttemptStatus;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Section;
use App\Services\AnswerRetentionService;
use App\Services\EmptyAttemptPruner;

beforeEach(function () {
    config(['quiz.answer_retention_limit' => 3]);
    $this->section = Section::factory()->create();
    [$this->q1, $this->q2] = Question::factory()->count(2)->for($this->section)->create();
});

test('同じ問題の回答は直近の件数だけ残し、古いものから削除する', function () {
    $attempts = collect(range(1, 5))->map(fn () => scoredAttempt($this->section, [$this->q1->id => 50]));

    app(AnswerRetentionService::class)->prune([$this->q1->id]);

    // 新しい3件(3〜5回目)だけが残る。採点結果も一緒に消える
    expect(Answer::where('question_id', $this->q1->id)->orderBy('attempt_id')->pluck('attempt_id')->all())
        ->toBe($attempts->slice(2)->pluck('id')->values()->all())
        ->and(Answer::has('score')->count())->toBe(3);
});

test('回答が0件になった挑戦も削除し、回答が残る挑戦は残す', function () {
    // 1回目だけ q2 にも回答している
    $first = scoredAttempt($this->section, [$this->q1->id => 50, $this->q2->id => 50]);
    $second = scoredAttempt($this->section, [$this->q1->id => 50]);
    collect(range(1, 3))->each(fn () => scoredAttempt($this->section, [$this->q1->id => 50]));

    app(AnswerRetentionService::class)->prune([$this->q1->id]);

    // 1回目は q2 の回答が残るので残し、2回目は空になるので消す
    expect(Attempt::find($first->id))->not->toBeNull()
        ->and(Attempt::find($second->id))->toBeNull();
});

test('採点中・失敗した挑戦の回答も件数に含める', function () {
    $oldest = scoredAttempt($this->section, [$this->q1->id => 50]);
    collect([AttemptStatus::Pending, AttemptStatus::Failed, AttemptStatus::Grading])
        ->each(fn (AttemptStatus $status) => scoredAttempt($this->section, [$this->q1->id => 50])->update(['status' => $status]));

    app(AnswerRetentionService::class)->prune([$this->q1->id]);

    expect(Attempt::find($oldest->id))->toBeNull()
        ->and(Answer::where('question_id', $this->q1->id)->count())->toBe(3);
});

test('件数が上限以下なら何も削除しない', function () {
    collect(range(1, 3))->each(fn () => scoredAttempt($this->section, [$this->q1->id => 50]));

    app(AnswerRetentionService::class)->prune([$this->q1->id]);

    expect(Answer::count())->toBe(3)
        ->and(Attempt::count())->toBe(3);
});

test('指定していない問題の回答は整理しない', function () {
    collect(range(1, 5))->each(fn () => scoredAttempt($this->section, [$this->q2->id => 50]));

    app(AnswerRetentionService::class)->prune([$this->q1->id]);

    expect(Answer::where('question_id', $this->q2->id)->count())->toBe(5);
});

test('保持件数の既定値は10件', function () {
    // requirements.md 3.4
    config(['quiz.answer_retention_limit' => (require config_path('quiz.php'))['answer_retention_limit']]);
    collect(range(1, 11))->each(fn () => scoredAttempt($this->section, [$this->q1->id => 50]));

    app(AnswerRetentionService::class)->prune([$this->q1->id]);

    expect(Answer::count())->toBe(10);
});

test('空の挑戦の削除は、回答が残っている挑戦には何もしない', function () {
    $withAnswers = scoredAttempt($this->section, [$this->q1->id => 50]);
    $empty = Attempt::factory()->for($this->section)->create();

    $deleted = app(EmptyAttemptPruner::class)->prune([$withAnswers->id, $empty->id]);

    expect($deleted)->toBe(1)
        ->and(Attempt::pluck('id')->all())->toBe([$withAnswers->id]);
});
