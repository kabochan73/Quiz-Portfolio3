<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Score;
use App\Models\Section;

test('採点レベル・種別・状態は Enum として読み書きできる', function () {
    $attempt = Attempt::factory()->create([
        'grading_level' => GradingLevel::Strict,
        'mode' => AttemptMode::Weak,
    ])->fresh();

    expect($attempt->grading_level)->toBe(GradingLevel::Strict)
        ->and($attempt->mode)->toBe(AttemptMode::Weak)
        ->and($attempt->status)->toBe(AttemptStatus::Pending);
});

test('採点済みの挑戦は、利用量と採点日時を持つ', function () {
    $attempt = Attempt::factory()->completed()->create()->fresh();

    expect($attempt->status)->toBe(AttemptStatus::Completed)
        ->and($attempt->model)->toBe('claude-sonnet-5-5')
        ->and($attempt->input_tokens)->toBeInt()
        ->and($attempt->graded_at)->not->toBeNull();
});

test('回答の Factory は、挑戦と同じセクションの問題を使う', function () {
    $attempt = Attempt::factory()->create();
    $answers = Answer::factory()->count(3)->inOrder()->for($attempt)->create();

    expect($answers->pluck('position')->all())->toBe([0, 1, 2])
        ->and($answers->map(fn (Answer $answer) => $answer->question->section_id)->unique()->all())
        ->toBe([$attempt->section_id]);
});

test('回答の Factory は、1件ずつ作れば順番を自動で振る', function () {
    $attempt = Attempt::factory()->create();
    $first = Answer::factory()->for($attempt)->create();
    $second = Answer::factory()->for($attempt)->create();

    expect([$first->position, $second->position])->toBe([0, 1]);
});

test('挑戦の回答は出題した順に並ぶ', function () {
    $attempt = Attempt::factory()->create();
    $questions = Question::factory()->count(2)->for($attempt->section)->create();
    // 作った順と出題した順が逆でも、position の順に並ぶ
    $second = Answer::factory()->for($attempt)->create(['question_id' => $questions[0]->id, 'position' => 1]);
    $first = Answer::factory()->for($attempt)->create(['question_id' => $questions[1]->id, 'position' => 0]);

    expect($attempt->answers->pluck('id')->all())->toBe([$first->id, $second->id]);
});

test('回答から採点結果をたどれる。採点前は null', function () {
    $graded = Score::factory()->create()->answer;
    $notGraded = Answer::factory()->create();

    expect($graded->score)->toBeInstanceOf(Score::class)
        ->and($notGraded->score)->toBeNull();
});

test('セクションから挑戦を、問題から回答をたどれる', function () {
    $answer = Answer::factory()->create();

    expect($answer->attempt->section->attempts->pluck('id')->all())->toBe([$answer->attempt_id])
        ->and($answer->question->answers->pluck('id')->all())->toBe([$answer->id]);
});

test('挑戦のセクションは一括代入で変えられない', function () {
    $attempt = Attempt::factory()->create();
    $otherSection = Section::factory()->create();

    $attempt->update(['section_id' => $otherSection->id, 'status' => AttemptStatus::Grading]);

    expect($attempt->fresh()->section_id)->not->toBe($otherSection->id)
        ->and($attempt->fresh()->status)->toBe(AttemptStatus::Grading);
});
