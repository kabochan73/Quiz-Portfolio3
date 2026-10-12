<?php

namespace Database\Seeders;

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;
use App\Models\Attempt;
use App\Models\Category;
use App\Models\Section;
use Illuminate\Database\Seeder;

/**
 * 開発用の見本データ。画面を作るときに、点数バッジ・前回比・苦手問題・失敗の表示などを確かめられるようにする。
 * 本番では入れない(architecture.md 8.3)。
 * 画面の見え方が毎回同じになるよう、ランダムな値は使わない。
 */
class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $basics = Category::create(['name' => '基本情報']);

        $network = $this->createSection($basics, 'ネットワーク', [
            'TCPとUDPの違いを説明してください。',
            'DNSの名前解決の流れを説明してください。',
            'HTTPSで通信が暗号化される仕組みを説明してください。',
        ]);
        // 2回挑戦し、2回目で前回比が出るようにする。2問目は2回とも60点未満 = 苦手問題
        $this->createCompletedAttempt($network, GradingLevel::Normal, AttemptMode::All, [70, 45, 80]);
        $this->createCompletedAttempt($network, GradingLevel::Strict, AttemptMode::All, [82, 52, 76]);

        $database = $this->createSection($basics, 'データベース', [
            '正規化の目的を説明してください。',
            'インデックスを貼ると検索が速くなる理由を説明してください。',
        ]);
        // 採点に失敗した挑戦(再採点ボタンの表示の確認用)
        $this->createAttemptWithAnswers($database, GradingLevel::Normal, AttemptMode::All)
            ->update(['status' => AttemptStatus::Failed, 'error_message' => 'Sample failure']);

        $laravel = Category::create(['name' => 'Laravel']);
        // まだ挑戦していないセクション
        $this->createSection($laravel, 'ルーティング', [
            'ルートモデルバインディングとは何か説明してください。',
        ]);
    }

    /**
     * @param  array<int, string>  $questionBodies
     */
    private function createSection(Category $category, string $name, array $questionBodies): Section
    {
        $section = $category->sections()->create(['name' => $name]);

        foreach ($questionBodies as $body) {
            $section->questions()->create(['body' => $body]);
        }

        return $section;
    }

    private function createAttemptWithAnswers(Section $section, GradingLevel $level, AttemptMode $mode): Attempt
    {
        $attempt = $section->attempts()->create(['grading_level' => $level, 'mode' => $mode]);

        foreach ($section->questions as $position => $question) {
            $attempt->answers()->create([
                'question_id' => $question->id,
                'position' => $position,
                'body' => "{$question->body} についての回答の例です。",
            ]);
        }

        return $attempt;
    }

    /**
     * @param  array<int, int>  $scores  出題順の点数
     */
    private function createCompletedAttempt(Section $section, GradingLevel $level, AttemptMode $mode, array $scores): Attempt
    {
        $attempt = $this->createAttemptWithAnswers($section, $level, $mode);

        foreach ($attempt->answers as $answer) {
            $answer->score()->create([
                'score' => $scores[$answer->position],
                'good_points' => '大事なポイントをおさえて説明できています。',
                'weak_points' => '具体的な例が1つあると、もっとわかりやすくなります。',
                'model_answer' => 'これは見本データの模範解答です。',
            ]);
        }

        $attempt->update([
            'status' => AttemptStatus::Completed,
            'model' => 'claude-sonnet-5-5',
            'input_tokens' => 12340,
            'output_tokens' => 3210,
            'web_search_requests' => 1,
            'graded_at' => now(),
        ]);

        return $attempt;
    }
}
