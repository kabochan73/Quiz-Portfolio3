<?php

use App\Models\User;

// ログイン画面とホーム(screens.md 2.1 / 2.12)

test('ログイン画面に、メールアドレス・パスワード・ログインボタンを出す', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('<title>Log in | Recollect</title>', false)
        ->assertSeeInOrder(['Recollect', 'A study notebook graded by AI', 'Email', 'Password', 'Log in'])
        ->assertSee('action="'.route('login.store').'"', false)
        ->assertSee('name="_token"', false)
        ->assertSee('type="password"', false);
});

test('ログイン画面に、ログイン状態を保持するチェックボックスは置かない', function () {
    // requirements.md 3.1: 常に30日保持する
    $this->get('/login')
        ->assertDontSee('type="checkbox"', false)
        ->assertDontSee('Remember');
});

test('認証に失敗したら、どちらの項目が違うかを出さず、フォームの上に1つだけエラーを出す', function () {
    User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-pass']);

    $this->followingRedirects()
        ->from('/login')
        ->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong'])
        ->assertSeeInOrder(['role="alert"', 'These credentials do not match our records.', 'Email'], false)
        // メールアドレスの欄は赤くしない
        ->assertDontSee('aria-invalid', false)
        // 入力したメールアドレスは残す
        ->assertSee('value="admin@example.com"', false);
});

test('入力が足りなければ、その項目の下にエラーを出す', function () {
    $this->followingRedirects()
        ->from('/login')
        ->post('/login', ['email' => '', 'password' => ''])
        ->assertSee('The email field is required.')
        ->assertSee('The password field is required.')
        ->assertDontSee('role="alert"', false);
});

test('ホームには、New category ボタンだけを置く', function () {
    // screens.md 2.12: コンセプトと使い方の文章は後回し
    $this->actingAs(User::factory()->create())
        ->get('/home')
        ->assertOk()
        ->assertSee('<title>Home | Recollect</title>', false)
        ->assertSee('href="'.route('categories.create').'"', false)
        ->assertSee('New category');
});

test('ログインすると、ホームが表示される', function () {
    // implementation-plan.md 5-7 の完了の条件
    User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-pass']);

    $this->followingRedirects()
        ->post('/login', ['email' => 'admin@example.com', 'password' => 'secret-pass'])
        ->assertOk()
        ->assertSee('<title>Home | Recollect</title>', false);
});
