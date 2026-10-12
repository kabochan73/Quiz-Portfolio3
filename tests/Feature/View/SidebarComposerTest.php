<?php

use App\Models\Attempt;
use App\Models\Category;
use App\Models\Question;
use App\View\Composers\SidebarComposer;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\View\View;

/**
 * URL にパラメータを持つページを開いたときに、サイドバーへ渡す値を返す。
 * サイドバーの部品(Blade)はまだないので、Composer を直接動かす。
 *
 * @param  array<string, mixed>  $parameters  ルートのパラメータ(ルートモデルバインディング済みのモデル)
 * @return array<string, mixed>
 */
function sidebarData(array $parameters = []): array
{
    $request = Request::create('/');
    $route = (new Route('GET', '/', []))->bind($request);
    foreach ($parameters as $name => $value) {
        $route->setParameter($name, $value);
    }
    $request->setRouteResolver(fn () => $route);

    $data = [];
    $view = Mockery::mock(View::class);
    $view->shouldReceive('with')->andReturnUsing(function (array $values) use (&$data, $view) {
        $data = $values;

        return $view;
    });

    (new SidebarComposer($request))->compose($view);

    return $data;
}

test('カテゴリの一覧を作成順で渡す', function () {
    $first = Category::factory()->create(['name' => 'Networking']);
    $second = Category::factory()->create(['name' => 'Laravel']);

    expect(sidebarData()['sidebarCategories']->pluck('name', 'id')->all())
        ->toBe([$first->id => 'Networking', $second->id => 'Laravel']);
});

test('カテゴリに関係のないページでは、強調するカテゴリはない', function () {
    // ホームやカテゴリ一覧など
    expect(sidebarData()['currentCategoryId'])->toBeNull();
});

test('URL に含まれるものから、今いるカテゴリをたどる', function (string $parameter) {
    $attempt = Attempt::factory()->create();
    $question = Question::factory()->for($attempt->section)->create();
    $models = [
        'category' => $attempt->section->category,
        'section' => $attempt->section,
        'question' => $question,
        'attempt' => $attempt,
    ];

    expect(sidebarData([$parameter => $models[$parameter]])['currentCategoryId'])
        ->toBe($attempt->section->category_id);
})->with([
    'カテゴリ詳細・編集' => 'category',
    'セクション詳細・回答フォーム・履歴' => 'section',
    '問題詳細・編集' => 'question',
    '再採点など' => 'attempt',
]);

test('サイドバーの部品を使うと、この Composer が呼ばれるように登録している', function () {
    expect(app('events')->hasListeners('composing: components.layout.sidebar'))->toBeTrue();
});
