<?php

use App\Models\Question;
use App\Models\Section;
use App\Models\User;

// 回答フォーム(screens.md 2.9)。下書きの保存・復元、送信前のチェックは JS なので、ブラウザで確かめる

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->section = Section::factory()->create(['name' => 'ネットワーク']);
    [$this->q1, $this->q2, $this->q3] = Question::factory()->count(3)->for($this->section)->sequence(
        ['body' => 'TCPとUDPの違いは?'],
        ['body' => 'DNSとは?'],
        ['body' => 'HTTPSの仕組みは?'],
    )->create();
});

test('全問モードでは、セクションの全問を出題順に並べる', function () {
    $this->get("/sections/{$this->section->id}/answers/create?mode=all")
        ->assertOk()
        ->assertSee('<title>Answer all | Recollect</title>', false)
        ->assertSeeInOrder([
            'ネットワーク', 'Answer all', '/ 3 answered',
            'Grading level',
            'Question 1', 'TCPとUDPの違いは?', 'name="answers[0][question_id]" value="'.$this->q1->id.'"', 'name="answers[0][body]"',
            'Question 2', 'DNSとは?', 'name="answers[1][question_id]" value="'.$this->q2->id.'"',
            'Question 3', 'HTTPSの仕組みは?',
            'Submit (3)',
        ], false)
        ->assertSee('name="mode" value="all"', false)
        ->assertSee('action="'.route('sections.answers.store', $this->section).'"', false);
});

test('採点レベルは Easy / Normal / Strict から選び、Normal を選んだ状態で開く', function () {
    $html = (string) $this->get("/sections/{$this->section->id}/answers/create")->getContent();

    expect($html)->toMatch('/value="normal"\s+class="peer sr-only"\s+checked/')
        ->and($html)->toContain('Easy')->toContain('Strict');
});

test('苦手モードでは、苦手問題だけを出し、前回60点未満だったことを添える', function () {
    scoredAttempt($this->section, [$this->q1->id => 40, $this->q2->id => 90, $this->q3->id => 50]);

    $this->get("/sections/{$this->section->id}/answers/create?mode=weak")
        ->assertOk()
        ->assertSee('<title>Retry weak questions | Recollect</title>', false)
        ->assertSee('2 questions scored under 60 last time.')
        ->assertSeeInOrder(['TCPとUDPの違いは?', 'HTTPSの仕組みは?', 'Submit (2)'])
        ->assertDontSee('DNSとは?')
        ->assertSee('name="mode" value="weak"', false);
});

test('下書きは、セクションと種別ごとのキーで保存する', function () {
    // architecture.md 7章
    $this->get("/sections/{$this->section->id}/answers/create?mode=all")
        ->assertSee("storageKey: 'quiz:draft:section:{$this->section->id}:all'", false)
        ->assertSee('Discard draft')
        ->assertSee('Draft saved');
});

test('回答の入力欄は、文字数の上限(5000文字)と問題 ID を持つ', function () {
    $this->get("/sections/{$this->section->id}/answers/create")
        ->assertSee('maxlength="5000"', false)
        ->assertSee('data-question-id="'.$this->q1->id.'"', false);
});

test('入力エラーで戻ったら、書いた回答と採点レベルを残し、未入力の問題にエラーを出す', function () {
    $response = $this->followingRedirects()
        ->from("/sections/{$this->section->id}/answers/create?mode=all")
        ->post("/sections/{$this->section->id}/answers", [
            'grading_level' => 'strict',
            'mode' => 'all',
            'answers' => [
                ['question_id' => $this->q1->id, 'body' => '信頼性が違う'],
                ['question_id' => $this->q2->id, 'body' => ''],
                ['question_id' => $this->q3->id, 'body' => '暗号化する'],
            ],
        ])
        ->assertSee('>信頼性が違う</textarea>', false)
        ->assertSee('Please answer all questions.');

    // Strict が選ばれた状態で戻り、既定の Normal は選ばれていない
    $checked = '/value="%s"\s+class="peer sr-only"\s+checked/';
    expect($response->getContent())
        ->toMatch(sprintf($checked, 'strict'))
        ->not->toMatch(sprintf($checked, 'normal'));
});

test('問題が追加・削除されて組み合わせが合わなければ、フォームの上にエラーを出す', function () {
    $this->followingRedirects()
        ->from("/sections/{$this->section->id}/answers/create?mode=all")
        ->post("/sections/{$this->section->id}/answers", [
            'grading_level' => 'normal',
            'mode' => 'all',
            'answers' => [['question_id' => $this->q1->id, 'body' => 'a']],
        ])
        ->assertSeeInOrder(['role="alert"', 'The questions have changed. Please reload the page.'], false);
});
