<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// モデルはまだないので(implementation-plan.md 2-3 で作る)、クエリビルダで直接確かめる。

/**
 * カテゴリ → セクション → 問題2問 → 挑戦1回 を作り、ID をまとめて返す。
 *
 * @return array{section: int, questions: array<int, int>, attempt: int}
 */
function attemptSchemaSetUp(): array
{
    $categoryId = DB::table('categories')->insertGetId(['name' => 'Networking']);
    $sectionId = DB::table('sections')->insertGetId(['category_id' => $categoryId, 'name' => 'TCP/IP']);
    $questionIds = [
        DB::table('questions')->insertGetId(['section_id' => $sectionId, 'body' => 'Q1']),
        DB::table('questions')->insertGetId(['section_id' => $sectionId, 'body' => 'Q2']),
    ];

    return ['section' => $sectionId, 'questions' => $questionIds, 'attempt' => attemptSchemaInsertAttempt($sectionId)];
}

function attemptSchemaInsertAttempt(int $sectionId, array $overrides = []): int
{
    return DB::table('attempts')->insertGetId([
        'section_id' => $sectionId,
        'grading_level' => 'normal',
        'mode' => 'all',
        ...$overrides,
    ]);
}

function attemptSchemaInsertAnswer(int $attemptId, int $questionId, int $position): int
{
    return DB::table('answers')->insertGetId([
        'attempt_id' => $attemptId,
        'question_id' => $questionId,
        'position' => $position,
        // DB を直接操作するので、モデルが自動で入れる問題文の写しも自分で入れる
        'question_body' => 'question',
        'body' => 'answer',
    ]);
}

function attemptSchemaInsertScore(int $answerId, int $score = 80): int
{
    return DB::table('scores')->insertGetId([
        'answer_id' => $answerId,
        'score' => $score,
        'good_points' => '良かった点',
        'weak_points' => '悪かった点',
        'model_answer' => '模範解答',
    ]);
}

test('挑戦・回答・採点結果のテーブルが設計どおりの列を持つ', function () {
    // architecture.md 2.4〜2.6。user_id は持たない(architecture.md 6章)
    expect(Schema::getColumnListing('attempts'))->toEqualCanonicalizing([
        'id', 'section_id', 'grading_level', 'mode', 'status',
        'model', 'input_tokens', 'output_tokens', 'web_search_requests',
        'error_message', 'graded_at', 'created_at', 'updated_at',
    ])
        ->and(Schema::getColumnListing('answers'))->toEqualCanonicalizing([
            'id', 'question_id', 'attempt_id', 'position', 'question_body', 'body', 'created_at', 'updated_at',
        ])
        ->and(Schema::getColumnListing('scores'))->toEqualCanonicalizing([
            'id', 'answer_id', 'score', 'good_points', 'weak_points', 'model_answer', 'created_at', 'updated_at',
        ]);
});

test('日時の列はタイムゾーン付きになっている', function () {
    // architecture.md 2.7
    expect(Schema::getColumnType('attempts', 'graded_at'))->toBe('timestamptz');

    foreach (['attempts', 'answers', 'scores'] as $table) {
        expect(Schema::getColumnType($table, 'created_at'))->toBe('timestamptz');
    }
});

test('挑戦の状態は、指定しなければ pending になる', function () {
    $ids = attemptSchemaSetUp();

    expect(DB::table('attempts')->where('id', $ids['attempt'])->value('status'))->toBe('pending');
});

test('採点レベル・種別・状態には決められた値しか入らない', function (string $column, string $value) {
    $ids = attemptSchemaSetUp();

    attemptSchemaInsertAttempt($ids['section'], [$column => $value]);
})->with([
    'v2 の hard は使わない' => ['grading_level', 'hard'],
    '種別' => ['mode', 'random'],
    '状態' => ['status', 'done'],
])->throws(QueryException::class);

test('点数は 0〜100 点の範囲だけ保存できる', function (int $score, bool $valid) {
    $ids = attemptSchemaSetUp();
    $answerId = attemptSchemaInsertAnswer($ids['attempt'], $ids['questions'][0], 0);

    if (! $valid) {
        $this->expectException(QueryException::class);
    }

    attemptSchemaInsertScore($answerId, $score);

    expect(DB::table('scores')->count())->toBe(1);
})->with([
    '0点' => [0, true],
    '100点' => [100, true],
    'マイナス' => [-1, false],
    '101点' => [101, false],
]);

test('1つの挑戦に、同じ問題への回答は1つしか入らない', function () {
    // architecture.md 2.5
    $ids = attemptSchemaSetUp();
    attemptSchemaInsertAnswer($ids['attempt'], $ids['questions'][0], 0);

    attemptSchemaInsertAnswer($ids['attempt'], $ids['questions'][0], 1);
})->throws(QueryException::class);

test('1つの挑戦に、同じ順番の回答は1つしか入らない', function () {
    $ids = attemptSchemaSetUp();
    attemptSchemaInsertAnswer($ids['attempt'], $ids['questions'][0], 0);

    attemptSchemaInsertAnswer($ids['attempt'], $ids['questions'][1], 0);
})->throws(QueryException::class);

test('1つの回答に、採点結果は1つしか入らない', function () {
    $ids = attemptSchemaSetUp();
    $answerId = attemptSchemaInsertAnswer($ids['attempt'], $ids['questions'][0], 0);
    attemptSchemaInsertScore($answerId);

    attemptSchemaInsertScore($answerId);
})->throws(QueryException::class);

test('挑戦を削除すると、回答と採点結果も削除される', function () {
    $ids = attemptSchemaSetUp();
    attemptSchemaInsertScore(attemptSchemaInsertAnswer($ids['attempt'], $ids['questions'][0], 0));

    DB::table('attempts')->where('id', $ids['attempt'])->delete();

    expect(DB::table('answers')->count())->toBe(0)
        ->and(DB::table('scores')->count())->toBe(0);
});

test('問題を削除しても、回答と採点結果は残り、回答の問題への参照だけが空になる', function () {
    // architecture.md 2.5 / 4.6(2026-10-13 決定): 履歴を残す
    $ids = attemptSchemaSetUp();
    attemptSchemaInsertScore(attemptSchemaInsertAnswer($ids['attempt'], $ids['questions'][0], 0));
    attemptSchemaInsertScore(attemptSchemaInsertAnswer($ids['attempt'], $ids['questions'][1], 1));

    DB::table('questions')->where('id', $ids['questions'][0])->delete();

    expect(DB::table('answers')->orderBy('position')->pluck('question_id')->all())->toBe([null, $ids['questions'][1]])
        ->and(DB::table('scores')->count())->toBe(2)
        ->and(DB::table('attempts')->count())->toBe(1);
});

test('問題文の写しは必須', function () {
    $ids = attemptSchemaSetUp();

    DB::table('answers')->insert(['attempt_id' => $ids['attempt'], 'question_id' => $ids['questions'][0], 'position' => 0, 'body' => 'a']);
})->throws(QueryException::class);

test('セクションを削除すると、その挑戦も削除される', function () {
    $ids = attemptSchemaSetUp();

    DB::table('sections')->where('id', $ids['section'])->delete();

    expect(DB::table('attempts')->count())->toBe(0);
});

test('集計で使うインデックスがある', function () {
    // architecture.md 2.4 / 2.5
    $indexedColumns = fn (string $table) => collect(Schema::getIndexes($table))->pluck('columns')->all();

    expect($indexedColumns('attempts'))->toContain(['section_id', 'id'])
        ->and($indexedColumns('answers'))->toContain(['question_id', 'attempt_id']);
});
