{{--
    トーストの入れ物(design-guide.md 7.5)。レイアウトに1つだけ置く。
    コントローラが ->with('toast', ['type' => 'success'|'error', 'message' => '...']) で渡したものを最初に出す。
    動き(4秒で消える・× で閉じる・JS のイベントから出す)は resources/js/toast.js。
--}}
@php
    $initial = session()->has('toast') ? [session('toast')] : [];
@endphp

<div
    x-data="toastStack(@js($initial))"
    x-on:toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-end="opacity-0"
            x-bind:role="toast.type === 'error' ? 'alert' : 'status'"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-neutral-200 bg-white p-4 shadow-lg"
        >
            <span x-show="toast.type !== 'error'" class="text-emerald-600"><x-icon name="check-circle" /></span>
            <span x-show="toast.type === 'error'" class="text-rose-600"><x-icon name="exclamation-circle" /></span>
            <p class="flex-1 text-sm text-neutral-900" x-text="toast.message"></p>
            <button
                type="button"
                class="-m-1 flex size-7 shrink-0 items-center justify-center rounded-md text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink"
                x-on:click="remove(toast.id)"
            >
                <x-icon name="x-mark" label="Close" class="size-4" />
            </button>
        </div>
    </template>
</div>
