{{--
    サイドバー(screens.md 1.1)。PC では左に固定し、スマホではドロワーの中に出す。
    $sidebarCategories / $currentCategoryId は SidebarComposer が渡す。
    今いるページは、薄いグレーの背景と黒の太字で強調する(design-guide.md 2.2)。
--}}
@php
    $linkClasses = fn(bool $active) => [
        'flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm transition-colors duration-150',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink',
        'bg-neutral-100 font-semibold text-neutral-900' => $active,
        'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900' => !$active,
    ];
@endphp

<div class="flex h-full flex-col">
    <div class="flex h-14 items-center px-6">
        <a href="{{ route('home') }}"
            class="text-2xl font-semibold tracking-tight text-neutral-900">{{ config('app.name') }}</a>
    </div>

    {{-- ホームへは、上の Recollect(ロゴ)から移る。役割が重なるので Home のリンクは置かない(2026-10-13 決定) --}}
    <nav class="flex-1 overflow-y-auto px-4 pb-2" aria-label="Main">
        <div>
            <div class="mb-1 flex items-center justify-between pr-1 pl-3 border-b border-neutral-200">
                {{-- カテゴリの一覧の画面はないので、見出しはリンクにしない --}}
                <p class="text-sm font-medium tracking-wide text-neutral-700 uppercase">
                    Categories</p>
                <a href="{{ route('categories.create') }}"
                    class="flex size-8 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink">
                    <x-icon name="plus" label="New category" class="size-4" />
                </a>
            </div>

            <ul class="space-y-0.5">
                @foreach ($sidebarCategories as $category)
                    <li>
                        <a href="{{ route('categories.show', $category) }}" @class($linkClasses($category->id === $currentCategoryId))
                            @if ($category->id === $currentCategoryId) aria-current="page" @endif>
                            <span class="truncate">{{ $category->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>

    <div class="border-t border-neutral-200 p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" @class([...$linkClasses(false), 'w-full'])>
                <x-icon name="arrow-right-start-on-rectangle" />
                Log out
            </button>
        </form>
    </div>
</div>
