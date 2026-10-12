{{--
    丸いバッジ(design-guide.md 4.3 / 7.4)。点数・状態・種別のバッジのもとになる。
    tone: neutral(控えめなグレー)/ emerald(良い・完了)/ amber(中くらい)/ rose(悪い・失敗)/ sky(処理中)
    色を使うのはバッジだけ(design-guide.md 1章)。色だけで意味を伝えないよう、中身には必ず文字か数字を入れる。
--}}
@props([
    'tone' => 'neutral',
])

<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
    match ($tone) {
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'rose' => 'bg-rose-50 text-rose-700',
        'sky' => 'bg-sky-50 text-sky-700',
        default => 'bg-neutral-100 text-neutral-700',
    },
]) }}>{{ $slot }}</span>
