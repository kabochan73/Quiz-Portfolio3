<?php

use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Models\Category;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Carbon;

// 問題の画面(screens.md 2.7 / 2.8)

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

test('編集画面は、今の問題文を入れた状態で開く', function () {
    Question::factory()->for($this->section)->create();
    $question = Question::factory()->for($this->section)->create(['body' => "DNSとは?\n説明してください。"]);

    $this->get("/questions/{$question->id}/edit")
        ->assertOk()
        ->assertSeeInOrder(['ネットワーク', 'Question 2', 'Edit question'])
        ->assertSee(">DNSとは?\n説明してください。</textarea>", false)
        ->assertSee('name="_method" value="PUT"', false)
        ->assertSee('Save');
});

test('詳細の見出しは、セクションの中で何問目か', function () {
    Question::factory()->count(2)->for($this->section)->create();
    $third = Question::factory()->for($this->section)->create(['body' => 'HTTPSの仕組みは?']);

    $this->get("/questions/{$third->id}")
        ->assertOk()
        ->assertSee('<title>Question 3 | Recollect</title>', false)
        ->assertSeeInOrder(['ネットワーク', 'Question 3', 'HTTPSの仕組みは?']);
});

test('問題文は改行をそのまま表示する', function () {
    $question = Question::factory()->for($this->section)->create(['body' => "1行目\n2行目"]);

    $this->get("/questions/{$question->id}")
        ->assertSee("1行目\n2行目", false)
        ->assertSee('whitespace-pre-wrap', false);
});

test('最近の点数を新しい順に出し、各行からその回の結果画面へ移れる', function () {
    $question = Question::factory()->for($this->section)->create();
    $old = scoredAttempt($this->section, [$question->id => 45]);
    $old->forceFill(['created_at' => Carbon::parse('2026-10-01 01:00', 'UTC')])->save();
    $new = scoredAttempt($this->section, [$question->id => 82]);
    $new->forceFill(['created_at' => Carbon::parse('2026-10-07 01:00', 'UTC'), 'grading_level' => GradingLevel::Strict])->save();

    $this->get("/questions/{$question->id}")
        ->assertSeeInOrder([
            'Recent scores',
            route('sections.history.show', [$this->section, $new]), 'Oct 7', '>82</span>', 'Strict',
            route('sections.history.show', [$this->section, $old]), 'Oct 1', '>45</span>', 'Normal',
        ], false);
});

test('最近の点数は、回答を残す件数(10回)までで、採点が終わっていない回は出さない', function () {
    // 2026-10-13: 回答は直近10件しか残らないので、表示もそれにそろえる
    $question = Question::factory()->for($this->section)->create();
    foreach (range(1, 11) as $score) {
        scoredAttempt($this->section, [$question->id => $score]);
    }
    scoredAttempt($this->section, [$question->id => 99])->update(['status' => AttemptStatus::Failed]);

    $html = (string) $this->get("/questions/{$question->id}")->getContent();

    // 1回目(1点)は11回目なので出ない
    expect(substr_count($html, route('sections.history.index', $this->section).'/'))->toBe(10)
        ->and($html)->not->toContain('>99</span>')
        ->not->toContain('>1</span>')
        ->toContain('>2</span>');
});

test('まだ回答していなければ、空の状態を出す', function () {
    $question = Question::factory()->for($this->section)->create();

    $this->get("/questions/{$question->id}")->assertSee('Not answered yet.');
});

test('削除の確認モーダルに、一緒に消える回答の件数を出す', function () {
    $question = Question::factory()->for($this->section)->create();
    scoredAttempt($this->section, [$question->id => 80]);
    scoredAttempt($this->section, [$question->id => 90]);

    $this->get("/questions/{$question->id}")
        ->assertSee('Delete this question?')
        ->assertSee('This will also delete 2 answers.')
        ->assertSee(route('questions.edit', $question), false);
});
