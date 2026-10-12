{{--
    スケルトン(design-guide.md 7.9)。読み込み中の場所に、形だけの薄いグレーのブロックを点滅させて出す。
    採点中の画面で、結果のカードの形を先に見せるのに使う。大きさ(h-4 w-32 など)は使う側で指定する。
    回転するスピナーは使わない(design-guide.md 5章)。
--}}
<div aria-hidden="true" {{ $attributes->class('animate-pulse rounded-md bg-neutral-100') }}></div>
