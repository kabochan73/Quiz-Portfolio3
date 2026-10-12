<?php

namespace App\Services\Grading;

/**
 * submit_grades ツールに渡された採点結果を検証し、Grade の一覧にする(architecture.md 4.4「結果の検証」)。
 * API に依存しない純粋なロジックなので、Unit テストで細かく確かめる。
 */
final class GradeResultParser
{
    private const TEXT_FIELDS = ['good_points', 'weak_points', 'model_answer'];

    /**
     * @param  array<string, mixed>  $toolInput  submit_grades の入力(JSON をデコードしたもの)
     * @param  list<GradingItem>  $items  採点を頼んだ問題と回答
     * @return list<Grade> 出題順
     *
     * @throws InvalidGradingResultException
     */
    public function parse(array $toolInput, array $items): array
    {
        $rawGrades = $toolInput['grades'] ?? null;

        if (! is_array($rawGrades) || ! array_is_list($rawGrades)) {
            throw new InvalidGradingResultException('grades が配列ではありません。');
        }

        if (count($rawGrades) !== count($items)) {
            throw new InvalidGradingResultException(
                sprintf('採点結果の件数(%d)が問題数(%d)と一致しません。', count($rawGrades), count($items)),
            );
        }

        $grades = array_map(fn (mixed $rawGrade) => $this->toGrade($rawGrade), $rawGrades);

        // position は、採点を頼んだ問題の position とちょうど同じ組み合わせでなければならない(重複・抜けがない)
        $positions = array_map(fn (Grade $grade) => $grade->position, $grades);
        $expectedPositions = array_map(fn (GradingItem $item) => $item->position, $items);
        sort($positions);
        sort($expectedPositions);

        if ($positions !== $expectedPositions) {
            throw new InvalidGradingResultException('position に重複か抜けがあります。');
        }

        usort($grades, fn (Grade $a, Grade $b) => $a->position <=> $b->position);

        return $grades;
    }

    private function toGrade(mixed $rawGrade): Grade
    {
        if (! is_array($rawGrade) || ! is_int($rawGrade['position'] ?? null) || ! is_int($rawGrade['score'] ?? null)) {
            throw new InvalidGradingResultException('position か score が整数ではありません。');
        }

        foreach (self::TEXT_FIELDS as $field) {
            if (! is_string($rawGrade[$field] ?? null) || trim($rawGrade[$field]) === '') {
                throw new InvalidGradingResultException("{$field} が空です。");
            }
        }

        return new Grade(
            position: $rawGrade['position'],
            // 範囲外の点数は、採点をやり直すほどの問題ではないので、範囲内に収める
            score: max(0, min(100, $rawGrade['score'])),
            goodPoints: trim($rawGrade['good_points']),
            weakPoints: trim($rawGrade['weak_points']),
            modelAnswer: trim($rawGrade['model_answer']),
        );
    }
}
