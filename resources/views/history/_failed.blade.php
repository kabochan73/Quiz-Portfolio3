{{-- 採点失敗(screens.md 2.10 (c))。回答は保存されているので、入力し直さずに再採点できる。失敗の理由は出さない --}}
<x-ui.page-header title="Grading failed" />

<p class="mb-6 text-sm text-neutral-600">
    Something went wrong while grading. Your answers are saved, so you can regrade without typing them again.
</p>

<div class="mb-8 flex flex-wrap gap-2">
    <form method="POST" action="{{ route('attempts.regrade', $attempt) }}">
        @csrf
        <x-ui.button type="submit">Regrade</x-ui.button>
    </form>
    <x-ui.button variant="secondary" :href="route('sections.show', $section)">Back to section</x-ui.button>
</div>

<h2 class="mb-3 text-lg font-semibold text-neutral-900">Your answers ({{ $attempt->answers->count() }})</h2>

<div class="space-y-3">
    @foreach ($attempt->answers as $answer)
        <x-ui.card :padding="false" x-data="{ open: false }">
            <button
                type="button"
                class="flex min-h-12 w-full items-center gap-3 px-4 py-3 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink lg:px-5"
                x-on:click="open = ! open"
                x-bind:aria-expanded="open"
            >
                <span class="shrink-0 text-sm font-medium text-neutral-900">Question {{ $loop->iteration }}</span>
                <span class="min-w-0 flex-1 truncate text-sm text-neutral-500" lang="ja">{{ Str::squish($answer->question->body) }}</span>
                <x-icon name="chevron-down" class="size-4 text-neutral-400 transition-transform duration-200" x-bind:class="open && 'rotate-180'" />
            </button>
            <div x-cloak x-show="open" x-collapse>
                <div class="space-y-4 border-t border-neutral-200 px-4 py-4 lg:px-5">
                    <p class="text-body whitespace-pre-wrap text-neutral-900" lang="ja">{{ $answer->question->body }}</p>
                    <div>
                        <p class="mb-1 text-xs font-medium text-neutral-500">Your answer</p>
                        <blockquote class="text-body border-l-2 border-neutral-200 pl-3 whitespace-pre-wrap text-neutral-700" lang="ja">{{ $answer->body }}</blockquote>
                    </div>
                </div>
            </div>
        </x-ui.card>
    @endforeach
</div>
