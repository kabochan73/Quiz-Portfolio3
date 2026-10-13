{{--
    カテゴリの作成・編集で共通のフォーム(screens.md 2.3)。ボタンの文言だけを変える。
    $action: 送信先 / $method: POST か PUT / $submitLabel: Create か Save / $cancelHref: キャンセルで戻る先
--}}
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @method($method)

    <x-ui.input name="name" label="Name" :value="$category?->name" maxlength="100" required autofocus />

    <div class="flex justify-end gap-2">
        <x-ui.button variant="secondary" :href="$cancelHref">Cancel</x-ui.button>
        <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
