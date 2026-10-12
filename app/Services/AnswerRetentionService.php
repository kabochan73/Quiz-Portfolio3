<?php

namespace App\Services;

use App\Models\Answer;
use Illuminate\Support\Facades\DB;

/**
 * 古い回答の整理(requirements.md 3.4、architecture.md 4.5)。
 * 同じ問題の回答は直近 N 件(既定 10件)だけ残し、それより古いものを削除する。
 * 採点中・失敗した挑戦の回答も件数に含める。削除で回答が0件になった挑戦も削除する。
 */
final readonly class AnswerRetentionService
{
    public function __construct(private EmptyAttemptPruner $pruner) {}

    /**
     * 回答を保存したトランザクションの中で、今回回答した問題について呼ぶ。
     *
     * @param  iterable<int>  $questionIds
     */
    public function prune(iterable $questionIds): void
    {
        $limit = config('quiz.answer_retention_limit');

        // 呼び出し側のトランザクションの中で呼ばれる前提だが、単独で呼ばれても途中で止まらないよう囲んでおく
        // (すでにトランザクションの中なら、Laravel はセーブポイントとして扱う)
        DB::transaction(function () use ($questionIds, $limit) {
            foreach (collect($questionIds)->unique() as $questionId) {
                // 新旧は挑戦の ID で決める(architecture.md 2.7)。1つの挑戦に同じ問題の回答は1つだけ
                $oldAnswers = Answer::query()
                    ->where('question_id', $questionId)
                    ->orderByDesc('attempt_id')
                    // limit + 1 件目以降 = 削除する回答
                    ->skip($limit)
                    ->get(['id', 'attempt_id']);

                if ($oldAnswers->isEmpty()) {
                    continue;
                }

                // scores は cascade で一緒に消える
                Answer::query()->whereIn('id', $oldAnswers->pluck('id'))->delete();
                $this->pruner->prune($oldAnswers->pluck('attempt_id'));
            }
        });
    }
}
