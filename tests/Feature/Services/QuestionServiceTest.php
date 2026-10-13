<?php

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Section;
use App\Services\QuestionLimitExceededException;
use App\Services\QuestionService;
use Illuminate\Support\Facades\DB;

test('問題を追加できる', function () {
    $section = Section::factory()->create();

    $question = app(QuestionService::class)->create($section, 'TCPとUDPの違いは?');

    expect($question->section_id)->toBe($section->id)
        ->and($question->body)->toBe('TCPとUDPの違いは?');
});

test('上限の問題数に達していたら追加できない', function () {
    // requirements.md 3.2
    config(['quiz.max_questions_per_section' => 3]);
    $section = Section::factory()->create();
    Question::factory()->count(3)->for($section)->create();

    app(QuestionService::class)->create($section, 'Q4');
})->throws(QuestionLimitExceededException::class);

test('上限ちょうどまでは追加できる', function () {
    config(['quiz.max_questions_per_section' => 3]);
    $section = Section::factory()->create();
    Question::factory()->count(2)->for($section)->create();

    app(QuestionService::class)->create($section, 'Q3');

    expect($section->questions()->count())->toBe(3);
});

test('問題数の上限の既定値は10問', function () {
    $section = Section::factory()->create();
    Question::factory()->count(10)->for($section)->create();

    app(QuestionService::class)->create($section, 'Q11');
})->throws(QuestionLimitExceededException::class);

test('問題数を数える前に、セクションの行をロックする', function () {
    // 同時に追加されて上限を超えないため(architecture.md 4.1)
    $section = Section::factory()->create();
    DB::enableQueryLog();

    app(QuestionService::class)->create($section, 'Q1');

    $queries = collect(DB::getQueryLog())->pluck('query');
    $lockIndex = $queries->search(fn (string $sql) => str_contains($sql, 'from "sections"') && str_contains($sql, 'for update'));
    $countIndex = $queries->search(fn (string $sql) => str_contains($sql, 'count(*)') && str_contains($sql, 'from "questions"'));

    expect($lockIndex)->not->toBeFalse()
        ->and($lockIndex)->toBeLessThan($countIndex);
});

test('問題を削除しても、回答・採点結果・挑戦は残り、回答には問題文の写しが残る', function () {
    // architecture.md 2.5 / 4.6(2026-10-13 決定): 履歴を残す
    $section = Section::factory()->create();
    [$q1, $q2] = Question::factory()->count(2)->for($section)->create();
    $q1Body = $q1->body;
    $onlyQ1 = scoredAttempt($section, [$q1->id => 50]);
    $both = scoredAttempt($section, [$q1->id => 50, $q2->id => 80]);

    app(QuestionService::class)->delete($q1);

    $kept = $onlyQ1->answers()->sole();
    expect(Question::find($q1->id))->toBeNull()
        ->and(Attempt::find($onlyQ1->id))->not->toBeNull()
        ->and($both->answers()->count())->toBe(2)
        ->and($kept->question_id)->toBeNull()
        ->and($kept->question_body)->toBe($q1Body)
        ->and($kept->score)->not->toBeNull();
});

test('回答のない問題も削除できる', function () {
    $question = Question::factory()->create();

    app(QuestionService::class)->delete($question);

    expect(Question::count())->toBe(0);
});
