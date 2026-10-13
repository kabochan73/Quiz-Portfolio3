{{--
    セクション詳細(screens.md 2.6)。よく使う画面。
    上に回答・再挑戦・履歴のボタン、下に問題の一覧を並べる。点数は履歴だけで見るので、問題の一覧には出さない(2026-10-13 決定)。
--}}
@php
    $count = $questions->count();
    $isFull = $count >= $maxQuestions;
    $plural = fn (int $n, string $noun) => $n.' '.Str::plural($noun, $n);
@endphp

<x-layouts.app :title="$section->name">
    <x-ui.breadcrumb :items="[
        ['label' => 'Home', 'href' => route('home')],
        ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
        ['label' => $section->name, 'href' => route('sections.show', $section)],
    ]" />

    <x-ui.page-header :title="$section->name">
        <x-slot:actions>
            <x-ui.dropdown>
                <x-ui.dropdown-item :href="route('sections.edit', $section)">Edit</x-ui.dropdown-item>
                <x-ui.dropdown-item variant="danger" x-on:click="$dispatch('open-modal', 'delete-section')">Delete</x-ui.dropdown-item>
            </x-ui.dropdown>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="space-y-8">
        {{-- 問題が0問のときは、操作のエリアを出さない(screens.md 2.6) --}}
        @if ($count > 0)
            <x-ui.card class="flex flex-wrap items-center gap-2">
                <x-ui.button :href="route('sections.answers.create', [$section, 'mode' => 'all'])">
                    Answer all
                </x-ui.button>
                <x-ui.button variant="secondary" :href="route('sections.answers.create', [$section, 'mode' => 'weak'])" :disabled="$weakCount === 0">
                    Retry weak ({{ $weakCount }})
                </x-ui.button>
                <x-ui.button variant="ghost" :href="route('sections.history.index', $section)">History</x-ui.button>
            </x-ui.card>
        @endif

        <section>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-neutral-900">Questions</h2>
                <x-ui.button variant="secondary" size="sm" :href="route('sections.questions.create', $section)" :disabled="$isFull">
                    <x-icon name="plus" class="size-4" />
                    Add question
                </x-ui.button>
            </div>

            @if ($isFull)
                <p class="mb-3 text-xs text-neutral-500">This section has the maximum of {{ $maxQuestions }} questions.</p>
            @endif

            @if ($count === 0)
                <x-ui.empty-state message="No questions yet." action="Add question" :action-href="route('sections.questions.create', $section)" />
            @else
                <x-ui.card :padding="false" class="divide-y divide-neutral-200 overflow-hidden">
                    @foreach ($questions as $question)
                        {{-- 行を押すと問題の編集へ。削除も編集画面から行う(問題の詳細の画面はない。2026-10-13 決定) --}}
                        <a
                            href="{{ route('questions.edit', $question) }}"
                            class="flex min-h-14 items-center gap-3 px-4 py-3 transition-colors duration-150 hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:outline-none lg:px-5"
                        >
                            <span class="w-5 shrink-0 text-sm text-neutral-500 tabular-nums">{{ $loop->iteration }}</span>
                            {{--
                                問題文の先頭40文字(screens.md 2.6)。Str::limit() は表示幅で数え、全角を2文字として扱うので使わない。
                                改行は1行に収めるため空白にする
                            --}}
                            <span class="min-w-0 flex-1 truncate text-sm text-neutral-900" lang="ja">{{ Str::length($question->body) > 40 ? Str::substr(Str::squish($question->body), 0, 40).'…' : Str::squish($question->body) }}</span>
                            <x-icon name="chevron-right" class="size-4 text-neutral-400" />
                        </a>
                    @endforeach
                </x-ui.card>
            @endif
        </section>
    </div>

    {{-- 削除の確認。一緒に消えるデータの件数を出す(requirements.md 5.3) --}}
    <x-ui.confirm-modal name="delete-section" title="Delete this section?" :action="route('sections.destroy', $section)">
        This will also delete {{ $plural($deletionCounts['questions'], 'question') }} and {{ $plural($deletionCounts['attempts'], 'attempt') }}.
    </x-ui.confirm-modal>

</x-layouts.app>
