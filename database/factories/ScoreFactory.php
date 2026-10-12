<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Score;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Score>
 */
class ScoreFactory extends Factory
{
    public function definition(): array
    {
        // AI の採点コメントは日本語(requirements.md 5.5)
        return [
            'answer_id' => Answer::factory(),
            'score' => fake()->numberBetween(0, 100),
            'good_points' => '要点をおさえて説明できています。',
            'weak_points' => '具体例があると、もっとわかりやすくなります。',
            'model_answer' => 'これは模範解答の例です。',
        ];
    }
}
