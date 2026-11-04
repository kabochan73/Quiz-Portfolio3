{{-- 採点中(screens.md 2.10 (a))。結果のカードの形を先に見せ、3秒ごとに状態を確かめる --}}
<div x-data="attemptStatus({ statusUrl: @js(route('attempts.status', $attempt)) })">
    <x-ui.page-header title="Grading…" />

    <div class="mb-8 space-y-1 text-sm text-neutral-600">
        <p>AI is checking {{ $attempt->answers->count() }} {{ Str::plural('answer', $attempt->answers->count()) }}. This can take about a minute.</p>
        <p>You can leave this page. Grading will continue.</p>
    </div>

    <p x-cloak x-show="timedOut" role="status" class="mb-6 text-sm text-neutral-900">
        This is taking longer than usual. Please reload the page later.
    </p>

    <div class="space-y-3">
        @foreach ($attempt->answers as $answer)
            <x-ui.card class="space-y-3">
                <div class="flex items-center justify-between">
                    <x-ui.skeleton class="h-4 w-24" />
                    <x-ui.skeleton class="h-5 w-10 rounded-full" />
                </div>
                <x-ui.skeleton class="h-4 w-full" />
                <x-ui.skeleton class="h-4 w-2/3" />
            </x-ui.card>
        @endforeach
    </div>
</div>
