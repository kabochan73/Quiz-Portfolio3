{{--
    履歴一覧(screens.md 2.11)。挑戦を新しい順に20件ずつ並べ、各行から結果画面へ移る。
    採点が終わっていない挑戦は、平均点の代わりに状態のバッジを出す。
--}}
<x-layouts.app title="History">
    <x-ui.breadcrumb :items="[
        ['label' => 'Home', 'href' => route('home')],
        ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
        ['label' => $section->name, 'href' => route('sections.show', $section)],
        ['label' => 'History', 'href' => route('sections.history.index', $section)],
    ]" />

    <x-ui.page-header title="History">
        <x-slot:description>{{ $total }} {{ Str::plural('attempt', $total) }}</x-slot:description>
    </x-ui.page-header>

    @if ($attempts->isEmpty())
        <x-ui.empty-state message="No attempts yet." action="Answer all" :action-href="route('sections.answers.create', [$section, 'mode' => 'all'])" />
    @else
        <x-ui.card :padding="false" class="divide-y divide-neutral-200 overflow-hidden">
            @foreach ($attempts as $attempt)
                <a
                    href="{{ route('sections.history.show', [$section, $attempt]) }}"
                    class="flex min-h-14 items-center gap-3 px-4 py-3 text-sm transition-colors duration-150 hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:outline-none lg:px-5"
                >
                    <x-ui.datetime :value="$attempt->created_at" format="long" class="w-24 shrink-0 text-neutral-900" />
                    <span class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-1 text-neutral-500">
                        <x-ui.badge>{{ $attempt->mode->label() }}</x-ui.badge>
                        <span>{{ $attempt->grading_level->label() }}</span>
                        <span class="tabular-nums">{{ $attempt->answers_count }} {{ Str::plural('question', $attempt->answers_count) }}</span>
                    </span>

                    @if ($attempt->status === \App\Enums\AttemptStatus::Completed)
                        <x-ui.score-badge :score="$averages[$attempt->id] ?? null" />
                    @else
                        <x-ui.status-badge :status="$attempt->status" />
                    @endif
                    <x-icon name="chevron-right" class="size-4 text-neutral-400" />
                </a>
            @endforeach
        </x-ui.card>

        {{-- 21件以上あるときだけ出す。ブラウザの「戻る」と相性のよいページ送りにする(screens.md 2.11) --}}
        @if ($attempts->hasPages())
            <nav class="mt-6 flex justify-between gap-2" aria-label="Pagination">
                <x-ui.button variant="secondary" :href="$attempts->previousPageUrl() ?? route('sections.history.index', $section)" :disabled="$attempts->onFirstPage()">Newer</x-ui.button>
                <x-ui.button variant="secondary" :href="$attempts->nextPageUrl() ?? route('sections.history.index', $section)" :disabled="! $attempts->hasMorePages()">Older</x-ui.button>
            </nav>
        @endif
    @endif
</x-layouts.app>
