{{-- セクションの作成(screens.md 2.5) --}}
<x-layouts.app title="New section" width="form">
    <x-ui.breadcrumb :items="[
        ['label' => 'Home', 'href' => route('home')],
        ['label' => $category->name, 'href' => route('categories.show', $category)],
        ['label' => 'New section', 'href' => route('categories.sections.create', $category)],
    ]" />

    <x-ui.page-header title="New section" />

    @include('sections._form', [
        'section' => null,
        'action' => route('categories.sections.store', $category),
        'method' => 'POST',
        'submitLabel' => 'Create',
        'cancelHref' => route('categories.show', $category),
    ])
</x-layouts.app>
