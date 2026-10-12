<?php

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Support\Facades\Hash;

/**
 * 本番での実行手順と同じく --force を付けて実行する(architecture.md 8.3)。
 * 付けないと、本番環境では実行してよいかの確認で止まってしまう。
 */
function seedForced(string $seeder): void
{
    test()->artisan('db:seed', ['--class' => $seeder, '--force' => true])->assertSuccessful();
}

beforeEach(function () {
    config(['quiz.admin' => ['email' => 'admin@example.com', 'password' => 'password']]);
});

test('管理者を .env の値で作る', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::sole();
    expect($admin->email)->toBe('admin@example.com')
        ->and(Hash::check('password', $admin->password))->toBeTrue();
});

test('何度実行しても管理者は1人のままで、パスワードは上書きされる', function () {
    $this->seed(AdminUserSeeder::class);
    config(['quiz.admin.password' => 'new-password']);

    $this->seed(AdminUserSeeder::class);

    expect(User::count())->toBe(1)
        ->and(Hash::check('new-password', User::sole()->password))->toBeTrue();
});

test('メールアドレスかパスワードが未設定なら止まる', function (string $key) {
    config(["quiz.admin.{$key}" => null]);

    $this->seed(AdminUserSeeder::class);
})->with(['email', 'password'])->throws(RuntimeException::class);

test('本番では、弱いパスワードのままなら止まる', function (string $password) {
    // requirements.md 3.1
    app()['env'] = 'production';
    config(['quiz.admin.password' => $password]);

    seedForced(AdminUserSeeder::class);
})->with([
    '開発用の値' => 'password',
    '8文字未満' => 'k7#mQ2x',
])->throws(RuntimeException::class);

test('本番でも、8文字以上の別のパスワードなら作れる', function () {
    app()['env'] = 'production';
    // ちょうど8文字(境界値)
    config(['quiz.admin.password' => 'k7#mQ2xZ']);

    seedForced(AdminUserSeeder::class);

    expect(User::count())->toBe(1);
});

test('開発環境では、管理者と見本データを入れる', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(1)
        ->and(Category::count())->toBeGreaterThan(0);
});

test('本番では、管理者だけを入れる', function () {
    // architecture.md 8.3: 本番は管理者アカウントだけの空の状態から始める
    app()['env'] = 'production';
    config(['quiz.admin.password' => 'a-strong-password']);

    seedForced(DatabaseSeeder::class);

    expect(User::count())->toBe(1)
        ->and(Category::count())->toBe(0);
});

test('見本データは、画面で確かめたい状態をひととおり含む', function () {
    $this->seed(SampleDataSeeder::class);

    // 完了が2回(前回比が出る)、失敗が1回
    expect(Attempt::where('status', AttemptStatus::Completed)->count())->toBe(2)
        ->and(Attempt::where('status', AttemptStatus::Failed)->count())->toBe(1);

    // どの挑戦も、セクションの全問に1つずつ回答している
    Attempt::with('answers', 'section.questions')->get()->each(function (Attempt $attempt) {
        expect($attempt->answers->pluck('question_id')->all())
            ->toBe($attempt->section->questions->pluck('id')->all());
    });

    // 完了した挑戦は全問に採点結果があり、60点未満(苦手)の問題も含む
    $scores = Attempt::where('status', AttemptStatus::Completed)->with('answers.score')->get()
        ->flatMap(fn (Attempt $attempt) => $attempt->answers->pluck('score.score'));
    expect($scores)->not->toContain(null)
        ->and($scores->min())->toBeLessThan(config('quiz.weak_threshold'));
});
