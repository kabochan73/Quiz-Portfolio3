{{--
    サイドバーのないレイアウト。ログイン画面とエラーページに使う(screens.md 2.1)。
    中身を画面の中央に置く。
--}}
@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="en">
<head>
    <x-layout.head :title="$title" />
</head>
<body class="bg-neutral-50">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-sm">
            {{ $slot }}
        </div>
    </main>
</body>
</html>
