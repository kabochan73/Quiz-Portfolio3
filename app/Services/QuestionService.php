<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Section;
use Illuminate\Support\Facades\DB;

/**
 * 問題の作成と削除。作成は、上限のチェックと追加をトランザクションで囲む(CLAUDE.md)。
 */
final readonly class QuestionService
{
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
     * 問題を削除する。回答・採点結果・挑戦(履歴)は残す(architecture.md 4.6。2026-10-13 決定)。
     * 回答の問題への参照は DB の ON DELETE SET NULL で空になり、問題文は回答の写し(question_body)に残る。
     */
    public function delete(Question $question): void
    {
        $question->delete();
    }
}
