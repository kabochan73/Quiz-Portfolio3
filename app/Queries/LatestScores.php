<?php

namespace App\Queries;

use App\Enums\AttemptStatus;
use Illuminate\Support\Facades\DB;

/**
 * 問題ごとの最新の点数(architecture.md 3.1 / 3.2)。
 * - セクション詳細の点数バッジ、苦手問題の判定に使う
 * - 「ある挑戦より前」に絞ると、結果画面の「前回の点数」になる
 *
 * 採点が完了した挑戦の回答だけを対象にする。新旧は挑戦の ID で決める(architecture.md 2.7)。
 * 1つの挑戦に同じ問題の回答は1つだけなので(answers の UNIQUE 制約)、問題ごとに挑戦の ID が最大の1件が最新になる。
 */
final class LatestScores
{
    /**
     * @param  iterable<int>  $questionIds
     * @param  int|null  $beforeAttemptId  指定すると、この挑戦より前の挑戦だけを見る(前回の点数)
     * @return array<int, int> 問題 ID => 点数。一度も採点されていない問題は含まない
     */
    public function forQuestions(iterable $questionIds, ?int $beforeAttemptId = null): array
    {
        $questionIds = collect($questionIds)->all();

        if ($questionIds === []) {
            return [];
        }

        // 問題ごとに1回のクエリで済ませるため、PostgreSQL の DISTINCT ON で各問題の先頭(最新)の1行だけを取る
        return DB::table('answers')
            ->join('attempts', 'attempts.id', '=', 'answers.attempt_id')
            ->join('scores', 'scores.answer_id', '=', 'answers.id')
            ->where('attempts.status', AttemptStatus::Completed->value)
            ->whereIn('answers.question_id', $questionIds)
            ->when($beforeAttemptId !== null, fn ($query) => $query->where('answers.attempt_id', '<', $beforeAttemptId))
            ->selectRaw('DISTINCT ON (answers.question_id) answers.question_id, scores.score')
            ->orderBy('answers.question_id')
            ->orderByDesc('answers.attempt_id')
            ->pluck('scores.score', 'answers.question_id')
            ->map(fn ($score) => (int) $score)
            ->all();
    }
}
