{{--
    点数のバッジ(design-guide.md 2.3 / 7.4)。
    80〜100点: 緑 / 60〜79点: 黄 / 0〜59点(苦手): 赤。点数がまだなければ「—」。
    赤の区切りは苦手の基準点(config の quiz.weak_threshold)と同じ値。80点の区切りは見た目だけのもの。
    平均点のような小数は、四捨五入して整数で出す。
--}}
@props([
    'score' => null,
])

@php
    $rounded = $score === null ? null : (int) round($score);

    $tone = match (true) {
        $rounded === null => 'neutral',
        $rounded >= 80 => 'emerald',
        $rounded >= config('quiz.weak_threshold') => 'amber',
        default => 'rose',
    };
@endphp

<x-ui.badge :tone="$tone" {{ $attributes->class('min-w-10 justify-center tabular-nums') }}>{{ $rounded ?? '—' }}</x-ui.badge>
