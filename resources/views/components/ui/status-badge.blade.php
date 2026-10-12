{{--
    採点の状態のバッジ(design-guide.md 2.3 / 7.4)。表示する文言は AttemptStatus::label()(screens.md 4.4)。
    待機中・採点中: 水色 / 完了: 緑 / 失敗: 赤
--}}
@props([
    'status',
])

@php
    $tone = match ($status) {
        \App\Enums\AttemptStatus::Pending, \App\Enums\AttemptStatus::Grading => 'sky',
        \App\Enums\AttemptStatus::Completed => 'emerald',
        \App\Enums\AttemptStatus::Failed => 'rose',
    };
@endphp

<x-ui.badge :tone="$tone" {{ $attributes }}>{{ $status->label() }}</x-ui.badge>
