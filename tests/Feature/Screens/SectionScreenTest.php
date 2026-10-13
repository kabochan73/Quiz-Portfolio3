<?php

use App\Models\Category;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;

// セクションの画面(screens.md 2.5 / 2.6)

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->category = Category::factory()->create(['name' => '基本情報']);
    $this->section = Section::factory()->for($this->category)->create(['name' => 'ネットワーク']);
});

test('作成画面は、カテゴリの下に作るフォームで、Cancel でカテゴリ詳細へ戻る', function () {
    $this->get("/categories/{$this->category->id}/sections/create")
        ->assertOk()
        ->assertSee('<title>New section | Recollect</title>', false)
        ->assertSeeInOrder(['Home', '基本情報', 'New section', 'Name', 'Cancel', 'Create'])
        ->assertSee('action="'.route('categories.sections.store', $this->category).'"', false)
        ->assertSee('href="'.route('categories.show', $this->category).'"', false);
});

test('編集画面は、今の名前を入れた状態で開く', function () {
    $this->get("/sections/{$this->section->id}/edit")
        ->assertOk()
        ->assertSeeInOrder(['Home', '基本情報', 'ネットワーク', 'Edit'])
        ->assertSee('value="ネットワーク"', false)
        ->assertSee('name="_method" value="PUT"', false);
});

test('詳細の見出しの下に、問題数は出さない', function () {
    // 2026-10-13 決定
    Question::factory()->count(3)->for($this->section)->create();

    $this->get("/sections/{$this->section->id}")
        ->assertOk()
        ->assertSee('<title>ネットワーク | Recollect</title>', false)
        ->assertDontSee('3 / 10');
});

test('回答・苦手の再挑戦・履歴のボタンを出し、苦手の再挑戦には問題数を添える', function () {
    [$q1, $q2, $q3] = Question::factory()->count(3)->for($this->section)->create();
    scoredAttempt($this->section, [$q1->id => 40, $q2->id => 90, $q3->id => 50]);

    $this->get("/sections/{$this->section->id}")
        ->assertSeeInOrder([
            'href="'.route('sections.answers.create', [$this->section, 'mode' => 'all']).'"', 'Answer all',
            'href="'.route('sections.answers.create', [$this->section, 'mode' => 'weak']).'"', 'Retry weak (2)',
            'href="'.route('sections.history.index', $this->section).'"', 'History',
        ], false)
        // 2026-10-13 決定: Answer all には問題数を添えない
        ->assertDontSee('Answer all (', false)
        ->assertDontSee('No weak questions.');
});

test('苦手問題が0問なら、苦手の再挑戦は押せない。説明の文は添えない', function () {
    // 2026-10-13 決定: 説明の文(No weak questions.)は出さない
    Question::factory()->for($this->section)->create();

    $this->get("/sections/{$this->section->id}")
        ->assertSee('Retry weak (0)')
        ->assertDontSee('No weak questions.')
        ->assertDontSee('href="'.route('sections.answers.create', [$this->section, 'mode' => 'weak']).'"', false);
});

test('問題を並べ、問題文の先頭40文字を出す。点数は出さない', function () {
    $long = Question::factory()->for($this->section)->create(['body' => str_repeat('あ', 50)]);
    $short = Question::factory()->for($this->section)->create(['body' => 'DNSとは?']);
    scoredAttempt($this->section, [$long->id => 85]);

    $this->get("/sections/{$this->section->id}")
        ->assertSeeInOrder([
            'href="'.route('questions.show', $long).'"', '1', str_repeat('あ', 40).'…',
            'href="'.route('questions.show', $short).'"', '2', 'DNSとは?',
        ], false)
        // 2026-10-13 決定: 点数は履歴だけで見る
        ->assertDontSee('>85</span>', false)
        ->assertSee(route('questions.edit', $long), false)
        ->assertDontSee(str_repeat('あ', 41), false);
});

test('問題が10問に達したら、追加できないことを示す', function () {
    Question::factory()->count(10)->for($this->section)->create();

    $this->get("/sections/{$this->section->id}")
        ->assertSee('This section has the maximum of 10 questions.')
        ->assertDontSee('href="'.route('sections.questions.create', $this->section).'"', false);
});

test('問題が0問なら、操作のエリアを出さず、空の状態から問題の追加へ進める', function () {
    $this->get("/sections/{$this->section->id}")
        ->assertSee('No questions yet.')
        ->assertSee(route('sections.questions.create', $this->section), false)
        ->assertDontSee('Answer all')
        ->assertDontSee('History');
});

test('セクションと各問題の削除の確認モーダルに、一緒に消えるものの件数を出す', function () {
    [$q1, $q2] = Question::factory()->count(2)->for($this->section)->create();
    scoredAttempt($this->section, [$q1->id => 80, $q2->id => 80]);
    scoredAttempt($this->section, [$q1->id => 90]);

    $this->get("/sections/{$this->section->id}")
        ->assertSee('Delete this section?')
        ->assertSee('This will also delete 2 questions and 2 attempts.')
        ->assertSeeInOrder([
            'delete-question-'.$q1->id, 'Delete this question?', 'This will also delete 2 answers.',
            'delete-question-'.$q2->id, 'Delete this question?', 'This will also delete 1 answer.',
        ], false);
});

test('セクション詳細では、サイドバーの所属するカテゴリを強調する', function () {
    $this->get("/sections/{$this->section->id}")
        ->assertSeeInOrder([route('categories.show', $this->category).'"', 'bg-neutral-100 font-semibold', 'aria-current="page"'], false);
});
