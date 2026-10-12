{{--
    エラーページの中身(screens.md 4.2)。サイドバーのない簡易レイアウトに、見出し・説明・戻るボタンを置く。
    エラーの詳しい理由は出さない(ログで確認する)。
--}}
@props([
    'code',
    'title',
    'message' => null,
])

<x-layouts.minimal :title="$title">
    <x-ui.card class="text-center">
        <p class="text-sm font-medium text-neutral-500 tabular-nums">{{ $code }}</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-900">{{ $title }}</h1>

        @if ($message !== null)
            <p class="mt-2 text-sm text-neutral-600">{{ $message }}</p>
        @endif

        <x-ui.button :href="route('categories.index')" class="mt-6 w-full">Back to categories</x-ui.button>
    </x-ui.card>
</x-layouts.minimal>
