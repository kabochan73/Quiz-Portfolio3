{{--
    問題の編集(screens.md 2.7)。変えられるのは問題文だけ。保存・キャンセル・削除のあとはセクション詳細へ戻る。
    問題の削除は、この画面の Delete から行う(2026-10-13 決定)
--}}
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
        'deleteModal' => 'delete-question',
    ])

    {{-- 削除の確認。回答が0件になった挑戦も一緒に消える(architecture.md 4.6)。フォームの中にフォームは置けないので、ここに置く --}}
    <x-ui.confirm-modal name="delete-question" title="Delete this question?" :action="route('questions.destroy', $question)">
        This will also delete {{ $answerCount }} {{ Str::plural('answer', $answerCount) }}.
    </x-ui.confirm-modal>
</x-layouts.app>
