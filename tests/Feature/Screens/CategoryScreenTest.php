<?php

use App\Models\Category;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;

// カテゴリの画面(screens.md 2.3 / 2.4)。カテゴリの一覧の画面は作らない

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('カテゴリの一覧の画面はない。一覧はサイドバーに任せる', function () {
    // 2026-10-13 決定
    $this->get('/categories')->assertStatus(405);
});

test('作成画面に、名前の入力欄と Create ボタンを出す', function () {
    $this->get('/categories/create')
        ->assertOk()
        ->assertSee('<title>New category | recollect</title>', false)
        ->assertSeeInOrder(['Home', 'New category', 'Name', 'Cancel', 'Create'])
        ->assertSee('action="'.route('categories.store').'"', false)
        ->assertSee('max-w-2xl', false);
});

test('編集画面は、今の名前を入れた状態で開き、Save ボタンで PUT を送る', function () {
    $category = Category::factory()->create(['name' => '基本情報']);

    $this->get("/categories/{$category->id}/edit")
        ->assertOk()
        ->assertSee('value="基本情報"', false)
        ->assertSee('action="'.route('categories.update', $category).'"', false)
        ->assertSee('name="_method" value="PUT"', false)
        ->assertSee('Save');
});

test('入力エラーで戻ったら、名前の下にエラーを出す', function () {
    $this->followingRedirects()
        ->from('/categories/create')
        ->post('/categories', ['name' => ''])
        ->assertSee('The name field is required.');
});

test('カテゴリ詳細に、セクションを行で並べ、問題数を出す。平均点は出さない', function () {
    $category = Category::factory()->create(['name' => '基本情報']);
    $network = Section::factory()->for($category)->create(['name' => 'ネットワーク']);
    [$q1, $q2] = Question::factory()->count(2)->for($network)->create();
    scoredAttempt($network, [$q1->id => 70, $q2->id => 75]);
    $security = Section::factory()->for($category)->create(['name' => 'セキュリティ']);

    $this->get("/categories/{$category->id}")
        ->assertOk()
        ->assertSee('<title>基本情報 | recollect</title>', false)
        ->assertSeeInOrder([
            'Sections', 'Add section',
            'href="'.route('sections.show', $network).'"', 'ネットワーク', '2 questions',
            'href="'.route('sections.show', $security).'"', 'セキュリティ', '0 questions',
        ], false)
        // 2026-10-13 決定: セクションの行に平均点は出さない
        ->assertDontSee('Avg');
});

test('カテゴリ詳細の見出しの下に、セクションの数は出さない', function () {
    // 2026-10-13 決定
    $category = Category::factory()->create(['name' => '基本情報']);
    Section::factory()->count(3)->for($category)->create();

    // 確認モーダルの「3 sections」以外には出ない
    expect(substr_count((string) $this->get("/categories/{$category->id}")->getContent(), '3 sections'))->toBe(1);
});

test('セクションが0件なら、空の状態からセクションの追加へ進める', function () {
    $category = Category::factory()->create();

    $this->get("/categories/{$category->id}")
        ->assertSee('No sections yet.')
        ->assertSee(route('categories.sections.create', $category), false);
});

test('削除の確認モーダルに、一緒に消えるものの件数を出す', function () {
    // requirements.md 5.3、screens.md 4.2
    $category = Category::factory()->create();
    $section = Section::factory()->for($category)->create();
    $questions = Question::factory()->count(3)->for($section)->create();
    scoredAttempt($section, $questions->mapWithKeys(fn ($q) => [$q->id => 80])->all());
    Section::factory()->for($category)->create();

    $this->get("/categories/{$category->id}")
        ->assertSee('Delete this category?')
        ->assertSee('This will also delete 2 sections, 3 questions, and 1 attempt.')
        ->assertSee('action="'.route('categories.destroy', $category).'"', false);
});

test('カテゴリ詳細のメニューから、編集と削除ができる', function () {
    $category = Category::factory()->create();

    $this->get("/categories/{$category->id}")
        ->assertSee(route('categories.edit', $category), false)
        ->assertSee("\$dispatch('open-modal', 'delete-category')", false);
});

test('カテゴリ詳細では、サイドバーのそのカテゴリを強調する', function () {
    $category = Category::factory()->create(['name' => '基本情報']);

    $this->get("/categories/{$category->id}")
        ->assertSeeInOrder([route('categories.show', $category).'"', 'bg-neutral-100 font-semibold', 'aria-current="page"'], false);
});

test('ログインしていなければ、カテゴリの画面は開けない', function () {
    auth()->logout();

    $this->get('/categories/create')->assertRedirect(route('login'));
});
