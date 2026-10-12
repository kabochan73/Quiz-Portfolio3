<?php

namespace App\Models;

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use Database\Factories\AttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 挑戦 = セクションの問題に一括で回答して送信した1回分(requirements.md 3.3)。
 * 採点の状態と、採点にかかった利用量もここに記録する。
 */
#[Fillable([
    'grading_level',
    'mode',
    'status',
    'model',
    'input_tokens',
    'output_tokens',
    'web_search_requests',
    'error_message',
    'graded_at',
])]
class Attempt extends Model
{
    /** @use HasFactory<AttemptFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'grading_level' => GradingLevel::class,
            'mode' => AttemptMode::class,
            'status' => AttemptStatus::class,
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'web_search_requests' => 'integer',
            'graded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * 回答は、挑戦の中で出題した順(position)に並べる(architecture.md 2.5)。
     *
     * @return HasMany<Answer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->orderBy('position');
    }
}
