<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * 開発環境: 管理者 + 見本データ。本番: 管理者だけ(architecture.md 8.3)。
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        if (! app()->isProduction()) {
            $this->call(SampleDataSeeder::class);
        }
    }
}
