{{--
    採点完了(screens.md 2.10 (b))。
    平均点と前回比、問題ごとのフィードバック(良かった点 / 悪かった点 / 模範解答)を並べる。
    問題のカードは、最初から全部開いておく(2026-10-13 決定)。見出しの行を押すと閉じられる。
--}}
@php
    $scoreOf = fn ($answer) => $answer->score?->score;
@endphp

<x-ui.page-header title="Results">
    <x-slot:actions>
        <x-ui.badge>{{ $attempt->mode->label() }}</x-ui.badge>
        <x-ui.badge>{{ $attempt->grading_level->label() }}</x-ui.badge>
    </x-slot:actions>
</x-ui.page-header>

<div class="space-y-8">
    {{-- 平均点の右に、もう一度挑戦するボタンを並べる。スマホで幅が足りなければ、ボタンは平均点の下に回る(2026-10-13) --}}
    <x-ui.card class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-neutral-500">Average</p>
            <div class="mt-1 flex items-baseline gap-2">
                <p class="text-4xl font-semibold text-neutral-900 tabular-nums">{{ $average === null ? '—' : (int) round($average) }}</p>
                @if ($average !== null)
                    {{-- 同じ種別(全問 / 苦手)の、ひとつ前の挑戦との差。初回は New --}}
                    <x-ui.score-delta :current="$average" :previous="$previousAverage" class="text-sm" />
                @endif
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button :href="route('sections.answers.create', [$section, 'mode' => 'all'])">Try again</x-ui.button>
            <x-ui.button variant="secondary" :href="route('sections.answers.create', [$section, 'mode' => 'weak'])" :disabled="$weakCount === 0">
                Retry weak ({{ $weakCount }})
            </x-ui.button>
        </div>
    </x-ui.card>

    <div class="space-y-3">
        @foreach ($attempt->answers as $answer)
            @php
                $score = $scoreOf($answer);
            @endphp
            <x-ui.card :padding="false" x-data="{ open: true }">
                <button
                    type="button"
                    class="flex min-h-12 w-full items-center gap-3 px-4 py-3 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink lg:px-5"
                    x-on:click="open = ! open"
                    x-bind:aria-expanded="open"
                >
                    <span class="flex-1 text-sm font-medium text-neutral-900">Question {{ $loop->iteration }}</span>
                    <x-ui.score-badge :score="$score" />
                    <x-ui.score-delta :current="$score ?? 0" :previous="$previousScores[$answer->question_id] ?? null" class="w-10" />
                    <x-icon name="chevron-down" class="size-4 text-neutral-400 transition-transform duration-200" x-bind:class="open && 'rotate-180'" />
                </button>

                <div x-show="open" x-collapse>
                    <div class="space-y-5 border-t border-neutral-200 px-4 py-4 lg:px-5">
                        <p class="text-body whitespace-pre-wrap text-neutral-900" lang="ja">{{ $answer->question->body }}</p>

                        <div>
                            <p class="mb-1 text-xs font-medium text-neutral-500">Your answer</p>
                            <blockquote class="text-body border-l-2 border-neutral-200 pl-3 whitespace-pre-wrap text-neutral-700" lang="ja">{{ $answer->body }}</blockquote>
                        </div>

                        @if ($answer->score)
                            {{-- AI の採点コメントは日本語(requirements.md 5.5) --}}
                            <div>
                                <p class="mb-1 text-sm font-semibold text-neutral-900">Good points</p>
                                <p class="text-body whitespace-pre-wrap" lang="ja">{{ $answer->score->good_points }}</p>
                            </div>
                            <div>
                                <p class="mb-1 text-sm font-semibold text-neutral-900">What went wrong</p>
                                <p class="text-body whitespace-pre-wrap" lang="ja">{{ $answer->score->weak_points }}</p>
                            </div>
                            <div>
                                <p class="mb-1 text-sm font-semibold text-neutral-900">Model answer</p>
                                <blockquote class="text-body border-l-2 border-neutral-200 pl-3 whitespace-pre-wrap text-neutral-700" lang="ja">{{ $answer->score->model_answer }}</blockquote>
                            </div>
                        @endif
                    </div>
                </div>
            </x-ui.card>
        @endforeach
    </div>

    {{-- 使用量は目立たせず、一番下に小さく出す(requirements.md 3.3)。単価が未設定なら、コストは出さない --}}
    <p class="text-xs text-neutral-500 tabular-nums">
        Usage: {{ number_format($attempt->input_tokens ?? 0) }} input / {{ number_format($attempt->output_tokens ?? 0) }} output tokens
        · {{ $attempt->web_search_requests ?? 0 }} {{ Str::plural('search', $attempt->web_search_requests ?? 0) }}@if ($cost !== null) · about ${{ number_format($cost, 2) }}@endif
    </p>
</div>
