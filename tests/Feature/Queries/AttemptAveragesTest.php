<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Models\Question;
use App\Models\Section;
use App\Queries\AttemptAverages;

beforeEach(function () {
    $this->section = Section::factory()->create();
    [$this->q1, $this->q2] = Question::factory()->count(2)->for($this->section)->create();
});

test('挑戦ごとの平均点を返す', function () {
    $first = scoredAttempt($this->section, [$this->q1->id => 70, $this->q2->id => 45]);
    $second = scoredAttempt($this->section, [$this->q1->id => 100]);

    expect((new AttemptAverages)->forAttempts([$first->id, $second->id]))
        ->toBe([$first->id => 57.5, $second->id => 100.0]);
});

test('採点が完了していない挑戦は含まない', function () {
    $attempt = scoredAttempt($this->section, [$this->q1->id => 70]);
    $attempt->update(['status' => AttemptStatus::Grading]);

    expect((new AttemptAverages)->forAttempts([$attempt->id]))->toBe([]);
});

test('問題が削除されて回答が減ったら、残った回答で平均を出す', function () {
    // architecture.md 4.6: 平均点は保存せず毎回計算する
    $attempt = scoredAttempt($this->section, [$this->q1->id => 70, $this->q2->id => 30]);

    $this->q2->delete();

    expect((new AttemptAverages)->forAttempts([$attempt->id]))->toBe([$attempt->id => 70.0]);
});

test('前回の平均点は、同じセクション・同じ種別のひとつ前の完了済みの挑戦から取る', function () {
    $first = scoredAttempt($this->section, [$this->q1->id => 40, $this->q2->id => 60]);
    // 苦手モードの挑戦は、全問モードの前回にはならない
    scoredAttempt($this->section, [$this->q1->id => 90])->update(['mode' => AttemptMode::Weak]);
    // 失敗した挑戦は飛ばす
    scoredAttempt($this->section, [$this->q1->id => 10, $this->q2->id => 10])->update(['status' => AttemptStatus::Failed]);
    $latest = scoredAttempt($this->section, [$this->q1->id => 80, $this->q2->id => 80]);

    expect((new AttemptAverages)->previous($latest))->toBe(50.0)
        ->and((new AttemptAverages)->previous($first))->toBeNull();
});

test('別のセクションの挑戦は、前回にならない', function () {
    $other = Section::factory()->create();
    $otherQuestion = Question::factory()->for($other)->create();
    scoredAttempt($other, [$otherQuestion->id => 50]);
    $attempt = scoredAttempt($this->section, [$this->q1->id => 80]);

    expect((new AttemptAverages)->previous($attempt))->toBeNull();
});

test('対象を指定しなければ空', function () {
    expect((new AttemptAverages)->forAttempts([]))->toBe([]);
});
