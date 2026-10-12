{{--
    前回比(design-guide.md 7.4、requirements.md 3.4)。点数バッジの右に小さく出す。
    ▲16(緑)/ ▼8(赤)/ ±0(グレー)。前回の点数がなければ(初回)「New」。
    平均点の前回比のような小数は、それぞれ四捨五入してから差を出す(画面に出す点数どうしの差と合うように)。
--}}
@props([
    'current',
    'previous' => null,
])

@php
    $delta = $previous === null ? null : (int) round($current) - (int) round($previous);

    [$text, $color] = match (true) {
        $delta === null => ['New', 'text-neutral-500'],
        $delta > 0 => ['▲'.$delta, 'text-emerald-600'],
        $delta < 0 => ['▼'.abs($delta), 'text-rose-600'],
        default => ['±0', 'text-neutral-500'],
    };
@endphp

<span {{ $attributes->class(['text-xs font-medium tabular-nums', $color]) }}>{{ $text }}</span>
