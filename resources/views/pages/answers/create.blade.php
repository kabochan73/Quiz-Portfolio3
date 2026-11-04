{{--
    回答フォーム(screens.md 2.9)。全問モードと苦手モードで同じ画面を使う。
    進捗・下書きの保存と復元・送信前のチェックは resources/js/answer-form.js。
--}}
@php
    $isWeak = $mode === \App\Enums\AttemptMode::Weak;
    $title = $isWeak ? 'Retry weak questions' : 'Answer all';
    $total = $questions->count();
    // 下書きのキー(architecture.md 7章)。結果画面でも同じ形で組み立てて消す
    $storageKey = "quiz:draft:section:{$section->id}:{$mode->value}";
@endphp

<x-layouts.app :title="$title">
    <x-ui.breadcrumb :items="[
        ['label' => 'Home', 'href' => route('home')],
        ['label' => $section->category->name, 'href' => route('categories.show', $section->category)],
        ['label' => $section->name, 'href' => route('sections.show', $section)],
        ['label' => $title, 'href' => route('sections.answers.create', [$section, 'mode' => $mode->value])],
    ]" />

    <form
        method="POST"
        action="{{ route('sections.answers.store', $section) }}"
        x-data="answerForm({ storageKey: @js($storageKey), defaultGradingLevel: @js($defaultGradingLevel->value) })"
        x-on:input="onInput($event)"
        x-on:submit="submit($event)"
        {{-- スマホでは送信ボタンを画面の下に固定するので、その分の余白をあける --}}
        class="pb-24 lg:pb-0"
    >
        @csrf
        <input type="hidden" name="mode" value="{{ $mode->value }}">

        <x-ui.page-header :title="$title">
            <x-slot:actions>
                <div class="flex flex-col items-end gap-0.5 text-right">
                    <p class="text-sm text-neutral-700 tabular-nums"><span x-text="answeredCount">0</span> / {{ $total }} answered</p>
                    <p class="flex h-5 items-center gap-1 text-xs text-neutral-500" x-cloak x-show="savedVisible" x-transition.opacity>
                        <x-icon name="check" class="size-3.5" />
                        Draft saved
                    </p>
                </div>
            </x-slot:actions>

            @if ($isWeak)
                <x-slot:description>{{ $total }} {{ Str::plural('question', $total) }} scored under {{ $weakThreshold }} last time.</x-slot:description>
            @endif
        </x-ui.page-header>

        <div class="space-y-6">
            {{-- 開いたあとに問題が追加・削除されたときなど、入力欄に結びつかないエラー --}}
            @error('answers')
                <x-ui.alert>{{ $message }}</x-ui.alert>
            @enderror

            <div class="flex items-end justify-between gap-4">
                <x-ui.radio-group
                    name="grading_level"
                    label="Grading level"
                    :options="collect($gradingLevels)->mapWithKeys(fn ($level) => [$level->value => $level->label()])->all()"
                    :value="$defaultGradingLevel->value"
                    class="w-full max-w-sm"
                />
                <x-ui.button variant="ghost" size="sm" x-on:click="discardDraft()">Discard draft</x-ui.button>
            </div>

            @foreach ($questions as $question)
                @php $key = $loop->index; @endphp
                <x-ui.card>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-neutral-900">Question {{ $loop->iteration }}</p>
                        <p class="flex items-center gap-1 text-xs font-medium text-emerald-700" x-cloak x-show="isAnswered('{{ $question->id }}')">
                            <x-icon name="check" class="size-3.5" />
                            Answered
                        </p>
                    </div>

                    <p class="text-body mb-4 whitespace-pre-wrap text-neutral-900" lang="ja">{{ $question->body }}</p>

                    <input type="hidden" name="answers[{{ $key }}][question_id]" value="{{ $question->id }}">
                    <x-ui.textarea
                        name="answers[{{ $key }}][body]"
                        label="Your answer"
                        rows="5"
                        maxlength="5000"
                        lang="ja"
                        data-question-id="{{ $question->id }}"
                    />

                    {{-- 送信しようとしたときに、未入力の問題に出す(サーバーのエラーと同じ文言) --}}
                    <p class="mt-1.5 text-sm text-rose-600" x-cloak x-show="showErrors && ! isAnswered('{{ $question->id }}')">Please answer all questions.</p>
                </x-ui.card>
            @endforeach
        </div>

        {{-- スマホでは画面の下に固定する(screens.md 2.9) --}}
        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-neutral-200 bg-white p-4 lg:static lg:mt-8 lg:flex lg:justify-end lg:border-0 lg:bg-transparent lg:p-0">
            <x-ui.button type="submit" class="w-full lg:w-auto" x-bind:disabled="submitting">
                <span x-show="! submitting">Submit ({{ $total }})</span>
                <span x-cloak x-show="submitting">Submitting…</span>
            </x-ui.button>
        </div>
    </form>
</x-layouts.app>
