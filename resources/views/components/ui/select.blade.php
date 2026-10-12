{{--
    選択肢から1つを選ぶ入力欄(design-guide.md 7.2)。選択肢が多いとき用。2〜4個なら radio-group を使う。
    options: [値 => 表示名]
--}}
@props([
    'name',
    'label',
    'options',
    'id' => null,
    'value' => null,
])

@php
    $id ??= $name;
    $error = ($errors ?? new \Illuminate\Support\ViewErrorBag)->first($name) ?: null;
    $selected = (string) old($name, $value);
@endphp

<x-ui.field :label="$label" :for="$id" :error="$error">
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->class([
            'block min-h-11 w-full rounded-lg border bg-white px-3 text-base text-neutral-900',
            'transition-colors duration-150 focus:outline-none focus:ring-1',
            'border-neutral-300 focus:border-ink focus:ring-ink' => ! $error,
            'border-rose-500 focus:border-rose-500 focus:ring-rose-500' => $error,
        ]) }}
    >
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</x-ui.field>
