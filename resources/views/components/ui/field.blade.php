{{--
    入力欄の外枠(design-guide.md 7.2)。ラベル・入力欄・エラー・文字数をまとめて並べる。
    input / textarea / select の中で使う。ラベルは入力欄の上に置く(プレースホルダだけで済ませない)。
    error: 入力欄の直下に赤で出すメッセージ。counter: 右下に出す文字数の表示(textarea で使う)。
--}}
@props([
    'label',
    'for',
    'error' => null,
    'counter' => null,
])

<div {{ $attributes->class('space-y-1.5') }}>
    <label for="{{ $for }}" class="block text-sm font-medium text-neutral-900">{{ $label }}</label>

    {{ $slot }}

    @if ($error !== null || $counter !== null)
        <div class="flex items-start justify-between gap-3">
            @if ($error !== null)
                {{-- 入力欄の aria-describedby から、このメッセージを読み上げる --}}
                <p id="{{ $for }}-error" class="text-sm text-rose-600">{{ $error }}</p>
            @endif

            @if ($counter !== null)
                <p class="ml-auto text-xs text-neutral-500 tabular-nums">{{ $counter }}</p>
            @endif
        </div>
    @endif
</div>
