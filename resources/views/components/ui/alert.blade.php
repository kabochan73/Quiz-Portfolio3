{{--
    お知らせ(design-guide.md 7.12)。入力欄に結びつかないエラーを、フォームの上などに1つ出す。
    ログインの認証エラー・回数の上限など(screens.md 2.1)。出たらすぐに読み上げる。
--}}
<div role="alert" {{ $attributes->class('flex items-start gap-2 rounded-lg bg-rose-50 p-3 text-sm text-rose-700') }}>
    <x-icon name="exclamation-circle" class="mt-px size-4" />
    <p>{{ $slot }}</p>
</div>
