{{--
    問題の作成・編集で共通のフォーム(screens.md 2.7)。入力は問題文だけ。所属するセクションは URL で決まる。
    $action: 送信先 / $method: POST か PUT / $submitLabel: Add か Save / $cancelHref: キャンセルで戻る先
--}}
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @method($method)

    {{-- 問題文は 2000 文字まで(architecture.md 2.3)。右下に文字数を出す --}}
    <x-ui.textarea name="body" label="Question" :value="$question?->body" rows="6" maxlength="2000" lang="ja" required autofocus />

    <div class="flex justify-end gap-2">
        <x-ui.button variant="secondary" :href="$cancelHref">Cancel</x-ui.button>
        <x-ui.button type="submit">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
