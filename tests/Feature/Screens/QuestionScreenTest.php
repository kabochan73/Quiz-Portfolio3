<?php

use App\Models\Category;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;

// 問題の画面(screens.md 2.7)。問題の詳細の画面は作らない

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->section = Section::factory()
        ->for(Category::factory()->state(['name' => '基本情報']))
        ->create(['name' => 'ネットワーク']);
});

test('追加画面は、問題文の入力欄と文字数を出し、Cancel でセクション詳細へ戻る', function () {
    $this->get("/sections/{$this->section->id}/questions/create")
        ->assertOk()
        ->assertSee('<title>Add question | Recollect</title>', false)
        ->assertSeeInOrder(['Home', '基本情報', 'ネットワーク', 'Add question', 'Question', '0</span> / 2000', 'Cancel', 'Add'], false)
        ->assertSee('action="'.route('sections.questions.store', $this->section).'"', false)
        ->assertSee('href="'.route('sections.show', $this->section).'"', false);
});

test('上限で追加できなかったら、問題文の下にエラーを出す', function () {
    Question::factory()->count(10)->for($this->section)->create();

    $this->followingRedirects()
        ->from("/sections/{$this->section->id}/questions/create")
        ->post("/sections/{$this->section->id}/questions", ['body' => 'Q11'])
        ->assertSee('This section has the maximum of 10 questions.')
        // 書いた問題文は残す
        ->assertSee('>Q11</textarea>', false);
});

test('編集画面は、今の問題文を入れた状態で開き、Cancel でセクション詳細へ戻る', function () {
    Question::factory()->for($this->section)->create();
    $question = Question::factory()->for($this->section)->create(['body' => "DNSとは?\n説明してください。"]);

    $this->get("/questions/{$question->id}/edit")
        ->assertOk()
        ->assertSeeInOrder(['ネットワーク', 'Edit question 2', 'Edit question'])
        ->assertSee('href="'.route('sections.show', $this->section).'"', false)
        ->assertSee(">DNSとは?\n説明してください。</textarea>", false)
        ->assertSee('name="_method" value="PUT"', false)
        ->assertSee('Save');
});

test('問題の詳細の画面はない。問題の一覧・編集・削除はセクション詳細から行う', function () {
    // 2026-10-13 決定
    $question = Question::factory()->for($this->section)->create();

    $this->get("/questions/{$question->id}")->assertStatus(405);
});
