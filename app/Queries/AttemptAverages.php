<?php

namespace App\Queries;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * 挑戦の平均点(requirements.md 3.4、architecture.md 3.2)。
 * 平均点は保存せず、毎回 scores から計算する。問題を削除して回答が減っても、残った回答で正しく計算されるように。
 * 採点が完了した挑戦だけを対象にする。新旧は ID で決める(architecture.md 2.7)。
 */
final class AttemptAverages
{
    /**
     * 挑戦ごとの平均点。履歴一覧や結果画面に使う。
     *
     * @param  iterable<int>  $attemptIds
     * @return array<int, float> 挑戦 ID => 平均点(ID の順)。完了していない挑戦は含まない
     */
    public function forAttempts(iterable $attemptIds): array
    {
        $attemptIds = collect($attemptIds)->all();

        if ($attemptIds === []) {
            return [];
        }

        return $this->completedScores()
            ->whereIn('attempts.id', $attemptIds)
            ->groupBy('attempts.id')
            ->selectRaw('attempts.id, AVG(scores.score) AS average')
            ->pluck('average', 'attempts.id')
            ->map(fn ($average) => (float) $average)
            // GROUP BY の結果は並び順が決まらないので、呼び出し側が扱いやすいよう ID の順にそろえる
            ->sortKeys()
            ->all();
    }

    /**
     * 同じセクション・同じ種別(全問 / 苦手)で、この挑戦のひとつ前の完了済みの挑戦の平均点。
     * 結果画面で平均点の前回比を出すのに使う(screens.md 2.10 (b))。
     *
     * @return float|null 前の挑戦がなければ null(初回)
     */
    public function previous(Attempt $attempt): ?float
    {
        $previousId = Attempt::query()
            ->where('section_id', $attempt->section_id)
            ->where('mode', $attempt->mode)
            ->where('status', AttemptStatus::Completed)
            ->where('id', '<', $attempt->id)
            ->max('id');

        return $previousId === null ? null : ($this->forAttempts([$previousId])[$previousId] ?? null);
    }

    /**
     * セクションごとに、いちばん新しい完了済みの挑戦の平均点。カテゴリ詳細の「平均」に使う(screens.md 2.4)。
     *
     * @param  iterable<int>  $sectionIds
     * @return array<int, float> セクション ID => 平均点(ID の順)。完了した挑戦がないセクションは含まない
     */
    public function latestForSections(iterable $sectionIds): array
    {
        $sectionIds = collect($sectionIds)->all();

        if ($sectionIds === []) {
            return [];
        }

        // セクションごとに、ID が最大の完了済みの挑戦を1つずつ選ぶ
        $latestAttemptIds = DB::table('attempts')
            ->where('status', AttemptStatus::Completed->value)
            ->whereIn('section_id', $sectionIds)
            ->groupBy('section_id')
            ->selectRaw('section_id, MAX(id) AS attempt_id')
            ->pluck('attempt_id', 'section_id');

        $averages = $this->forAttempts($latestAttemptIds->values());

        return $latestAttemptIds
            ->filter(fn ($attemptId) => isset($averages[$attemptId]))
            ->map(fn ($attemptId) => $averages[$attemptId])
            ->sortKeys()
            ->all();
    }

    private function completedScores(): Builder
    {
        return DB::table('attempts')
            ->join('answers', 'answers.attempt_id', '=', 'attempts.id')
            ->join('scores', 'scores.answer_id', '=', 'answers.id')
            ->where('attempts.status', AttemptStatus::Completed->value);
    }
}
