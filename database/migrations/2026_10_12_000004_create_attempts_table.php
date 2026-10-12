<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// architecture.md 2.4
// 「挑戦」= セクションの問題に一括で回答して送信した1回分。ユーザーは1人なので user_id は持たない(architecture.md 6章)。
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();

            // PostgreSQL の enum 型ではなく、varchar + CHECK 制約にする(値を足すマイグレーションを簡単にするため)。
            // Laravel の enum() は PostgreSQL でこの形で作る。
            // 値はアプリの Enum(GradingLevel など)と同じだが、マイグレーションは書いた時点の形を残すものなので、Enum は参照しない。
            $table->enum('grading_level', ['easy', 'normal', 'strict']);
            $table->enum('mode', ['all', 'weak']);
            $table->enum('status', ['pending', 'grading', 'completed', 'failed'])->default('pending');

            // 利用量の記録(requirements.md 3.3)。採点が終わるまでは空
            $table->string('model', 100)->nullable();
            $table->integer('input_tokens')->nullable();
            $table->integer('output_tokens')->nullable();
            $table->integer('web_search_requests')->nullable();

            // 失敗の理由。ログ用途で、画面には出さない(screens.md 2.10 (c))
            $table->text('error_message')->nullable();
            $table->timestampTz('graded_at')->nullable();
            $table->timestampsTz();

            // 履歴一覧をセクションごとに新しい順で出すため。新旧は ID で決める(architecture.md 2.7)
            $table->index(['section_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
