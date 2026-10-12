<?php

use App\Services\Grading\Grade;
use App\Services\Grading\GradeResultParser;
use App\Services\Grading\GradingItem;
use App\Services\Grading\InvalidGradingResultException;

/**
 * @return list<GradingItem>
 */
function parserItems(int $count = 2): array
{
    return array_map(fn (int $i) => new GradingItem($i, "Q{$i}", "A{$i}"), range(0, $count - 1));
}

function rawGrade(int $position, array $overrides = []): array
{
    return [
        'position' => $position,
        'score' => 70,
        'good_points' => '良かった点',
        'weak_points' => '悪かった点',
        'model_answer' => '模範解答',
        ...$overrides,
    ];
}

test('正しい結果を、出題順の Grade にする', function () {
    // AI が順番を入れ替えて返しても、position の順に並べ直す
    $grades = (new GradeResultParser)->parse(
        ['grades' => [rawGrade(1, ['score' => 40]), rawGrade(0, ['score' => 90])]],
        parserItems(),
    );

    expect($grades)->each->toBeInstanceOf(Grade::class)
        ->and(array_column($grades, 'position'))->toBe([0, 1])
        ->and(array_column($grades, 'score'))->toBe([90, 40])
        ->and($grades[0]->goodPoints)->toBe('良かった点')
        ->and($grades[0]->weakPoints)->toBe('悪かった点')
        ->and($grades[0]->modelAnswer)->toBe('模範解答');
});

test('範囲外の点数は、0〜100点に収める', function (int $raw, int $expected) {
    $grades = (new GradeResultParser)->parse(['grades' => [rawGrade(0, ['score' => $raw])]], parserItems(1));

    expect($grades[0]->score)->toBe($expected);
})->with([
    'マイナス' => [-5, 0],
    '100点超え' => [120, 100],
    '境界 0点' => [0, 0],
    '境界 100点' => [100, 100],
]);

test('前後の空白は取り除く', function () {
    $grades = (new GradeResultParser)->parse(
        ['grades' => [rawGrade(0, ['good_points' => "  良かった点\n"])]],
        parserItems(1),
    );

    expect($grades[0]->goodPoints)->toBe('良かった点');
});

test('不正な結果は例外にする', function (array $toolInput) {
    (new GradeResultParser)->parse($toolInput, parserItems());
})->with([
    'grades がない' => [[]],
    'grades が配列ではない' => [['grades' => 'none']],
    '件数が足りない' => [['grades' => [rawGrade(0)]]],
    '件数が多い' => [['grades' => [rawGrade(0), rawGrade(1), rawGrade(2)]]],
    'position が重複' => [['grades' => [rawGrade(0), rawGrade(0)]]],
    'position が抜けている' => [['grades' => [rawGrade(0), rawGrade(2)]]],
    'score が整数ではない' => [['grades' => [rawGrade(0, ['score' => '80']), rawGrade(1)]]],
    'good_points が空' => [['grades' => [rawGrade(0, ['good_points' => '']), rawGrade(1)]]],
    'weak_points が空白だけ' => [['grades' => [rawGrade(0, ['weak_points' => "  \n"]), rawGrade(1)]]],
    'model_answer がない' => [['grades' => [array_diff_key(rawGrade(0), ['model_answer' => '']), rawGrade(1)]]],
])->throws(InvalidGradingResultException::class);

test('position は、採点を頼んだ問題の position と照らし合わせる', function () {
    // 回答の一部だけを採点し直すことはないが、0始まりでない position でも正しく照合できるようにしておく
    $items = [new GradingItem(3, 'Q', 'A'), new GradingItem(5, 'Q', 'A')];

    $grades = (new GradeResultParser)->parse(['grades' => [rawGrade(5), rawGrade(3)]], $items);

    expect(array_column($grades, 'position'))->toBe([3, 5]);
});
