{{-- ログイン画面(screens.md 2.1)。サイドバーなしで、カードを画面の中央に置く --}}
<x-layouts.minimal title="Log in">
    <div class="mb-8 text-center">
        <p class="text-2xl font-semibold tracking-tight text-neutral-900">{{ config('app.name') }}</p>
        <p class="mt-1 text-sm text-neutral-500">A study notebook graded by AI</p>
    </div>

    <x-ui.card>
        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf

            {{-- 認証の失敗と回数の上限は、どちらの項目が違うかを出さず、フォームの上に1つだけ出す --}}
            @error('credentials')
                <x-ui.alert>{{ $message }}</x-ui.alert>
            @enderror

            <x-ui.input name="email" type="email" label="Email" autocomplete="username" required autofocus />
            <x-ui.input name="password" type="password" label="Password" autocomplete="current-password" required />

            {{-- 「ログイン状態を保持」のチェックボックスは置かない。常に30日保持する(requirements.md 3.1) --}}
            <x-ui.button type="submit" class="w-full">Log in</x-ui.button>
        </form>
    </x-ui.card>
</x-layouts.minimal>
