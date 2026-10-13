{{--
    採点結果 = 履歴詳細(screens.md 2.10)。採点の直後も、あとから履歴で開いたときも同じ画面。
    採点の状態によって、採点中(_grading)/ 完了(_completed)/ 失敗(_failed)の表示を切り替える。
--}}
@use('App\Enums\AttemptStatus')
@use('App\Support\DisplayTime')

@php
    $title = match (true) {
        $attempt->status->isInProgress() => 'Grading…',
        $attempt->status === AttemptStatus::Failed => 'Grading failed',
        default => 'Results',
    };
@endphp

<x-layouts.app :title="$title">
    <x-ui.breadcrumb :items="[
        ['label' => 'Home', 'href' => route('home')],
        ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
        ['label' => $section->name, 'href' => route('sections.show', $section)],
        ['label' => 'History', 'href' => route('sections.history.index', $section)],
        ['label' => DisplayTime::format($attempt->created_at, 'long'), 'href' => route('sections.history.show', [$section, $attempt])],
    ]" />

    {{-- 送信して結果画面に移ったら、回答フォームの下書きを消す(architecture.md 7章) --}}
    <div x-data="draftCleaner(@js("quiz:draft:section:{$section->id}:{$attempt->mode->value}"))"></div>

    @if ($attempt->status->isInProgress())
        @include('history._grading')
    @elseif ($attempt->status === AttemptStatus::Failed)
        @include('history._failed')
    @else
        @include('history._completed')
    @endif
</x-layouts.app>
