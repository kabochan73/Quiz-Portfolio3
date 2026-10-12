<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// architecture.md 2.5
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            // 問題を削除したら、その問題への回答も消す(回答が0件になった挑戦の削除はアプリ側で行う。architecture.md 4.6)
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            // その挑戦の中での問題の順番(0始まり)。採点結果との対応付けにも使う(architecture.md 4.4)
            $table->smallInteger('position');
            // 文字数の上限(5000文字)は FormRequest でチェックする
            $table->text('body');
            $table->timestampsTz();

            // 結果画面を回答した順に並べるため。attempt_id から始まるので、attempt_id の外部キーのインデックスも兼ねる
            $table->unique(['attempt_id', 'position']);
            // 1つの挑戦の中で、同じ問題への回答は1つだけ。入力チェックをすり抜けても DB で止める(architecture.md 2.5)
            $table->unique(['attempt_id', 'question_id']);
            // 最新の点数・前回の点数・古い回答の整理で、問題ごとに挑戦の新しい順に並べるため(architecture.md 2.7 / 3章)
            $table->index(['question_id', 'attempt_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
