{{--
    空の状態(design-guide.md 7.8、requirements.md 5.3)。カテゴリ・問題・履歴が0件のときに出す。
    点線の枠のカードに、アイコン・一文の説明・次の行動のボタンを中央寄せで置く。
    action / actionHref を渡したときだけボタンを出す。
--}}
@props([
    'message',
    'action' => null,
    'actionHref' => null,
    'icon' => 'document-text',
])

<div {{ $attributes->class('flex flex-col items-center rounded-xl border border-dashed border-neutral-300 px-6 py-10 text-center') }}>
    <x-icon :name="$icon" class="size-8 text-neutral-400" />
    <p class="mt-3 text-sm text-neutral-600">{{ $message }}</p>

    @if ($action !== null && $actionHref !== null)
        <x-ui.button :href="$actionHref" class="mt-5">
            <x-icon name="plus" class="size-4" />
            {{ $action }}
        </x-ui.button>
    @endif
</div>
