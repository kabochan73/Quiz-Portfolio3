<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

// エラーページ(screens.md 4.2)

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    // テストのためだけに、エラーを起こすページを用意する
    Route::get('/_test/abort/{code}', fn (int $code) => abort($code))->middleware('web');
    Route::get('/_test/exception', fn () => throw new RuntimeException('secret detail'))->middleware('web');
});

test('存在しないページは、404 のページを出す', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('<title>Page not found | recollect</title>', false)
        ->assertSee('Back to home')
        ->assertSee(route('home'), false);
});

test('存在しないカテゴリを開いても、404 のページを出す', function () {
    $this->get('/categories/999999')->assertNotFound()->assertSee('Page not found');
});

test('403 も 404 と同じ文言にする', function () {
    $this->get('/_test/abort/403')
        ->assertForbidden()
        ->assertSee('Page not found')
        ->assertDontSee('Forbidden');
});

test('419 は、再読み込みをうながす', function () {
    $this->get('/_test/abort/419')
        ->assertStatus(419)
        ->assertSee('Page expired')
        ->assertSee('Please reload the page and try again.');
});

test('500 は、詳しい理由を出さずに、時間をおいてもらう', function () {
    // 本番と同じく、デバッグ表示をしない設定で確かめる
    config(['app.debug' => false]);

    $this->get('/_test/exception')
        ->assertStatus(500)
        ->assertSee('Something went wrong')
        ->assertSee('Please try again later.')
        ->assertDontSee('secret detail');
});
