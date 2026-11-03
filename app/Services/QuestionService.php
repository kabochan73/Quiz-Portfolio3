<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Section;
use Illuminate\Support\Facades\DB;

/**
 * 問題の作成。上限のチェックと追加をトランザクションで囲む(CLAUDE.md)。
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
}
