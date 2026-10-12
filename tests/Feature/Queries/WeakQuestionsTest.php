<?php

use App\Models\Question;
use App\Models\Section;
use App\Queries\WeakQuestions;

test('最新の点数が基準点未満の問題だけを、問題の並び順で返す', function () {
    $section = Section::factory()->create();
    [$q1, $q2, $q3, $unscored] = Question::factory()->count(4)->for($section)->create();
    scoredAttempt($section, [$q1->id => 30, $q2->id => 50, $q3->id => 59]);
    // q2 は2回目で基準点(60点)ちょうどになったので、苦手ではなくなる
    scoredAttempt($section, [$q2->id => 60]);

    expect(app(WeakQuestions::class)->idsFor($section))->toBe([$q1->id, $q3->id]);
});

test('一度も採点されていない問題は苦手に含めない', function () {
    $section = Section::factory()->create();
    Question::factory()->count(2)->for($section)->create();

    expect(app(WeakQuestions::class)->idsFor($section))->toBe([]);
});

test('基準点は config で変えられる', function () {
    // requirements.md 3.3: 苦手判定の基準点は config で変更できる
    config(['quiz.weak_threshold' => 80]);
    $section = Section::factory()->create();
    $question = Question::factory()->for($section)->create();
    scoredAttempt($section, [$question->id => 70]);

    expect(app(WeakQuestions::class)->idsFor($section))->toBe([$question->id]);
});

test('別のセクションの問題は含めない', function () {
    $section = Section::factory()->create();
    $other = Section::factory()->create();
    $otherQuestion = Question::factory()->for($other)->create();
    scoredAttempt($other, [$otherQuestion->id => 10]);

    expect(app(WeakQuestions::class)->idsFor($section))->toBe([]);
});
