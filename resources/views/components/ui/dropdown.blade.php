{{--
    「…」メニュー(design-guide.md 7.10)。編集・削除などの副操作を、ボタン1つの中にまとめる。
    一覧の各行や詳細画面の右上に置き、画面を操作ボタンで埋めないようにする。
    中身には dropdown-item を並べる。外側のクリック・Esc キー・項目の選択で閉じる。
--}}
@props([
    'label' => 'More actions',
])

<div x-data="{ open: false }" x-on:keydown.escape="open = false" x-on:click.outside="open = false" {{ $attributes->class('relative') }}>
    <button
        type="button"
        class="flex size-11 items-center justify-center rounded-lg text-neutral-500 transition-colors duration-150 hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink lg:size-10"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open"
        aria-haspopup="menu"
    >
        <x-icon name="ellipsis-horizontal" :label="$label" />
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity.duration.150ms
        x-on:click="open = false"
        role="menu"
        class="absolute right-0 z-30 mt-1 w-40 rounded-xl border border-neutral-200 bg-white p-1 shadow-lg"
    >
        {{ $slot }}
    </div>
</div>
