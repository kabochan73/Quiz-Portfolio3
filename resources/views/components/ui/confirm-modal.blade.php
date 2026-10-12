{{--
    削除の確認モーダル(design-guide.md 7.6、requirements.md 5.3)。ブラウザ標準の confirm() は使わない。
    name を付けて置き、$dispatch('open-modal', '名前') で開く(「…」メニューの Delete から)。
    中身(slot)には、一緒に消えるデータの件数を書く(例: This will also delete 3 sections, ...)。
    Delete はフォームの DELETE 送信。開いたら Cancel にフォーカスを移す(誤操作を防ぐため)。
--}}
@props([
    'name',
    'title',
    'action',
])

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === @js($name)) open = true"
    x-on:keydown.escape.window="open = false"
>
    <div
        x-cloak
        x-show="open"
        class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $name }}-title"
    >
        <div class="absolute inset-0 bg-neutral-900/40" x-show="open" x-transition.opacity.duration.200ms x-on:click="open = false"></div>

        <div
            x-show="open"
            x-trap.noscroll="open"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-end="scale-95 opacity-0"
            class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
        >
            <h2 id="{{ $name }}-title" class="text-lg font-semibold text-neutral-900">{{ $title }}</h2>

            @if ($slot->isNotEmpty())
                <p class="mt-2 text-sm text-neutral-600">{{ $slot }}</p>
            @endif

            <form method="POST" action="{{ $action }}" class="mt-6 flex justify-end gap-2">
                @csrf
                @method('DELETE')
                {{-- x-trap は最初のボタンにフォーカスを移すので、Cancel を先に置く --}}
                <x-ui.button variant="secondary" x-on:click="open = false">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="danger">Delete</x-ui.button>
            </form>
        </div>
    </div>
</div>
