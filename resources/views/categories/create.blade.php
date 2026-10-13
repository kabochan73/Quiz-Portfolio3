{{-- カテゴリの作成(screens.md 2.3) --}}
<x-layouts.app title="New category" width="form">
    <x-ui.breadcrumb :items="[
        ['label' => 'Home', 'href' => route('home')],
        ['label' => 'New category', 'href' => route('categories.create')],
    ]" />

    <x-ui.page-header title="New category" />

    @include('categories._form', [
        'category' => null,
        'action' => route('categories.store'),
        'method' => 'POST',
        'submitLabel' => 'Create',
        'cancelHref' => route('home'),
    ])
</x-layouts.app>
