<?php

use App\Models\Category;
use App\Models\Section;
use Illuminate\Routing\Route;

// 全体のレイアウトとサイドバー(screens.md 1章)。見た目はブラウザで確かめる

/**
 * サイドバーの「今いるページ」を決めるため、開いているページのルートを用意する。
 * 画面(コントローラ)はまだないので、ルートの名前とパラメータだけを持たせる。
 */
function onRoute(string $name, array $parameters = []): void
{
    $route = (new Route('GET', '/', []))->name($name)->bind(request());
    foreach ($parameters as $key => $value) {
        $route->setParameter($key, $value);
    }
    request()->setRouteResolver(fn () => $route);
}

test('タイトルは「ページ名 | Recollect」で、ページは英語', function () {
    // design-guide.md 8章、requirements.md 5.5
    $this->blade('<x-layouts.app title="Categories">Body</x-layouts.app>')
        ->assertSee('<html lang="en">', false)
        ->assertSee('<title>Categories | Recollect</title>', false);
});

test('タイトルを渡さなければ、アプリ名だけにする', function () {
    $this->blade('<x-layouts.minimal>Body</x-layouts.minimal>')
        ->assertSee('<title>Recollect</title>', false);
});

test('サイドバーに、ロゴ(ホームへ)・カテゴリの一覧・カテゴリ作成・ログアウトを出す', function () {
    $first = Category::factory()->create(['name' => '基本情報']);
    $second = Category::factory()->create(['name' => 'Laravel']);

    $this->blade('<x-layouts.app title="Categories">Body</x-layouts.app>')
        ->assertSeeInOrder([
            route('home'), 'Recollect',
            'Categories',
            route('categories.create'), 'New category',
            route('categories.show', $first), '基本情報',
            route('categories.show', $second), 'Laravel',
            route('logout'), 'Log out',
        ], false);
});

test('PC 用とスマホのドロワー用に、サイドバーを2つ出す', function () {
    Category::factory()->create(['name' => '基本情報']);

    $html = (string) $this->blade('<x-layouts.app>Body</x-layouts.app>');

    expect(substr_count($html, '基本情報'))->toBe(2)
        ->and($html)->toContain('aria-label="Open menu"')
        ->toContain('aria-label="Close menu"')
        ->toContain('x-trap.noscroll="drawerOpen"');
});

test('サイドバーに Home のリンクは置かない。ホームへはロゴから移る', function () {
    // 2026-10-13 決定: ロゴと役割が重なるため
    onRoute('home');

    $this->blade('<x-layouts.app>Body</x-layouts.app>')
        ->assertDontSee('>Home<', false)
        ->assertDontSee('aria-current="page"', false);
});

test('今いるページが属するカテゴリを強調し、ほかは強調しない', function () {
    // screens.md 1.1: カテゴリ詳細と、その中のセクション・問題・回答・結果・履歴で強調する
    $current = Category::factory()->create(['name' => '基本情報']);
    Category::factory()->create(['name' => 'Laravel']);
    onRoute('sections.show', ['section' => Section::factory()->for($current)->create()]);

    $html = (string) $this->blade('<x-layouts.app>Body</x-layouts.app>');

    // PC 用とドロワー用の2か所で、基本情報だけが現在地になる
    expect(substr_count($html, 'aria-current="page"'))->toBe(2)
        ->and($html)->toMatch('/'.preg_quote(route('categories.show', $current), '/').'"[^>]*bg-neutral-100 font-semibold[^>]*aria-current="page"/');
});

test('ログアウトは POST で送り、CSRF トークンを付ける', function () {
    $this->blade('<x-layouts.app>Body</x-layouts.app>')
        ->assertSeeInOrder(['<form method="POST" action="'.route('logout').'"', 'name="_token"'], false);
});

test('メイン領域の幅は、フォームなら狭く、それ以外は広くする', function () {
    // design-guide.md 4.2
    $this->blade('<x-layouts.app width="form">Body</x-layouts.app>')->assertSee('max-w-2xl', false);
    $this->blade('<x-layouts.app>Body</x-layouts.app>')->assertSee('max-w-3xl', false);
});
