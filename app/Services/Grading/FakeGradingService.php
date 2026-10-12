<?php

namespace App\Services\Grading;

use App\Enums\GradingLevel;

/**
 * Claude API を呼ばずに、決まった採点結果を返すフェイク(CLAUDE.md)。
 * テストと、画面の作り込みに使う(GRADING_DRIVER=fake)。API の料金がかからない。
 */
final class FakeGradingService implements GradingService
{
    public const MODEL = 'fake';

    public function grade(array $items, GradingLevel $level): GradingResult
    {
        $grades = array_map(fn (GradingItem $item) => new Grade(
            position: $item->position,
            score: self::scoreFor($item->answer),
            goodPoints: "(フェイク採点)問題 {$this->number($item)} の良かった点です。",
            weakPoints: "(フェイク採点)問題 {$this->number($item)} の悪かった点です。",
            modelAnswer: "(フェイク採点)問題 {$this->number($item)} の模範解答です。",
        ), $items);

        return new GradingResult(
            grades: $grades,
            model: self::MODEL,
            inputTokens: 0,
            outputTokens: 0,
            webSearchRequests: 0,
        );
    }

    /**
     * 回答文から 0〜100点 を決める。
     * 同じ回答なら毎回同じ点数になり(テストで結果を予想できる)、
     * 回答が違えば点数もばらつく(画面で高得点・苦手の両方の表示を確かめられる)。
     */
    public static function scoreFor(string $answer): int
    {
        return crc32($answer) % 101;
    }

    private function number(GradingItem $item): int
    {
        return $item->position + 1;
    }
}
