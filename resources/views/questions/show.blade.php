{{--
    問題詳細(screens.md 2.8)。問題文と、最近の点数(採点が終わった直近10回。回答を残す件数と同じ)を出す。
    最近の点数の各行から、その回の結果画面へ移れる。
--}}
<x-layouts.app :title="'Question '.$number">
    <x-ui.breadcrumb :items="[
        ['label' => 'Home', 'href' => route('home')],
        ['label' => $question->section->category->name, 'href' => route('categories.show', $question->section->category)],
        ['label' => $question->section->name, 'href' => route('sections.show', $question->section)],
        ['label' => 'Question '.$number, 'href' => route('questions.show', $question)],
    ]" />

    <x-ui.page-header :title="'Question '.$number">
        <x-slot:actions>
            <x-ui.dropdown>
                <x-ui.dropdown-item :href="route('questions.edit', $question)">Edit</x-ui.dropdown-item>
                <x-ui.dropdown-item variant="danger" x-on:click="$dispatch('open-modal', 'delete-question')">Delete</x-ui.dropdown-item>
            </x-ui.dropdown>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="space-y-8">
        <x-ui.card>
            {{-- 改行はそのまま表示する(design-guide.md 3.2) --}}
            <p class="text-body whitespace-pre-wrap text-neutral-900" lang="ja">{{ $question->body }}</p>
        </x-ui.card>

        <section>
            <h2 class="mb-3 text-lg font-semibold text-neutral-900">Recent scores</h2>

            @if ($recentAnswers->isEmpty())
                <x-ui.empty-state message="Not answered yet." />
            @else
                <x-ui.card :padding="false" class="divide-y divide-neutral-200 overflow-hidden">
                    @foreach ($recentAnswers as $answer)
                        <a
                            href="{{ route('sections.history.show', [$question->section, $answer->attempt]) }}"
                            class="flex min-h-12 items-center gap-4 px-4 py-2.5 text-sm transition-colors duration-150 hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:outline-none lg:px-5"
                        >
                            <x-ui.datetime :value="$answer->attempt->created_at" class="w-14 shrink-0 text-neutral-500" />
                            <x-ui.score-badge :score="$answer->score->score" />
                            <span class="flex-1 text-neutral-600">{{ $answer->attempt->grading_level->label() }}</span>
                            <x-icon name="chevron-right" class="size-4 text-neutral-400" />
                        </a>
                    @endforeach
                </x-ui.card>
            @endif
        </section>
    </div>

    {{-- 削除の確認。回答が0件になった挑戦も一緒に消える(architecture.md 4.6) --}}
    <x-ui.confirm-modal name="delete-question" title="Delete this question?" :action="route('questions.destroy', $question)">
        This will also delete {{ $answerCount }} {{ Str::plural('answer', $answerCount) }}.
    </x-ui.confirm-modal>
</x-layouts.app>
