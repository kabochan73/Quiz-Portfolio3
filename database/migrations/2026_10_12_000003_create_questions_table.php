<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// architecture.md 2.3
// ユーザーは1人なので user_id は持たない(architecture.md 6章)。
// 1セクション10問までの上限は、同時に追加されても超えないようアプリ側でロックしてチェックする(architecture.md 4.1)。
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->index()->constrained()->cascadeOnDelete();
            // 文字数の上限(2000文字)は FormRequest でチェックする
            $table->text('body');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
