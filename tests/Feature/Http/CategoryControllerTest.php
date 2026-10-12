<?php

use App\Models\Attempt;
use App\Models\Category;
use App\Models\Question;
use App\Models\Section;
use App\Models\User;

// 画面(Blade)はまだないので、ここでは作成・更新・削除の結果だけを確かめる。画面の表示はフェーズ5で確かめる

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('カテゴリを作成すると、詳細へ移り、トーストを出す', function () {
    $response = $this->post('/categories', ['name' => 'Networking']);

    $category = Category::sole();
    $response->assertRedirect(route('categories.show', $category))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'Category created.']);
    expect($category->name)->toBe('Networking');
});

test('名前が空なら作成しない', function () {
    $this->post('/categories', ['name' => ''])->assertSessionHasErrors('name');

    expect(Category::count())->toBe(0);
});

test('カテゴリ名を変更すると、詳細へ移り、トーストを出す', function () {
    $category = Category::factory()->create(['name' => 'Old']);

    $this->put("/categories/{$category->id}", ['name' => 'New'])
        ->assertRedirect(route('categories.show', $category))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'Category saved.']);

    expect($category->fresh()->name)->toBe('New');
});

test('カテゴリを削除すると、配下もすべて消え、一覧へ戻る', function () {
    $section = Section::factory()->create();
    $question = Question::factory()->for($section)->create();
    scoredAttempt($section, [$question->id => 80]);

    $this->delete("/categories/{$section->category_id}")
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'Category deleted.']);

    expect(Category::count())->toBe(0)
        ->and(Section::count())->toBe(0)
        ->and(Question::count())->toBe(0)
        ->and(Attempt::count())->toBe(0);
});

test('存在しないカテゴリは 404', function (string $method) {
    $this->{$method}('/categories/999999', ['name' => 'X'])->assertNotFound();
})->with(['put', 'delete']);

test('ログインしていなければ、作成・変更・削除はできない', function () {
    auth()->logout();
    $category = Category::factory()->create();

    $this->post('/categories', ['name' => 'X'])->assertRedirect(route('login'));
    $this->put("/categories/{$category->id}", ['name' => 'X'])->assertRedirect(route('login'));
    $this->delete("/categories/{$category->id}")->assertRedirect(route('login'));

    expect(Category::count())->toBe(1);
});

test('カテゴリから、セクションをまたいだ挑戦をたどれる', function () {
    // 削除の確認モーダルの件数に使う
    $category = Category::factory()->create();
    Attempt::factory()->count(2)->for(Section::factory()->for($category))->create();
    Attempt::factory()->create(); // 別のカテゴリ

    expect($category->attempts)->toHaveCount(2);
});
