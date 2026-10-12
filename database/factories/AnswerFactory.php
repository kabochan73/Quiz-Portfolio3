<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attempt_id' => Attempt::factory(),
            // 問題は、挑戦と同じセクションのものにする(別のセクションの問題に回答することはないため)
            'question_id' => fn (array $attributes) => Question::factory()->create([
                'section_id' => Attempt::findOrFail($attributes['attempt_id'])->section_id,
            ])->id,
            // 同じ挑戦の中で順番が重ならないよう、すでにある回答の数を次の順番にする(answers の UNIQUE 制約)。
            // count() でまとめて作ると保存前に全件の値が決まり、すべて同じ順番になるので、そのときは inOrder() を使う
            'position' => fn (array $attributes) => Answer::where('attempt_id', $attributes['attempt_id'])->count(),
            'body' => fake()->paragraph(),
        ];
    }

    /**
     * まとめて作るときに、作る順に 0, 1, 2, … と順番を振る。
     */
    public function inOrder(): static
    {
        return $this->sequence(fn (Sequence $sequence) => ['position' => $sequence->index]);
    }
}
