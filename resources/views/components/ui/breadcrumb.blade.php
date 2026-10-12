{{--
    パンくず(design-guide.md 7.7、screens.md 1.3)。「Categories › 〇〇 › △△」の形で現在地を示す。
    items: [['label' => 表示名, 'href' => URL], ...]。最後の項目は現在地なので、リンクにしない。
    スマホでは途中を省き、「… › 現在地」にする(長いと折り返して読みにくいため)。
--}}
@props([
    'items',
])

@php
    $items = array_values($items);
    $last = count($items) - 1;
@endphp

<nav aria-label="Breadcrumb" {{ $attributes->class('mb-4 text-sm') }}>
    <ol class="flex min-w-0 items-center gap-1.5 text-neutral-500">
        @foreach ($items as $index => $item)
            @if ($index === $last)
                @if ($last > 0)
                    {{-- スマホでだけ出す「…」。1つ前の項目へ戻れるようにする --}}
                    <li class="flex items-center gap-1.5 sm:hidden">
                        <a href="{{ $items[$last - 1]['href'] }}" class="rounded hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink" aria-label="Back to {{ $items[$last - 1]['label'] }}">…</a>
                        <span aria-hidden="true">›</span>
                    </li>
                @endif
                <li class="min-w-0 truncate font-medium text-neutral-900" aria-current="page">{{ $item['label'] }}</li>
            @else
                <li class="hidden min-w-0 items-center gap-1.5 sm:flex">
                    <a href="{{ $item['href'] }}" class="truncate rounded transition-colors duration-150 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink">{{ $item['label'] }}</a>
                    <span aria-hidden="true">›</span>
                </li>
            @endif
        @endforeach
    </ol>
</nav>
