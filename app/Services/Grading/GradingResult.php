<?php

namespace App\Services\Grading;

/**
 * 1回の挑戦の採点結果。全問の点数と、採点にかかった利用量(requirements.md 3.3)。
 */
final readonly class GradingResult
{
    /**
     * @param  list<Grade>  $grades  出題順
     */
    public function __construct(
        public array $grades,
        // 採点に使ったモデル名(attempts.model)
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
        public int $webSearchRequests,
    ) {}
}
