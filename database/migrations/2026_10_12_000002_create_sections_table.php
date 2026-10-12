<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// architecture.md 2.2
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            // requirements.md 3.2: カテゴリを削除したら、配下のセクションも消す
            // (PostgreSQL は外部キーに自動でインデックスを作らないので、index() を付ける)
            $table->foreignId('category_id')->index()->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
