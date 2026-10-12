<?php

namespace App\Models;

use Database\Factories\ScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1問の採点結果(architecture.md 2.6)。回答と1対1。
 * フィードバックは 良かった点 / 悪かった点 / 模範解答 の3つ(requirements.md 3.3)。
 */
#[Fillable(['score', 'good_points', 'weak_points', 'model_answer'])]
class Score extends Model
{
    /** @use HasFactory<ScoreFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Answer, $this>
     */
    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}
