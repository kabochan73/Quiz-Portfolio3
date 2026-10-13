<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;

// 履歴一覧(screens.md 2.11)

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->section = Section::factory()->create(['name' => 'ネットワーク']);
    [$this->q1, $this->q2] = Question::factory()->count(2)->for($this->section)->create();
});

test('挑戦を新しい順に並べ、種別・採点レベル・問題数・平均点を出す', function () {
    $old = scoredAttempt($this->section, [$this->q1->id => 70, $this->q2->id => 74]);
    $new = scoredAttempt($this->section, [$this->q1->id => 40]);
    $new->update(['mode' => AttemptMode::Weak, 'grading_level' => GradingLevel::Strict]);

    $this->get("/sections/{$this->section->id}/history")
        ->assertOk()
        ->assertSee('<title>History | Recollect</title>', false)
        ->assertSeeInOrder(['ネットワーク', 'History'])
        // 2026-10-13 決定: 見出しの下に件数は出さない
        ->assertDontSee('2 attempts')
        ->assertSeeInOrder([
            route('sections.history.show', [$this->section, $new]), '>Weak</span>', 'Strict', '1 question', '>40</span>',
            route('sections.history.show', [$this->section, $old]), '>All</span>', 'Normal', '2 questions', '>72</span>',
        ], false);
});

test('採点が終わっていない挑戦は、平均点の代わりに状態を出す', function (AttemptStatus $status, string $label) {
    $attempt = Attempt::factory()->for($this->section)->create(['status' => $status]);

    $this->get("/sections/{$this->section->id}/history")
        ->assertSee(route('sections.history.show', [$this->section, $attempt]), false)
        ->assertSee(">{$label}</span>", false);
})->with([
    [AttemptStatus::Pending, 'Waiting'],
    [AttemptStatus::Grading, 'Grading'],
    [AttemptStatus::Failed, 'Failed'],
]);

test('20件ずつ表示し、21件以上なら Newer / Older でページを切り替える', function () {
    Attempt::factory()->count(21)->for($this->section)->completed()->create();
    $oldest = Attempt::query()->orderBy('id')->first();

    $first = $this->get("/sections/{$this->section->id}/history");
    $first->assertSee('Older')
        ->assertDontSee(route('sections.history.show', [$this->section, $oldest]), false);
    expect(substr_count($first->getContent(), route('sections.history.index', $this->section).'/'))->toBe(20);

    $this->get("/sections/{$this->section->id}/history?page=2")
        ->assertSee(route('sections.history.show', [$this->section, $oldest]), false)
        ->assertSee('Newer');
});

test('20件以下なら、ページ送りは出さない', function () {
    scoredAttempt($this->section, [$this->q1->id => 80]);

    $this->get("/sections/{$this->section->id}/history")
        ->assertDontSee('Older')
        ->assertDontSee('Newer');
});

test('別のセクションの挑戦は出さない', function () {
    $other = Attempt::factory()->completed()->create();

    $this->get("/sections/{$this->section->id}/history")
        ->assertDontSee(route('sections.history.show', [$other->section, $other]), false);
});

test('まだ挑戦していなければ、空の状態から回答へ進める', function () {
    $this->get("/sections/{$this->section->id}/history")
        ->assertSee('No attempts yet.')
        ->assertSee(route('sections.answers.create', [$this->section, 'mode' => 'all']), false);
});
