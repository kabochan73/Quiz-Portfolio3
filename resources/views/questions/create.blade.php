{{-- 問題の追加(screens.md 2.7)。追加したあとは、続けて追加しやすいようセクション詳細に戻る --}}
<x-layouts.app title="Add question" width="form">
    <x-ui.breadcrumb :items="[
        ['label' => 'Home', 'href' => route('home')],
        ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
        ['label' => $section->name, 'href' => route('sections.show', $section)],
        ['label' => 'Add question', 'href' => route('sections.questions.create', $section)],
    ]" />

    <x-ui.page-header title="Add question" />

    @include('questions._form', [
        'question' => null,
        'action' => route('sections.questions.store', $section),
        'method' => 'POST',
        'submitLabel' => 'Add',
        'cancelHref' => route('sections.show', $section),
    ])
</x-layouts.app>
