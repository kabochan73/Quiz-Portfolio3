{{-- 問題の編集(screens.md 2.7)。変えられるのは問題文だけ。保存・キャンセルのあとはセクション詳細へ戻る --}}
<x-layouts.app title="Edit question" width="form">
    <x-ui.breadcrumb :items="[
        ['label' => 'Home', 'href' => route('home')],
        ['label' => $question->section->category->name, 'href' => route('categories.show', $question->section->category)],
        ['label' => $question->section->name, 'href' => route('sections.show', $question->section)],
        ['label' => 'Edit question '.$number, 'href' => route('questions.edit', $question)],
    ]" />

    <x-ui.page-header title="Edit question" />

    @include('questions._form', [
        'action' => route('questions.update', $question),
        'method' => 'PUT',
        'submitLabel' => 'Save',
        'cancelHref' => route('sections.show', $question->section),
    ])
</x-layouts.app>
