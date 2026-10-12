<?php

use App\Support\CostCalculator;

function calculator(array $pricing = ['claude-sonnet-5-5' => ['input_per_mtok' => '2', 'output_per_mtok' => '10']], mixed $search = '0.01'): CostCalculator
{
    return new CostCalculator($pricing, $search);
}

test('入力・出力・検索の単価から推定コストを計算する', function () {
    // 100万入力 × $2 + 50万出力 × $10 + 3回 × $0.01 = 2 + 5 + 0.03
    expect(calculator()->estimate('claude-sonnet-5-5', 1_000_000, 500_000, 3))->toEqualWithDelta(7.03, 1e-9);
});

test('使った量が 0 なら 0 ドル', function () {
    expect(calculator()->estimate('claude-sonnet-5-5', 0, 0, 0))->toBe(0.0);
});

test('単価が設定されていないモデルは計算しない', function () {
    // フェイク採点(モデル名 fake)や、フォールバック先のモデルなど
    expect(calculator()->estimate('fake', 100, 100, 0))->toBeNull();
});

test('入力か出力の単価が未設定なら計算しない', function (mixed $input, mixed $output) {
    $pricing = ['claude-sonnet-5-5' => ['input_per_mtok' => $input, 'output_per_mtok' => $output]];

    expect(calculator($pricing)->estimate('claude-sonnet-5-5', 100, 100, 0))->toBeNull();
})->with([
    '入力が null' => [null, '10'],
    '出力が空文字' => ['2', ''],
]);

test('検索の単価が未設定でも、検索していなければ計算する', function () {
    expect(calculator(search: null)->estimate('claude-sonnet-5-5', 1_000_000, 0, 0))->toEqualWithDelta(2.0, 1e-9);
});

test('検索の単価が未設定で、検索していたら計算しない', function () {
    expect(calculator(search: null)->estimate('claude-sonnet-5-5', 100, 100, 1))->toBeNull();
});
