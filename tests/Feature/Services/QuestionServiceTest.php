<?php

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
