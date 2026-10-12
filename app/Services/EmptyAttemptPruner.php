<?php

namespace App\Services;

use App\Models\Attempt;

/**
 * 回答が0件になった挑戦を削除する(architecture.md 4.5 / 4.6)。
 * 回答のない挑戦が履歴に残らないようにするため、古い回答の整理と問題の削除の両方から使う。
 */
final class EmptyAttemptPruner
{
    /**
     * @param  iterable<int>  $attemptIds  回答が減った可能性のある挑戦
     * @return int 削除した挑戦の数
     */
    public function prune(iterable $attemptIds): int
    {
        $attemptIds = collect($attemptIds)->unique()->all();

        if ($attemptIds === []) {
            return 0;
        }

        return Attempt::query()
            ->whereIn('id', $attemptIds)
            ->whereDoesntHave('answers')
            ->delete();
    }
}
