{{--
    カード(design-guide.md 7.3)。白背景・薄い枠・ごく薄い影。
    href を渡すと、カード全体がリンクになり、ホバーで枠が少し濃くなる。
    padding=false にすると内側の余白をなくす(行を並べる一覧のカードなど)。
--}}
@props([
    'href' => null,
    'padding' => true,
])

@php
    $classes = [
        'block rounded-xl border border-neutral-200 bg-white shadow-xs',
        'p-4 lg:p-5' => $padding,
        'transition-colors duration-150 hover:border-neutral-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2' => $href !== null,
    ];
@endphp

@if ($href !== null)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <div {{ $attributes->class($classes) }}>{{ $slot }}</div>
@endif
