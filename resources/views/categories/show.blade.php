{{--
    カテゴリ詳細(screens.md 2.4)。セクションを1枚のカードの中に行として並べ、各行に問題数を出す。
--}}
<x-layouts.app :title="$category->name">
    <x-ui.breadcrumb :items="[
        ['label' => 'Home', 'href' => route('home')],
        ['label' => $category->name, 'href' => route('categories.show', $category)],
    ]" />

    <x-ui.page-header :title="$category->name">
        <x-slot:actions>
            <x-ui.dropdown>
                <x-ui.dropdown-item :href="route('categories.edit', $category)">Edit</x-ui.dropdown-item>
                <x-ui.dropdown-item variant="danger" x-on:click="$dispatch('open-modal', 'delete-category')">Delete</x-ui.dropdown-item>
            </x-ui.dropdown>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-neutral-900">Sections</h2>
        <x-ui.button variant="secondary" size="sm" :href="route('categories.sections.create', $category)">
            <x-icon name="plus" class="size-4" />
            Add section
        </x-ui.button>
    </div>

    @if ($sections->isEmpty())
        <x-ui.empty-state message="No sections yet." action="Add section" :action-href="route('categories.sections.create', $category)" />
    @else
        <x-ui.card :padding="false" class="divide-y divide-neutral-200 overflow-hidden">
            @foreach ($sections as $section)
                <a
                    href="{{ route('sections.show', $section) }}"
                    class="flex min-h-14 items-center gap-3 px-4 py-3 transition-colors duration-150 hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:outline-none lg:px-5"
                >
                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-neutral-900">{{ $section->name }}</span>
                    <span class="shrink-0 text-xs text-neutral-500 tabular-nums">
                        {{ $section->questions_count }} {{ Str::plural('question', $section->questions_count) }}
                    </span>
                    <x-icon name="chevron-right" class="size-4 text-neutral-400" />
                </a>
            @endforeach
        </x-ui.card>
    @endif

    {{-- 削除の確認。一緒に消えるデータの件数を出す(requirements.md 5.3) --}}
    @php
        $count = fn (string $key, string $noun) => $deletionCounts[$key].' '.Str::plural($noun, $deletionCounts[$key]);
    @endphp
    <x-ui.confirm-modal name="delete-category" title="Delete this category?" :action="route('categories.destroy', $category)">
        This will also delete {{ $count('sections', 'section') }}, {{ $count('questions', 'question') }}, and {{ $count('attempts', 'attempt') }}.
    </x-ui.confirm-modal>
</x-layouts.app>
