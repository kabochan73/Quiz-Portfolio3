<?php

use App\Models\Answer;
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

test('問題を削除すると、回答と採点結果も消え、空になった挑戦も消える', function () {
    // architecture.md 4.6
    $section = Section::factory()->create();
    [$q1, $q2] = Question::factory()->count(2)->for($section)->create();
    $onlyQ1 = scoredAttempt($section, [$q1->id => 50]);
    $both = scoredAttempt($section, [$q1->id => 50, $q2->id => 80]);

    app(QuestionService::class)->delete($q1);

    expect(Question::find($q1->id))->toBeNull()
        ->and(Answer::where('question_id', $q1->id)->exists())->toBeFalse()
        // q1 にだけ回答していた挑戦は空になるので消える。q2 の回答が残る挑戦は残る
        ->and(Attempt::find($onlyQ1->id))->toBeNull()
        ->and(Attempt::find($both->id))->not->toBeNull()
        ->and($both->answers()->count())->toBe(1);
});

test('回答のない問題も削除できる', function () {
    $question = Question::factory()->create();

    app(QuestionService::class)->delete($question);

    expect(Question::count())->toBe(0);
});
