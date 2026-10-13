<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// architecture.md 2.5 / 4.6(2026-10-13 決定)
// 問題を削除しても履歴を残す。回答には、回答したときの問題文を写しておき、問題を消したら問題への参照を空にする。
return new class extends Migration
{
    public function up(): void
    {
        // 1. 問題文の写しの列を足し、すでにある回答には今の問題文を写す
        Schema::table('answers', function (Blueprint $table) {
            $table->text('question_body')->nullable()->after('position');
        });

        DB::statement('UPDATE answers SET question_body = questions.body FROM questions WHERE questions.id = answers.question_id');

        // 2. 写しは必須にし、問題を消しても回答が消えないよう、外部キーを「消したら NULL にする」に付け替える
        Schema::table('answers', function (Blueprint $table) {
            $table->text('question_body')->nullable(false)->change();

            $table->dropForeign(['question_id']);
            $table->unsignedBigInteger('question_id')->nullable()->change();
            $table->foreign('question_id')->references('id')->on('questions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // 問題への参照がない回答は、元の形(問題を消したら回答も消える)に戻せないので削除する
        DB::table('answers')->whereNull('question_id')->delete();

        Schema::table('answers', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->unsignedBigInteger('question_id')->nullable(false)->change();
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();

            $table->dropColumn('question_body');
        });
    }
};
