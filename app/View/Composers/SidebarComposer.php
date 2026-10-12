<?php

namespace App\View\Composers;

use App\Models\Attempt;
use App\Models\Category;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * サイドバーに、カテゴリの一覧と「今いるカテゴリ」を渡す(screens.md 1.1)。
 * どの画面でもサイドバーを出すので、コントローラごとに渡さず、サイドバーの部品が使われるときにここで渡す。
 */
final readonly class SidebarComposer
{
    public function __construct(private Request $request) {}

    public function compose(View $view): void
    {
        $view->with([
            'sidebarCategories' => Category::query()->orderBy('id')->get(['id', 'name']),
            'currentCategoryId' => $this->currentCategoryId(),
        ]);
    }

    /**
     * 今いるページが属するカテゴリ。カテゴリ詳細と、その中のセクション・問題・回答・結果・履歴で強調する。
     * URL に含まれるものから、いちばん近い親をたどって決める。
     */
    private function currentCategoryId(): ?int
    {
        $route = $this->request->route();

        return match (true) {
            $route?->parameter('category') instanceof Category => $route->parameter('category')->id,
            $route?->parameter('section') instanceof Section => $route->parameter('section')->category_id,
            $route?->parameter('question') instanceof Question => $route->parameter('question')->section->category_id,
            $route?->parameter('attempt') instanceof Attempt => $route->parameter('attempt')->section->category_id,
            default => null,
        };
    }
}
