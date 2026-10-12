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
 */
#[Fillable(['question_id', 'position', 'body'])]
class Answer extends Model
{
    /** @use HasFactory<AnswerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
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
