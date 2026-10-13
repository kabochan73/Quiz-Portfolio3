{{-- セクションの編集(screens.md 2.5)。変えられるのは名前だけ --}}
<x-layouts.app title="Edit section" width="form">
    <x-ui.breadcrumb :items="[
        ['label' => 'Home', 'href' => route('home')],
        ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
        ['label' => $section->name, 'href' => route('sections.show', $section)],
        ['label' => 'Edit', 'href' => route('sections.edit', $section)],
    ]" />

    <x-ui.page-header title="Edit section" />

    @include('sections._form', [
        'action' => route('sections.update', $section),
        'method' => 'PUT',
        'submitLabel' => 'Save',
        'cancelHref' => route('sections.show', $section),
    ])
</x-layouts.app>
