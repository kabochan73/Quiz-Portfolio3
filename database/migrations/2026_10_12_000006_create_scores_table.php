<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// architecture.md 2.6
// 回答(answers)と1対1。行がないこと = まだ採点されていないことを表す。
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')->unique()->constrained()->cascadeOnDelete();
            $table->smallInteger('score');
            // フィードバック(requirements.md 3.3)。AI の採点コメントなので日本語(requirements.md 5.5)
            $table->text('good_points');   // 良かった点
            $table->text('weak_points');   // 悪かった点
            $table->text('model_answer');  // 模範解答
            $table->timestampsTz();
        });

        // 点数は 0〜100 点。Laravel のスキーマビルダには CHECK 制約を足す方法がないため、SQL で足す
        DB::statement('ALTER TABLE scores ADD CONSTRAINT scores_score_range CHECK (score BETWEEN 0 AND 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
