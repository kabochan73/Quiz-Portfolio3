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

test('URL のカテゴリにセクションを作成し、詳細へ移る', function () {
    $category = Category::factory()->create();

    $response = $this->post("/categories/{$category->id}/sections", ['name' => 'TCP/IP']);

    $section = Section::sole();
    $response->assertRedirect(route('sections.show', $section))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'Section created.']);
    expect($section->category_id)->toBe($category->id)
        ->and($section->name)->toBe('TCP/IP');
});

test('名前が空なら作成しない', function () {
    $category = Category::factory()->create();

    $this->post("/categories/{$category->id}/sections", ['name' => ''])->assertSessionHasErrors('name');

    expect(Section::count())->toBe(0);
});

test('存在しないカテゴリには作成できない', function () {
    $this->post('/categories/999999/sections', ['name' => 'X'])->assertNotFound();
});

test('セクション名を変更できる。所属するカテゴリは変えられない', function () {
    // requirements.md 3.2
    $section = Section::factory()->create();
    $other = Category::factory()->create();

    $this->put("/sections/{$section->id}", ['name' => 'Renamed', 'category_id' => $other->id])
        ->assertRedirect(route('sections.show', $section))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'Section saved.']);

    expect($section->fresh()->name)->toBe('Renamed')
        ->and($section->fresh()->category_id)->not->toBe($other->id);
});

test('セクションを削除すると、問題と挑戦も消え、カテゴリ詳細へ戻る', function () {
    $section = Section::factory()->create();
    $question = Question::factory()->for($section)->create();
    scoredAttempt($section, [$question->id => 80]);
    $sibling = Section::factory()->for($section->category)->create();

    $this->delete("/sections/{$section->id}")
        ->assertRedirect(route('categories.show', $section->category_id))
        ->assertSessionHas('toast', ['type' => 'success', 'message' => 'Section deleted.']);

    expect(Section::pluck('id')->all())->toBe([$sibling->id])
        ->and(Question::count())->toBe(0)
        ->and(Attempt::count())->toBe(0);
});

test('存在しないセクションは 404', function (string $method) {
    $this->{$method}('/sections/999999', ['name' => 'X'])->assertNotFound();
})->with(['put', 'delete']);

test('ログインしていなければ、作成・変更・削除はできない', function () {
    auth()->logout();
    $section = Section::factory()->create();

    $this->post("/categories/{$section->category_id}/sections", ['name' => 'X'])->assertRedirect(route('login'));
    $this->put("/sections/{$section->id}", ['name' => 'X'])->assertRedirect(route('login'));
    $this->delete("/sections/{$section->id}")->assertRedirect(route('login'));

    expect(Section::count())->toBe(1);
});
