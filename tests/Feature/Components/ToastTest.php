<?php

// トースト(design-guide.md 7.5)。表示の動き(4秒で消える・× で閉じる)は resources/js/toast.js で、ブラウザで確かめる

test('コントローラから渡したトーストを、最初に出すデータとして渡す', function () {
    session()->flash('toast', ['type' => 'success', 'message' => 'Category created.']);

    $this->blade('<x-ui.toast-stack />')
        ->assertSee('x-data="toastStack(', false)
        ->assertSee('Category created.', false);
});

test('トーストがなければ、空で始める', function () {
    $this->blade('<x-ui.toast-stack />')
        ->assertSee('toastStack([])', false);
});

test('画面の JS からのトーストも受け取れる', function () {
    // 回答フォームの「Draft restored.」など(screens.md 2.9)
    $this->blade('<x-ui.toast-stack />')->assertSee('x-on:toast.window="add($event.detail)"', false);
});

test('エラーのトーストは alert、それ以外は status として読み上げる', function () {
    $this->blade('<x-ui.toast-stack />')
        ->assertSee("toast.type === 'error' ? 'alert' : 'status'", false);
});

test('ログイン後の画面のレイアウトに、トーストの入れ物を1つ置く', function () {
    $html = (string) $this->blade('<x-layouts.app>Body</x-layouts.app>');

    expect(substr_count($html, 'toastStack('))->toBe(1);
});
