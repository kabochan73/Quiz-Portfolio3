{{--
    ログイン後の画面のレイアウト(screens.md 1章)。
    PC(1024px 以上): 左にサイドバーを固定し、メイン領域だけスクロールする。
    スマホ: 上にヘッダーを固定し、☰ でサイドバーをドロワーとして左から出す。
    width: form(作成・編集。640px)/ wide(一覧・詳細・回答・結果。768px)(design-guide.md 4.2)
--}}
@props([
    'title' => null,
    'width' => 'wide',
])

<!DOCTYPE html>
<html lang="en">
<head>
    <x-layout.head :title="$title" />
</head>
<body x-data="{ drawerOpen: false }" x-on:keydown.escape.window="drawerOpen = false">
    {{-- PC のサイドバー --}}
    <aside class="fixed inset-y-0 left-0 z-20 hidden w-64 border-r border-neutral-200 bg-white lg:block">
        <x-layout.sidebar />
    </aside>

    {{-- スマホのヘッダー --}}
    <header class="sticky top-0 z-30 flex h-14 items-center gap-1 border-b border-neutral-200 bg-white px-2 lg:hidden">
        <button
            type="button"
            class="flex size-11 items-center justify-center rounded-lg text-neutral-700 hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink"
            x-on:click="drawerOpen = true"
            x-bind:aria-expanded="drawerOpen"
            aria-controls="drawer"
        >
            <x-icon name="bars-3" label="Open menu" />
        </button>
        <a href="{{ route('home') }}" class="text-base font-semibold tracking-tight text-neutral-900">{{ config('app.name') }}</a>
    </header>

    {{-- スマホのドロワー。背景のタップ・Esc キーで閉じる。開いている間はフォーカスを中に閉じ込める(design-guide.md 8章) --}}
    <div x-cloak x-show="drawerOpen" class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
        <div
            class="absolute inset-0 bg-neutral-900/40"
            x-show="drawerOpen"
            x-transition.opacity.duration.200ms
            x-on:click="drawerOpen = false"
        ></div>
        <div
            id="drawer"
            class="absolute inset-y-0 left-0 w-64 bg-white shadow-xl"
            x-show="drawerOpen"
            x-trap.noscroll="drawerOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="-translate-x-full"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-end="-translate-x-full"
        >
            <button
                type="button"
                class="absolute top-1.5 right-1.5 flex size-11 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink"
                x-on:click="drawerOpen = false"
            >
                <x-icon name="x-mark" label="Close menu" />
            </button>
            <x-layout.sidebar />
        </div>
    </div>

    <main class="lg:pl-64">
        <div @class([
            'mx-auto px-4 py-6 lg:px-8 lg:py-10',
            'max-w-2xl' => $width === 'form',
            'max-w-3xl' => $width !== 'form',
        ])>
            {{ $slot }}
        </div>
    </main>
</body>
</html>
