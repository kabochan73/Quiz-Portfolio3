{{--
    アイコン(design-guide.md 6章)。Heroicons(Outline)の SVG を埋め込む(外部の CDN から読み込まない)。
    色は文字色に合わせる(currentColor)。大きさの既定は 20px(size-5)。class で size-4 などを渡すと、その大きさになる。
    アイコンだけで意味を伝えるときは label を渡す(読み上げソフト向け)。渡さなければ飾りとして読み上げない。
    使うアイコンだけをここに足していく。
--}}
@props([
    'name',
    'label' => null,
])

@php
    $paths = [
        'arrow-right-start-on-rectangle' => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9',
        'bars-3' => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
        'check' => 'm4.5 12.75 6 6 9-13.5',
        'check-circle' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'chevron-down' => 'm19.5 8.25-7.5 7.5-7.5-7.5',
        'chevron-right' => 'm8.25 4.5 7.5 7.5-7.5 7.5',
        'document-text' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        'ellipsis-horizontal' => 'M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
        'exclamation-circle' => 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
        'home' => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        'plus' => 'M12 4.5v15m7.5-7.5h-15',
        'x-mark' => 'M6 18 18 6M6 6l12 12',
    ];

    // 書き間違いに気づけるよう、知らない名前はエラーにする
    $path = $paths[$name] ?? throw new InvalidArgumentException("Unknown icon: {$name}");
@endphp

<svg
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor"
    @if ($label !== null) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
    {{-- 大きさ(size-4 など)を渡されたら、既定の size-5 は付けない(両方あると、どちらが効くかが CSS の並び順任せになる) --}}
    {{ $attributes->class(['shrink-0', 'size-5' => ! str_contains((string) $attributes->get('class'), 'size-')]) }}
><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" /></svg>
