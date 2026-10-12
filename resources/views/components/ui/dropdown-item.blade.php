{{--
    「…」メニューの1項目(design-guide.md 7.10)。href を渡すとリンク、渡さないとボタン。
    variant=danger は削除用で、赤い文字にする。
--}}
@props([
    'href' => null,
    'variant' => 'default',
])

@php
    $classes = [
        'flex min-h-10 w-full items-center rounded-lg px-3 text-left text-sm transition-colors duration-150',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink',
        'text-rose-600 hover:bg-rose-50' => $variant === 'danger',
        'text-neutral-700 hover:bg-neutral-100' => $variant !== 'danger',
    ];
@endphp

@if ($href !== null)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="button" role="menuitem" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
