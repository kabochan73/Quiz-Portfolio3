<?php

namespace App\Services\Grading;

/**
 * 採点する1問分の入力。問題文と回答文のペア。
 */
final readonly class GradingItem
{
    public function __construct(
        // 挑戦の中での順番(0始まり)。採点結果との対応付けに使う(architecture.md 2.5)
        public int $position,
        public string $question,
        public string $answer,
    ) {}
}
