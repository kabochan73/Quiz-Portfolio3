<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * 唯一のログインユーザー(管理者)を作る(requirements.md 3.1)。
 * メールアドレスとパスワードは .env(config/quiz.php の admin)から読む。リポジトリは公開するので、コードには書かない。
 * 本番では最初に1回だけ、Railway から `php artisan db:seed --class=AdminUserSeeder --force` で実行する(architecture.md 8.3)。
 */
class AdminUserSeeder extends Seeder
{
    // .env.example に書いてある開発用のパスワード。本番でこのまま使われるのを防ぐ
    private const DEV_PASSWORD = 'password';

    // ログインは1分に6回までしか試せないので、ランダムな8文字なら総当たりでは当てられない(requirements.md 3.1)
    private const MIN_PRODUCTION_PASSWORD_LENGTH = 8;

    public function run(): void
    {
        $email = config('quiz.admin.email');
        $password = config('quiz.admin.password');

        if (blank($email) || blank($password)) {
            throw new RuntimeException('ADMIN_EMAIL と ADMIN_PASSWORD を .env に設定してください。');
        }

        // requirements.md 3.1: 本番で弱いパスワードのまま管理者が作られないよう、ここで止める
        if (app()->isProduction()
            && ($password === self::DEV_PASSWORD || mb_strlen($password) < self::MIN_PRODUCTION_PASSWORD_LENGTH)) {
            throw new RuntimeException('本番では、ADMIN_PASSWORD を開発用の値以外の8文字以上にしてください。');
        }

        // 何度実行しても管理者は1人のまま。パスワードを変えたいときは、.env を直して再実行すれば上書きされる
        User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Admin', 'password' => $password],
        );
    }
}
