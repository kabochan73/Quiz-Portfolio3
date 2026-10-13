<?php

namespace App\Models;

use Database\Factories\AnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 挑戦の中の、1問への回答(architecture.md 2.5)。
 * 挑戦(attempt_id)はリレーション経由で決めるので fillable にしない。
 *
 * 回答したときの問題文を question_body に写しておく(2026-10-13 決定)。
 * あとから問題を消したり書き直したりしても、履歴は「その時の問題と回答」のまま残るようにするため。
 * 問題を消すと question_id は NULL になる(DB の ON DELETE SET NULL)。
 */
#[Fillable(['question_id', 'position', 'question_body', 'body'])]
class Answer extends Model
{
    /** @use HasFactory<AnswerFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // 回答を作るどこからでも書き忘れが起きないよう、保存するときに問題の今の問題文を写す
        static::creating(function (Answer $answer) {
            if ($answer->question_body === null && $answer->question_id !== null) {
                $answer->question_body = Question::query()->whereKey($answer->question_id)->value('body');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * 問題を削除していれば null。問題文は question_body を使う。
     *
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return BelongsTo<Attempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    /**
     * 採点結果。まだ採点されていなければ null(architecture.md 2.6)。
     *
     * @return HasOne<Score, $this>
     */
    public function score(): HasOne
    {
        return $this->hasOne(Score::class);
    }
}
