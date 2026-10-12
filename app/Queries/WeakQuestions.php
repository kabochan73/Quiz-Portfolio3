<?php

namespace App\Queries;

use App\Models\Section;

/**
 * 苦手問題 = 最新の点数が基準点(既定 60点)未満の問題(requirements.md 3.3、architecture.md 3.1)。
 * 一度も採点されていない問題は含めない。
 */
final readonly class WeakQuestions
{
    public function __construct(private LatestScores $latestScores) {}

    /**
     * @return list<int> 苦手問題の ID(セクションの問題の並び順)
     */
    public function idsFor(Section $section): array
    {
        $questionIds = $section->questions()->pluck('id');
        $latestScores = $this->latestScores->forQuestions($questionIds);
        $threshold = config('quiz.weak_threshold');

        return $questionIds
            ->filter(fn (int $id) => isset($latestScores[$id]) && $latestScores[$id] < $threshold)
            ->values()
            ->all();
    }
}
