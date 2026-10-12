<?php

// 「…」メニューと削除の確認モーダル(design-guide.md 7.6 / 7.10)。開閉の動きはブラウザで確かめる

test('「…」ボタンは、メニューを開くボタンとして読み上げられる', function () {
    $this->blade('<x-ui.dropdown><x-ui.dropdown-item href="/categories/1/edit">Edit</x-ui.dropdown-item></x-ui.dropdown>')
        ->assertSee('aria-haspopup="menu"', false)
        ->assertSee('aria-label="More actions"', false)
        ->assertSee('role="menu"', false);
});

test('メニューは、外側のクリックと Esc キーで閉じる', function () {
    $this->blade('<x-ui.dropdown>items</x-ui.dropdown>')
        ->assertSee('x-on:click.outside="open = false"', false)
        ->assertSee('x-on:keydown.escape="open = false"', false);
});

test('メニューの項目は、リンクにもボタンにもでき、削除は赤くする', function () {
    $this->blade(<<<'BLADE'
        <x-ui.dropdown>
            <x-ui.dropdown-item href="/categories/1/edit">Edit</x-ui.dropdown-item>
            <x-ui.dropdown-item variant="danger" x-on:click="$dispatch('open-modal', 'delete-category')">Delete</x-ui.dropdown-item>
        </x-ui.dropdown>
        BLADE)
        ->assertSeeInOrder(['<a href="/categories/1/edit" role="menuitem"', 'Edit', '<button type="button" role="menuitem"', 'text-rose-600', 'Delete'], false)
        ->assertSee("x-on:click=\"\$dispatch('open-modal', 'delete-category')\"", false);
});

test('確認モーダルは、名前を指定したイベントで開く', function () {
    $this->blade('<x-ui.confirm-modal name="delete-category" title="Delete this category?" action="/categories/1" />')
        ->assertSee("if (\$event.detail === 'delete-category') open = true", false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('aria-labelledby="delete-category-title"', false)
        ->assertSee('Delete this category?');
});

test('確認モーダルの Delete は、フォームの DELETE 送信', function () {
    // requirements.md 3.2: ブラウザ標準の confirm() は使わない
    $this->blade('<x-ui.confirm-modal name="d" title="Delete?" action="/categories/1" />')
        ->assertSeeInOrder(['<form method="POST" action="/categories/1"', 'name="_token"', 'name="_method" value="DELETE"'], false)
        ->assertDontSee('confirm(', false);
});

test('Cancel を先に置き、開いたときに Cancel にフォーカスが移るようにする', function () {
    // design-guide.md 7.6: 誤操作を防ぐため
    $this->blade('<x-ui.confirm-modal name="d" title="Delete?" action="/x" />')
        ->assertSee('x-trap.noscroll="open"', false)
        ->assertSeeInOrder(['Cancel', 'type="submit"', 'bg-rose-600', 'Delete'], false);
});

test('一緒に消えるデータの件数を、説明として書ける', function () {
    $this->blade('<x-ui.confirm-modal name="d" title="Delete this category?" action="/x">This will also delete 3 sections, 24 questions, and 12 attempts.</x-ui.confirm-modal>')
        ->assertSee('This will also delete 3 sections, 24 questions, and 12 attempts.');
});

test('確認モーダルは、Esc キーと背景のクリックで閉じる', function () {
    $this->blade('<x-ui.confirm-modal name="d" title="Delete?" action="/x" />')
        ->assertSee('x-on:keydown.escape.window="open = false"', false)
        ->assertSee('bg-neutral-900/40" x-show="open" x-transition.opacity.duration.200ms x-on:click="open = false"', false);
});
