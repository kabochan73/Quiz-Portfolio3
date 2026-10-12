<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Queries\AttemptAverages;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * カテゴリの一覧・作成・詳細・編集・削除(requirements.md 3.2、screens.md 2.2〜2.4)。
 */
class CategoryController extends Controller
{
    public function index(): View
    {
        // カードにセクション数・問題数を出す(requirements.md 5.4)。並び順は作成順
        $categories = Category::query()
            ->withCount(['sections', 'questions'])
            ->orderBy('id')
            ->get();

        return view('categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return redirect()->route('categories.show', $category)
            ->with('toast', ['type' => 'success', 'message' => 'Category created.']);
    }

    public function show(Category $category, AttemptAverages $averages): View
    {
        // セクションの行に、問題数と直近の挑戦の平均点を出す(screens.md 2.4)
        $sections = $category->sections()
            ->withCount('questions')
            ->orderBy('id')
            ->get();

        return view('categories.show', [
            'category' => $category,
            'sections' => $sections,
            'latestAverages' => $averages->latestForSections($sections->modelKeys()),
            // 削除の確認モーダルで、一緒に消えるものの件数を出す(requirements.md 5.3)
            'deletionCounts' => [
                'sections' => $sections->count(),
                'questions' => $category->questions()->count(),
                'attempts' => $category->attempts()->count(),
            ],
        ]);
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', ['category' => $category]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.show', $category)
            ->with('toast', ['type' => 'success', 'message' => 'Category saved.']);
    }

    public function destroy(Category $category): RedirectResponse
    {
        // 配下のセクション・問題・挑戦は DB の cascade で一緒に消える(requirements.md 3.2)
        $category->delete();

        return redirect()->route('categories.index')
            ->with('toast', ['type' => 'success', 'message' => 'Category deleted.']);
    }
}
