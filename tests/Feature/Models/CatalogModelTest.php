<?php

use App\Models\Category;
use App\Models\Question;
use App\Models\Section;

test('Factory でカテゴリ → セクション → 問題を作れる', function () {
    $question = Question::factory()->create();

    expect($question->section)->toBeInstanceOf(Section::class)
        ->and($question->section->category)->toBeInstanceOf(Category::class);
});

test('カテゴリからセクションと、セクションをまたいだ問題をたどれる', function () {
    // カテゴリ一覧の問題数(screens.md 2.2)や削除の確認モーダルの件数(screens.md 2.4)に使う
    $category = Category::factory()->create();
    $sections = Section::factory()->count(2)->for($category)->create();
    Question::factory()->count(2)->for($sections[0])->create();
    Question::factory()->count(3)->for($sections[1])->create();
    Question::factory()->create(); // 別のカテゴリの問題は数えない

    expect($category->sections)->toHaveCount(2)
        ->and($category->questions)->toHaveCount(5);
});

test('セクションの問題は作成順に並ぶ', function () {
    $section = Section::factory()->create();
    $first = Question::factory()->for($section)->create();
    $second = Question::factory()->for($section)->create();
    // 作成日時が前後しても、番号は作成順(ID 順)のまま(architecture.md 2.7)
    $first->update(['created_at' => now()->addDay()]);

    expect($section->questions->pluck('id')->all())->toBe([$first->id, $second->id]);
});

test('所属するカテゴリ・セクションは一括代入で変えられない', function () {
    // requirements.md 3.2: 所属は作成時に決まったまま変更できない
    $section = Section::factory()->create();
    $question = Question::factory()->for($section)->create();
    $otherSection = Section::factory()->create();

    $section->update(['name' => 'Renamed', 'category_id' => $otherSection->category_id]);
    $question->update(['body' => 'Edited?', 'section_id' => $otherSection->id]);

    expect($section->fresh()->category_id)->not->toBe($otherSection->category_id)
        ->and($section->fresh()->name)->toBe('Renamed')
        ->and($question->fresh()->section_id)->toBe($section->id)
        ->and($question->fresh()->body)->toBe('Edited?');
});
