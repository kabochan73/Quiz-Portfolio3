<?php

use App\Support\CostCalculator;

test('推定コストの計算には config/quiz.php の単価を使う', function () {
    config([
        'quiz.pricing' => ['claude-sonnet-5-5' => ['input_per_mtok' => '3', 'output_per_mtok' => '15']],
        'quiz.web_search_per_request' => '0.02',
    ]);

    expect(app(CostCalculator::class)->estimate('claude-sonnet-5-5', 1_000_000, 1_000_000, 1))
        ->toEqualWithDelta(18.02, 1e-9);
});
