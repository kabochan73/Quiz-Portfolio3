{{--
    ホーム = ログイン後の着地点(requirements.md 3.1.1、screens.md 2.12)。
    このアプリの紹介(見出し → 使い方 → 向いている使い方)を出し、一番下に「さあ、始めよう。」と New category ボタンを置く。
    使う人・使い方によって効果が変わるアプリなので、向いている / 向いていない使い方も毎回見えるようにする。
    紹介の文章だけは日本語にする(2026-10-14 決定)。ボタンはほかの画面と同じく英語。
--}}
@php
    $steps = [
        [
            'title' => '問題を作成',
            'text' => 'カテゴリとセクションを作って、問題を追加します。1セクションに10問まで登録できます。',
        ],
        [
            'title' => '自分の言葉で回答',
            'text' => '自分の言葉で答えを書きます。採点の厳しさは Easy / Normal / Strict から選べます。',
        ],
        [
            'title' => 'AIが採点、フィードバック',
            'text' => 'AI が 0〜100点で採点し、「良かった点」「悪かった点」「模範解答」を返します。',
        ],
        [
            'title' => '苦手だけ再挑戦',
            'text' => '60点未満の問題は苦手として残ります。できるようになるまで、苦手な問題だけをくり返せます。',
        ],
    ];
@endphp

<x-layouts.app title="Home">
    <div class="space-y-12 py-4 lg:py-8" lang="ja">
        <section>
            <h1 class="text-3xl font-semibold tracking-tight text-neutral-900 lg:text-4xl">思い出す、そして理解する</h1>
            <p class="text-xl mt-4 max-w-xl text-neutral-600">
                自分の言葉で答えを書くことで、<br>
                自分が本当に理解しているかを確かめる学習アプリです。
            </p>
        </section>

        <section>
            {{-- 手順なので、上から順に読めるよう縦一列に並べる。番号を左、見出しと説明を右に置く。
                 カードの枠で囲まず、下線だけで区切る(2026-10-14 決定) --}}
            <ol>
                @foreach ($steps as $step)
                    <li class="flex items-start gap-4 border-b border-neutral-200 py-4">
                        <span
                            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-semibold text-white tabular-nums">{{ $loop->iteration }}</span>
                        <div>
                            <p class="text-sm font-semibold text-neutral-900">{{ $step['title'] }}</p>
                            <p class="mt-1 text-sm leading-6 text-neutral-600">{{ $step['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        <section>
            <h2 class="mb-4 text-lg font-semibold text-neutral-900">こんな人におすすめ</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-ui.card>
                    <p class="flex items-center gap-2 text-sm font-semibold text-neutral-900">
                        <x-icon name="check" class="size-4" />
                        向いている使い方
                    </p>
                    <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm leading-6 text-neutral-600">
                        <li>人に説明できるくらい理解したい</li>
                        <li>記述式の答えを練習したい</li>
                        <li>「覚えた」ではなく「理解した」かを確かめたい</li>
                    </ul>
                </x-ui.card>
                <x-ui.card>
                    <p class="flex items-center gap-2 text-sm font-semibold text-neutral-900">
                        <x-icon name="x-mark" class="size-4" />
                        向いていない使い方
                    </p>
                    <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm leading-6 text-neutral-600">
                        <li>答えが1つに決まる、単語や用語の暗記(単語カードのほうが向いています)</li>
                        <li>短い時間でたくさん解くクイズ(1問の答えを書くのに数分かかります)</li>
                    </ul>
                </x-ui.card>
            </div>

        </section>

        {{-- 紹介を読み終えたところで、最初のカテゴリを作れるようにする。枠では囲まない(2026-10-14 決定) --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-4xl font-semibold text-neutral-900">さあ、始めよう。</p>
            <x-ui.button :href="route('categories.create')" lang="en">
                <x-icon name="plus" class="size-4" />
                New category
            </x-ui.button>
        </div>
    </div>
</x-layouts.app>
