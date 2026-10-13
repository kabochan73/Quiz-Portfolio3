<?php

use App\Models\User;

// 共通コンポーネントの見本ページ(implementation-plan.md 5-6)

test('開発環境では、共通コンポーネントの見本を1画面で見られる', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dev/components')
        ->assertOk()
        ->assertSee('<title>Components | Recollect</title>', false)
        ->assertSeeInOrder(['Colors', 'Typography', 'Buttons', 'Form fields', 'Badges', 'Cards', 'Feedback', 'Icons'])
        // 入力欄のエラーの見本
        ->assertSee('The name field is required.');
});

test('本番では開けない', function () {
    app()['env'] = 'production';
    // runningUnitTests() は env を見るので、本番に切り替えると見本ページは 404 になる

    $this->actingAs(User::factory()->create())->get('/dev/components')->assertNotFound();
});

test('ログインしていなければ開けない', function () {
    $this->get('/dev/components')->assertRedirect(route('login'));
});
