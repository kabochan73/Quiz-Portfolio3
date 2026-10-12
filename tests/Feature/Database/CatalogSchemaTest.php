<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// モデルはまだないので(implementation-plan.md 2-3 で作る)、クエリビルダで直接確かめる。

function catalogInsertCategory(): int
{
    return DB::table('categories')->insertGetId(['name' => 'Networking']);
}

function catalogInsertSection(int $categoryId): int
{
    return DB::table('sections')->insertGetId(['category_id' => $categoryId, 'name' => 'TCP/IP']);
}

function catalogInsertQuestion(int $sectionId): int
{
    return DB::table('questions')->insertGetId(['section_id' => $sectionId, 'body' => 'TCP と UDP の違いを説明してください。']);
}

test('カテゴリ・セクション・問題のテーブルが設計どおりの列を持つ', function () {
    // architecture.md 2.1〜2.3。user_id は持たない(architecture.md 6章)
    expect(Schema::getColumnListing('categories'))->toEqualCanonicalizing(['id', 'name', 'created_at', 'updated_at'])
        ->and(Schema::getColumnListing('sections'))->toEqualCanonicalizing(['id', 'category_id', 'name', 'created_at', 'updated_at'])
        ->and(Schema::getColumnListing('questions'))->toEqualCanonicalizing(['id', 'section_id', 'body', 'created_at', 'updated_at']);
});

test('日時の列はタイムゾーン付きになっている', function () {
    // architecture.md 2.7
    foreach (['users', 'categories', 'sections', 'questions'] as $table) {
        expect(Schema::getColumnType($table, 'created_at'))->toBe('timestamptz');
    }
});

test('カテゴリを削除すると、配下のセクションと問題も削除される', function () {
    // requirements.md 3.2
    $categoryId = catalogInsertCategory();
    $sectionId = catalogInsertSection($categoryId);
    catalogInsertQuestion($sectionId);

    DB::table('categories')->where('id', $categoryId)->delete();

    expect(DB::table('sections')->count())->toBe(0)
        ->and(DB::table('questions')->count())->toBe(0);
});

test('セクションを削除すると、配下の問題も削除される', function () {
    $sectionId = catalogInsertSection(catalogInsertCategory());
    catalogInsertQuestion($sectionId);

    DB::table('sections')->where('id', $sectionId)->delete();

    expect(DB::table('questions')->count())->toBe(0);
});

test('名前は100文字までしか保存できない', function () {
    // architecture.md 2.1: varchar(100)
    DB::table('categories')->insert(['name' => str_repeat('a', 101)]);
})->throws(QueryException::class);

test('外部キーの列にインデックスがある', function () {
    // PostgreSQL は外部キーに自動でインデックスを作らないため、マイグレーションで付けている
    $indexedColumns = fn (string $table) => collect(Schema::getIndexes($table))->pluck('columns')->all();

    expect($indexedColumns('sections'))->toContain(['category_id'])
        ->and($indexedColumns('questions'))->toContain(['section_id']);
});
