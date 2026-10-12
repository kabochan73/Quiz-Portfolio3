<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->user = User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-pass']);
});

function login(string $password = 'secret-pass', string $email = 'admin@example.com'): TestResponse
{
    return test()->post('/login', ['email' => $email, 'password' => $password]);
}

test('正しいメールアドレスとパスワードでログインすると、ホームへ移る', function () {
    login()->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($this->user);
});

test('ログイン状態は、チェックボックスなしで常に30日間保持する', function () {
    // requirements.md 3.1
    $response = login();

    $cookie = collect($response->headers->getCookies())
        ->first(fn ($cookie) => $cookie->getName() === Auth::guard()->getRecallerName());

    expect($cookie)->not->toBeNull()
        ->and($cookie->getExpiresTime())->toBeBetween(now()->addDays(30)->subMinute()->timestamp, now()->addDays(30)->addMinute()->timestamp);
});

test('パスワードが違えばログインできず、1つのエラーを出す', function () {
    login('wrong-password')->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

    $this->assertGuest();
});

test('入力が足りなければエラーにする', function () {
    $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
});

test('1分間に6回失敗したら、何秒後に再試行できるかを出し、正しいパスワードでもログインさせない', function () {
    // 待ち時間(秒)がテストの実行中に進まないよう、時刻を止める
    $this->freezeTime();

    foreach (range(1, 6) as $_) {
        login('wrong-password');
    }

    login()->assertSessionHasErrors(['email' => __('auth.throttle', ['seconds' => 60])]);

    $this->assertGuest();
});

test('ログインに成功したら、失敗の回数をリセットする', function () {
    foreach (range(1, 5) as $_) {
        login('wrong-password');
    }
    login();
    $this->post('/logout');

    login('wrong-password');
    login()->assertRedirect(route('home'));
});

test('ログアウトすると、ログイン画面へ戻る', function () {
    $this->actingAs($this->user)->post('/logout')->assertRedirect(route('login'));

    $this->assertGuest();
});

test('ログインしていなければ、ホームを開くとログイン画面へ移る', function () {
    $this->get('/home')->assertRedirect(route('login'));
});

test('トップページはホームへ移す(未ログインなら、そのあとログイン画面へ)', function () {
    $this->get('/')->assertRedirect('/home');
});

test('ログイン済みでログイン画面を開くと、ホームへ移る', function () {
    $this->actingAs($this->user)->get('/login')->assertRedirect(route('home'));
});

test('ログインしていなければ、ログアウトはできない', function () {
    $this->post('/logout')->assertRedirect(route('login'));
});
