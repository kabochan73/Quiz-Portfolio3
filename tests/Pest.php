<?php

use App\Models\Attempt;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature テストは Laravel の TestCase で動かし、テストごとに DB を作り直す。
| Unit テストは DB や API に依存しないロジックだけを対象にするので(CLAUDE.md)、素の PHPUnit のまま。
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| 共通の補助関数
|--------------------------------------------------------------------------
|
| 複数のテストファイルで使うものだけをここに置く。
|
*/

/**
 * 採点が完了した挑戦を1回作り、問題ごとに点数をつける。
 *
 * @param  array<int, int>  $scores  問題 ID => 点数
 */
function scoredAttempt(Section $section, array $scores): Attempt
{
    $attempt = Attempt::factory()->for($section)->completed()->create();

    foreach (array_keys($scores) as $position => $questionId) {
        $answer = $attempt->answers()->create(['question_id' => $questionId, 'position' => $position, 'body' => 'answer']);
        $answer->score()->create([
            'score' => $scores[$questionId],
            'good_points' => 'g',
            'weak_points' => 'w',
            'model_answer' => 'm',
        ]);
    }

    return $attempt;
}
