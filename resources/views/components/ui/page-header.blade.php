{{--
    ページ見出し(screens.md 1.3、design-guide.md 3.2)。
    左に見出し、右に主ボタンか「…」メニュー(actions)、見出しの下に件数などの補足(description)を置く。
--}}
@props([
    'title',
])

<div {{ $attributes->class('mb-6') }}>
    <div class="flex items-start justify-between gap-4">
        <h1 class="min-w-0 text-2xl font-semibold tracking-tight break-words text-neutral-900">{{ $title }}</h1>

        @isset($actions)
            <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    @isset($description)
        <p class="mt-1 text-sm text-neutral-500">{{ $description }}</p>
    @endisset
</div>
