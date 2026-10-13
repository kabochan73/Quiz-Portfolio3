{{--
    ホーム = ログイン後の着地点(requirements.md 3.1.1)。
    コンセプトと使い方の文章は後回し。それまでは白紙に New category ボタンだけを置く(screens.md 2.12)。
--}}
<x-layouts.app title="Home">
    <div class="flex justify-center py-24">
        <x-ui.button :href="route('categories.create')">
            <x-icon name="plus" class="size-4" />
            New category
        </x-ui.button>
    </div>
</x-layouts.app>
