<?php

namespace Database\Factories;

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Models\Attempt;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attempt>
 */
class AttemptFactory extends Factory
{
    /**
     * 送信した直後の状態(採点前)。
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'grading_level' => GradingLevel::Normal,
            'mode' => AttemptMode::All,
            'status' => AttemptStatus::Pending,
        ];
    }

    /**
     * 採点が終わった状態。利用量も記録されている。
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => AttemptStatus::Completed,
            'model' => 'claude-sonnet-5-5',
            'input_tokens' => fake()->numberBetween(1000, 20000),
            'output_tokens' => fake()->numberBetween(500, 5000),
            'web_search_requests' => fake()->numberBetween(0, 3),
            'graded_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => AttemptStatus::Failed,
            'error_message' => 'API error',
        ]);
    }

    public function weak(): static
    {
        return $this->state(fn () => ['mode' => AttemptMode::Weak]);
    }
}
