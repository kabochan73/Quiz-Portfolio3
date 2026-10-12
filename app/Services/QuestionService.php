<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Section;
use Illuminate\Support\Facades\DB;

/**
 * 問題の作成と削除。どちらも複数の行やテーブルにまたがるので、トランザクションで囲む(CLAUDE.md)。
 */
final readonly class QuestionService
{
    public function __construct(private EmptyAttemptPruner $pruner) {}

    /**
     * 問題を追加する。1セクションの問題数には上限がある(requirements.md 3.2)。
     *
     * @throws QuestionLimitExceededException
     */
    public function create(Section $section, string $body): Question
    {
        return DB::transaction(function () use ($section, $body) {
            // 同時に追加されて上限を超えないよう、セクションの行をロックしてから数える(architecture.md 4.1)。
            // ロックはトランザクションが終わるまで続くので、もう一方の追加は数え終わるまで待たされる
            Section::query()->whereKey($section->id)->lockForUpdate()->first();

            if ($section->questions()->count() >= config('quiz.max_questions_per_section')) {
                throw new QuestionLimitExceededException;
            }

            return $section->questions()->create(['body' => $body]);
        });
    }

    /**
     * 問題を削除する。回答と採点結果は cascade で消え、回答が0件になった挑戦も一緒に消す(architecture.md 4.6)。
     */
    public function delete(Question $question): void
    {
        DB::transaction(function () use ($question) {
            // 削除すると回答から挑戦をたどれなくなるので、先に控えておく
            $attemptIds = $question->answers()->pluck('attempt_id');

            $question->delete();

            $this->pruner->prune($attemptIds);
        });
    }
}
