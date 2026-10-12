<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * 分類(requirements.md 3.2)。Category → Section → Question の一番上。
 */
#[Fillable(['name'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /**
     * カテゴリ内の全問題。カテゴリ一覧の問題数や、削除の確認モーダルの件数に使う(screens.md 2.2 / 2.4)。
     *
     * @return HasManyThrough<Question, Section, $this>
     */
    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(Question::class, Section::class);
    }
}
