{{--
    ボタン(design-guide.md 7.1)。
    href を渡すとリンク(<a>)、渡さないと <button> として出す。
    variant: primary(画面の主操作。原則1画面に1つ)/ secondary(副操作)/ ghost(控えめな操作)/ danger(確認モーダル内の削除だけ)
    size: md(押しやすい高さ 44px、PC では 40px)/ sm(32px。一覧の中など)
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'disabled' => false,
])

@php
    $classes = [
        'inline-flex items-center justify-center gap-2 rounded-lg text-sm font-medium whitespace-nowrap',
        'transition-colors duration-150',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2',
        match ($size) {
            'sm' => 'h-8 px-3',
            default => 'min-h-11 px-4 lg:min-h-10',
        },
        match ($variant) {
            'secondary' => 'border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-100',
            'ghost' => 'text-neutral-600 hover:bg-neutral-100',
            'danger' => 'bg-rose-600 text-white hover:bg-rose-700',
            default => 'bg-ink text-white hover:bg-ink-hover',
        },
        // 押せないときは薄くし、ホバーの色の変化も止める
        'pointer-events-none opacity-50' => $disabled,
    ];
@endphp

@if ($href !== null && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@elseif ($href !== null)
    {{-- 押せないリンクは、リンクにしない(キーボードでも選べないように) --}}
    <span aria-disabled="true" {{ $attributes->class($classes) }}>{{ $slot }}</span>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
