<?php

namespace App\Services\Grading;

/**
 * 1問分の採点結果。scores テーブルの1行になる(architecture.md 2.6)。
 */
final readonly class Grade
{
    public function __construct(
        public int $position,
        // 0〜100点
        public int $score,
        // 良かった点 / 悪かった点 / 模範解答(requirements.md 3.3)
        public string $goodPoints,
        public string $weakPoints,
        public string $modelAnswer,
    ) {}
}
