<?php

namespace App\Http\Controllers;

use App\Http\Requests\SectionRequest;
use App\Models\Category;
use App\Models\Section;
use App\Queries\WeakQuestions;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * セクションの作成・詳細・編集・削除(requirements.md 3.2、screens.md 2.5 / 2.6)。
 * 所属するカテゴリは作成時の URL で決まり、あとから変えられない。
 */
class SectionController extends Controller
{
    public function create(Category $category): View
    {
        return view('sections.create', ['category' => $category]);
    }

    public function store(SectionRequest $request, Category $category): RedirectResponse
    {
        $section = $category->sections()->create($request->validated());

        return redirect()->route('sections.show', $section)
            ->with('toast', ['type' => 'success', 'message' => 'Section created.']);
    }

    public function show(Section $section, WeakQuestions $weakQuestions): View
    {
        // 各行の「…」から問題を削除するときの確認モーダルに、一緒に消える回答の件数を出す(screens.md 2.8)
        $questions = $section->questions()->withCount('answers')->get();

        return view('sections.show', [
            'section' => $section->load('category'),
            'questions' => $questions,
            // 「苦手だけ再挑戦」のボタンに出す問題数。0問ならボタンを無効にする(screens.md 2.6)
            'weakCount' => count($weakQuestions->idsFor($section)),
            // 上限に達したら「問題を追加」を無効にする(screens.md 2.6)
            'maxQuestions' => config('quiz.max_questions_per_section'),
            // 削除の確認モーダルで、一緒に消えるものの件数を出す(requirements.md 5.3)
            'deletionCounts' => [
                'questions' => $questions->count(),
                'attempts' => $section->attempts()->count(),
            ],
        ]);
    }

    public function edit(Section $section): View
    {
        return view('sections.edit', ['section' => $section->load('category')]);
    }

    public function update(SectionRequest $request, Section $section): RedirectResponse
    {
        $section->update($request->validated());

        return redirect()->route('sections.show', $section)
            ->with('toast', ['type' => 'success', 'message' => 'Section saved.']);
    }

    public function destroy(Section $section): RedirectResponse
    {
        // 配下の問題・挑戦は DB の cascade で一緒に消える(requirements.md 3.2)。削除後は1つ上の階層へ(screens.md 3章)
        $section->delete();

        return redirect()->route('categories.show', $section->category_id)
            ->with('toast', ['type' => 'success', 'message' => 'Section deleted.']);
    }
}
