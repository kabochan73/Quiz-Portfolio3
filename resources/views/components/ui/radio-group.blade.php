{{--
    切り替えボタン(design-guide.md 7.11)。少ない選択肢(2〜4個)から1つを選ぶ。採点レベルの選択に使う。
    中身は普通のラジオボタン(画面には出さない)なので、キーボードの矢印キーでも選べる。
    選ばれている項目は黒の背景・白文字にする(白黒の切り替えスイッチの見た目)。
    options: [値 => 表示名]
--}}
@props([
    'name',
    'label',
    'options',
    'value' => null,
])

@php
    $error = ($errors ?? new \Illuminate\Support\ViewErrorBag)->first($name) ?: null;
    $selected = (string) old($name, $value);
@endphp

<fieldset {{ $attributes->class('space-y-1.5') }}>
    <legend class="mb-1.5 block text-sm font-medium text-neutral-900">{{ $label }}</legend>

    <div class="grid auto-cols-fr grid-flow-col gap-1 rounded-lg border border-neutral-300 bg-white p-1">
        @foreach ($options as $optionValue => $optionLabel)
            <label class="relative">
                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $optionValue }}"
                    class="peer sr-only"
                    @checked($selected === (string) $optionValue)
                >
                <span class="flex min-h-10 cursor-pointer items-center justify-center rounded-md px-3 text-sm font-medium text-neutral-600 transition-colors duration-150 hover:bg-neutral-100 peer-checked:bg-ink peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-ink peer-focus-visible:ring-offset-2">
                    {{ $optionLabel }}
                </span>
            </label>
        @endforeach
    </div>

    @if ($error !== null)
        <p class="text-sm text-rose-600">{{ $error }}</p>
    @endif
</fieldset>
