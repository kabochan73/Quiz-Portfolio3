<?php

// 共通コンポーネントの出力(design-guide.md 7.1 / 7.3)。見た目は /dev/components(5-6)で確かめる

test('href がなければ button を出し、type の既定は button', function () {
    $this->blade('<x-ui.button>Save</x-ui.button>')
        ->assertSee('<button type="button"', false)
        ->assertSee('Save');
});

test('送信ボタンにできる', function () {
    $this->blade('<x-ui.button type="submit">Create</x-ui.button>')
        ->assertSee('type="submit"', false);
});

test('href を渡すとリンクになる', function () {
    $this->blade('<x-ui.button href="/categories/create">New category</x-ui.button>')
        ->assertSee('<a href="/categories/create"', false)
        ->assertDontSee('<button', false);
});

test('variant ごとに見た目を変える', function (string $variant, string $class) {
    $this->blade('<x-ui.button :variant="$variant">Go</x-ui.button>', ['variant' => $variant])
        ->assertSee($class, false);
})->with([
    'primary は黒' => ['primary', 'bg-ink'],
    'secondary は白に枠' => ['secondary', 'border-neutral-300'],
    'ghost は背景なし' => ['ghost', 'text-neutral-600'],
    'danger は赤' => ['danger', 'bg-rose-600'],
    'danger-ghost は赤い文字だけ' => ['danger-ghost', 'text-rose-600'],
]);

test('押しやすい高さを確保する。sm は一覧の中用に小さくする', function () {
    // design-guide.md 4.4: 押せる領域は 44px 以上(PC では 40px まで)
    $this->blade('<x-ui.button>Go</x-ui.button>')->assertSee('min-h-11', false)->assertSee('lg:min-h-10', false);
    $this->blade('<x-ui.button size="sm">Go</x-ui.button>')->assertSee('h-8', false)->assertDontSee('min-h-11', false);
});

test('押せないボタンは disabled にする', function () {
    $this->blade('<x-ui.button disabled>Retry weak (0)</x-ui.button>')
        ->assertSee('disabled', false);
});

test('押せないリンクは、リンクにしない', function () {
    // 苦手問題が0問のときの「Retry weak」など(screens.md 2.6)
    $this->blade('<x-ui.button href="/x" disabled>Retry weak (0)</x-ui.button>')
        ->assertSee('aria-disabled="true"', false)
        ->assertDontSee('href=', false);
});

test('追加のクラスや属性を渡せる', function () {
    $this->blade('<x-ui.button class="w-full" data-test="go">Go</x-ui.button>')
        ->assertSee('w-full', false)
        ->assertSee('data-test="go"', false);
});

test('カードは白背景・薄い枠・角丸で、内側に余白がある', function () {
    $this->blade('<x-ui.card>Networking</x-ui.card>')
        ->assertSee('<div', false)
        ->assertSee('border-neutral-200', false)
        ->assertSee('rounded-xl', false)
        ->assertSee('p-4', false);
});

test('href を渡すと、カード全体がリンクになる', function () {
    $this->blade('<x-ui.card href="/categories/1">Networking</x-ui.card>')
        ->assertSee('<a href="/categories/1"', false)
        ->assertSee('hover:border-neutral-400', false);
});

test('余白なしのカードにできる', function () {
    $this->blade('<x-ui.card :padding="false">rows</x-ui.card>')
        ->assertDontSee('p-4', false);
});
