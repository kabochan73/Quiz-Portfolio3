<?php

use Illuminate\View\ViewException;

// アイコン・パンくず・ページ見出し(design-guide.md 6章 / 7.7、screens.md 1.3)

test('アイコンは SVG を埋め込み、文字色に合わせる', function () {
    $this->blade('<x-icon name="plus" />')
        ->assertSee('<svg', false)
        ->assertSee('stroke="currentColor"', false)
        ->assertSee('size-5', false);
});

test('飾りのアイコンは読み上げない。意味のあるアイコンにはラベルを付ける', function () {
    // design-guide.md 6章: アイコンだけで意味を伝える場合は aria-label を付ける
    $this->blade('<x-icon name="plus" />')->assertSee('aria-hidden="true"', false);
    $this->blade('<x-icon name="bars-3" label="Open menu" />')
        ->assertSee('role="img"', false)
        ->assertSee('aria-label="Open menu"', false)
        ->assertDontSee('aria-hidden', false);
});

test('知らないアイコンの名前はエラーにする', function () {
    $this->blade('<x-icon name="unknown" />');
})->throws(ViewException::class, 'Unknown icon: unknown');

test('パンくずは、最後の項目(現在地)だけリンクにしない', function () {
    $this->blade('<x-ui.breadcrumb :items="$items" />', ['items' => [
        ['label' => 'Categories', 'href' => '/categories'],
        ['label' => '基本情報', 'href' => '/categories/1'],
        ['label' => 'ネットワーク', 'href' => '/sections/1'],
    ]])
        ->assertSeeInOrder(['href="/categories"', 'Categories', 'href="/categories/1"', '基本情報', 'aria-current="page"', 'ネットワーク'], false)
        ->assertDontSee('href="/sections/1"', false);
});

test('スマホでは途中を省き、「…」から1つ前に戻れる', function () {
    // design-guide.md 7.7
    $this->blade('<x-ui.breadcrumb :items="$items" />', ['items' => [
        ['label' => 'Categories', 'href' => '/categories'],
        ['label' => '基本情報', 'href' => '/categories/1'],
        ['label' => 'ネットワーク', 'href' => '/sections/1'],
    ]])
        ->assertSee('hidden min-w-0 items-center gap-1.5 sm:flex', false)
        ->assertSee('aria-label="Back to 基本情報"', false);
});

test('パンくずが1項目だけなら、「…」は出さない', function () {
    $this->blade('<x-ui.breadcrumb :items="$items" />', ['items' => [['label' => 'Categories', 'href' => '/categories']]])
        ->assertDontSee('…')
        ->assertSee('aria-current="page"', false);
});

test('ページ見出しに、右側の操作と下の補足を置ける', function () {
    $this->blade(<<<'BLADE'
        <x-ui.page-header title="ネットワーク">
            <x-slot:actions><button>Edit</button></x-slot:actions>
            <x-slot:description>8 / 10 questions</x-slot:description>
        </x-ui.page-header>
        BLADE)
        ->assertSeeInOrder(['<h1', 'ネットワーク', '</h1>', 'Edit', '8 / 10 questions'], false);
});

test('ページ見出しの操作と補足は、なくてもよい', function () {
    $this->blade('<x-ui.page-header title="Categories" />')
        ->assertSee('Categories')
        ->assertDontSee('<p', false);
});

test('アイコンの大きさを渡したら、既定の大きさは付けない', function () {
    $this->blade('<x-icon name="plus" class="size-4" />')
        ->assertSee('size-4', false)
        ->assertDontSee('size-5', false);
});
