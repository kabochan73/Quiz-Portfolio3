{{-- カテゴリの編集(screens.md 2.3) --}}
<x-layouts.app title="Edit category" width="form">
    <x-ui.breadcrumb :items="[
        ['label' => 'Home', 'href' => route('home')],
        ['label' => $category->name, 'href' => route('categories.show', $category)],
        ['label' => 'Edit', 'href' => route('categories.edit', $category)],
    ]" />

    <x-ui.page-header title="Edit category" />

    @include('categories._form', [
        'action' => route('categories.update', $category),
        'method' => 'PUT',
        'submitLabel' => 'Save',
        'cancelHref' => route('categories.show', $category),
    ])
</x-layouts.app>
