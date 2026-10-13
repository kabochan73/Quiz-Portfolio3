<?php

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;

// 画面(Blade)はまだないので、ここでは作成・更新・削除の結果だけを確かめる。画面の表示はフェーズ5で確かめる

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->section = Section::factory()->create();
});

test('URL のセクションに問題を追加し、続けて追加できるようセクション詳細へ戻る', function () {
    $this->post("/sections/{$this->section->id}/questions", ['body' => 'TCPとUDPの違いは?'])
        ->assertRedirect(route('sections.show', $this->section))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'Question created.']);

    expect(Question::sole()->only('section_id', 'body'))
        ->toBe(['section_id' => $this->section->id, 'body' => 'TCPとUDPの違いは?']);
});

test('問題文が空なら追加しない', function () {
    $this->post("/sections/{$this->section->id}/questions", ['body' => ''])->assertSessionHasErrors('body');

    expect(Question::count())->toBe(0);
});

test('上限の10問に達していたら、問題文の欄にエラーを出して追加しない', function () {
    // requirements.md 3.2、screens.md 4.3
    Question::factory()->count(10)->for($this->section)->create();

    $this->post("/sections/{$this->section->id}/questions", ['body' => 'Q11'])
        ->assertSessionHasErrors(['body' => 'This section has the maximum of 10 questions.']);

    expect($this->section->questions()->count())->toBe(10);
});

test('問題文を変更できる。所属するセクションは変えられない', function () {
    $question = Question::factory()->for($this->section)->create();
    $other = Section::factory()->create();

    $this->put("/questions/{$question->id}", ['body' => 'Edited?', 'section_id' => $other->id])
        ->assertRedirect(route('sections.show', $this->section))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'Question saved.']);

    expect($question->fresh()->only('section_id', 'body'))
        ->toBe(['section_id' => $this->section->id, 'body' => 'Edited?']);
});

test('問題を削除しても履歴は残り、セクション詳細へ戻る', function () {
    // architecture.md 4.6(2026-10-13 決定)
    [$q1, $q2] = Question::factory()->count(2)->for($this->section)->create();
    $onlyQ1 = scoredAttempt($this->section, [$q1->id => 50]);
    $both = scoredAttempt($this->section, [$q1->id => 50, $q2->id => 80]);

    $this->delete("/questions/{$q1->id}")
        ->assertRedirect(route('sections.show', $this->section))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'Question deleted.']);

    expect(Question::pluck('id')->all())->toBe([$q2->id])
        ->and(Attempt::orderBy('id')->pluck('id')->all())->toBe([$onlyQ1->id, $both->id]);
});

test('存在しないセクション・問題は 404', function () {
    $this->post('/sections/999999/questions', ['body' => 'X'])->assertNotFound();
    $this->put('/questions/999999', ['body' => 'X'])->assertNotFound();
    $this->delete('/questions/999999')->assertNotFound();
});

test('ログインしていなければ、作成・変更・削除はできない', function () {
    auth()->logout();
    $question = Question::factory()->for($this->section)->create();

    $this->post("/sections/{$this->section->id}/questions", ['body' => 'X'])->assertRedirect(route('login'));
    $this->put("/questions/{$question->id}", ['body' => 'X'])->assertRedirect(route('login'));
    $this->delete("/questions/{$question->id}")->assertRedirect(route('login'));

    expect(Question::count())->toBe(1);
});
