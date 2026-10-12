{{--
    1行の入力欄(design-guide.md 7.2)。ラベルとエラーは field が並べる。
    エラーは、Laravel の入力エラーから name で自動的に取り出す(answers[0][body] → answers.0.body)。
--}}
@props([
    'name',
    'label',
    'id' => null,
    'type' => 'text',
    'value' => null,
])

@php
    $id ??= str_replace(['[', ']'], ['-', ''], $name);
    $error = ($errors ?? new \Illuminate\Support\ViewErrorBag)->first(str_replace(['[', ']'], ['.', ''], $name)) ?: null;
@endphp

<x-ui.field :label="$label" :for="$id" :error="$error">
    <input
        type="{{ $type }}"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ old(str_replace(['[', ']'], ['.', ''], $name), $value) }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->class([
            // 16px 以上にして、iOS で入力時に画面が拡大されるのを防ぐ(design-guide.md 3.2)
            'block min-h-11 w-full rounded-lg border bg-white px-3 text-base text-neutral-900 placeholder:text-neutral-500',
            'transition-colors duration-150 focus:outline-none focus:ring-1',
            'border-neutral-300 focus:border-ink focus:ring-ink' => ! $error,
            'border-rose-500 focus:border-rose-500 focus:ring-rose-500' => $error,
        ]) }}
    >
</x-ui.field>
