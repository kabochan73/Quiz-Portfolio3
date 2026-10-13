<?php

use App\Enums\AttemptStatus;
use App\Models\Question;
use App\Models\Section;
use App\Queries\LatestScores;

beforeEach(function () {
    $this->section = Section::factory()->create();
    [$this->q1, $this->q2] = Question::factory()->count(2)->for($this->section)->create();
});

test('問題ごとに、いちばん新しい挑戦の点数を返す', function () {
    scoredAttempt($this->section, [$this->q1->id => 40, $this->q2->id => 90]);
    scoredAttempt($this->section, [$this->q1->id => 75]);

    expect((new LatestScores)->forQuestions([$this->q1->id, $this->q2->id]))
        ->toBe([$this->q1->id => 75, $this->q2->id => 90]);
});

test('新旧は日時ではなく挑戦の ID で決める', function () {
    $older = scoredAttempt($this->section, [$this->q1->id => 40]);
    scoredAttempt($this->section, [$this->q1->id => 75]);
    // 古い挑戦の日時があとになっていても、ID が小さいほうが古い(architecture.md 2.7)
    // created_at は一括代入できないので、forceFill で書き換える(update では黙って無視される)
    $older->forceFill(['created_at' => now()->addDay()])->save();

    expect((new LatestScores)->forQuestions([$this->q1->id]))->toBe([$this->q1->id => 75]);
});

test('採点が完了していない挑戦は数えない', function (AttemptStatus $status) {
    scoredAttempt($this->section, [$this->q1->id => 40]);
    // 新しい挑戦に点数が残っていても、完了していなければ無視する(再採点の途中など)
    scoredAttempt($this->section, [$this->q1->id => 99])->update(['status' => $status]);

    expect((new LatestScores)->forQuestions([$this->q1->id]))->toBe([$this->q1->id => 40]);
})->with([AttemptStatus::Pending, AttemptStatus::Grading, AttemptStatus::Failed]);

test('一度も採点されていない問題は含まない', function () {
    scoredAttempt($this->section, [$this->q1->id => 40]);

    expect((new LatestScores)->forQuestions([$this->q1->id, $this->q2->id]))->toBe([$this->q1->id => 40]);
});

test('問題を指定しなければ空', function () {
    expect((new LatestScores)->forQuestions([]))->toBe([]);
});

test('挑戦を指定すると、それより前の点数(前回の点数)を返す', function () {
    // architecture.md 3.2
    scoredAttempt($this->section, [$this->q1->id => 40]);
    $second = scoredAttempt($this->section, [$this->q1->id => 60, $this->q2->id => 70]);
    $third = scoredAttempt($this->section, [$this->q1->id => 80, $this->q2->id => 85]);

    expect((new LatestScores)->forQuestions([$this->q1->id, $this->q2->id], beforeAttemptId: $third->id))
        ->toBe([$this->q1->id => 60, $this->q2->id => 70])
        // 2回目の挑戦から見ると、q2 は初回なので含まれない
        ->and((new LatestScores)->forQuestions([$this->q1->id, $this->q2->id], beforeAttemptId: $second->id))
        ->toBe([$this->q1->id => 40]);
});
