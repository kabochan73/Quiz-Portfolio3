<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'body' => fake()->sentence().'?',
        ];
    }
}
