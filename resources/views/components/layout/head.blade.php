{{--
    <head> の中身。layouts/app と layouts/minimal で共通。
    タイトルは「ページ名 | Recollect」の形(design-guide.md 8章、screens.md 4.2)。
--}}
@props([
    'title' => null,
])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title === null ? config('app.name') : $title.' | '.config('app.name') }}</title>

{{--
    vite.config.js で取り込んだフォント(Inter / Noto Sans JP)を、自サイトから読み込む。
    ビルドした CSS ファイルを <link> で読む(App\Support\FontTags)。npm run dev のときだけ Laravel の @fonts を使う
--}}
@if (Vite::isRunningHot())
    @fonts
@else
    {{ App\Support\FontTags::html(public_path('build/fonts-manifest.json'), asset('build')) }}
@endif
@vite(['resources/css/app.css', 'resources/js/app.js'])
