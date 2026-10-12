{{--
    複数行の入力欄(design-guide.md 7.2)。問題文・回答に使う。改行はそのまま保存される。
    maxlength を渡すと、右下に「120 / 2000」のように文字数を出し、入力に合わせて更新する(Alpine.js)。
--}}
@props([
    'name',
    'label',
    'id' => null,
    'value' => null,
    'rows' => 5,
    'maxlength' => null,
])

@php
    $id ??= str_replace(['[', ']'], ['-', ''], $name);
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $error = ($errors ?? new \Illuminate\Support\ViewErrorBag)->first($key) ?: null;
    $text = (string) old($key, $value);
@endphp

<div
    @if ($maxlength) x-data="{ length: {{ mb_strlen($text) }} }" @endif
>
    <x-ui.field :label="$label" :for="$id" :error="$error">
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            @if ($maxlength) maxlength="{{ $maxlength }}" x-on:input="length = $event.target.value.length" @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->class([
                'block w-full rounded-lg border bg-white px-3 py-2.5 text-base leading-7 text-neutral-900 placeholder:text-neutral-500',
                'transition-colors duration-150 focus:outline-none focus:ring-1',
                'border-neutral-300 focus:border-ink focus:ring-ink' => ! $error,
                'border-rose-500 focus:border-rose-500 focus:ring-rose-500' => $error,
            ]) }}
        >{{ $text }}</textarea>

        @if ($maxlength)
            <x-slot:counter>
                <span x-text="length">{{ mb_strlen($text) }}</span> / {{ $maxlength }}
            </x-slot:counter>
        @endif
    </x-ui.field>
</div>
