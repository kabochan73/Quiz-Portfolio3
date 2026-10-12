<?php

namespace App\Http\Controllers;

use App\Enums\AttemptStatus;
use App\Http\Requests\QuestionRequest;
use App\Models\Question;
use App\Models\Section;
use App\Services\QuestionLimitExceededException;
use App\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * 問題の作成・詳細・編集・削除(requirements.md 3.2、screens.md 2.7 / 2.8)。
 * 所属するセクションは作成時の URL で決まり、あとから変えられない。
 */
class QuestionController extends Controller
{
    // 問題詳細の「最近の点数」に出す件数(screens.md 2.8)
    private const RECENT_SCORES_LIMIT = 5;

    public function __construct(private readonly QuestionService $questions) {}

    public function create(Section $section): View
    {
        return view('questions.create', ['section' => $section->load('category')]);
    }

    public function store(QuestionRequest $request, Section $section): RedirectResponse
    {
        try {
            $this->questions->create($section, $request->validated('body'));
        } catch (QuestionLimitExceededException) {
            // screens.md 4.3 の「上限に達したとき」の文言を、入力欄のエラーとして出す
            throw ValidationException::withMessages([
                'body' => sprintf('This section has the maximum of %d questions.', config('quiz.max_questions_per_section')),
            ]);
        }

        // 続けて追加しやすいよう、セクション詳細に戻る(screens.md 2.7)
        return redirect()->route('sections.show', $section)
            ->with('toast', ['type' => 'success', 'message' => 'Question created.']);
    }

    public function show(Question $question): View
    {
        // 採点が終わった直近の回答(screens.md 2.8)。各行から、その回の結果画面へ移れるよう挑戦も読む
        $recentAnswers = $question->answers()
            ->whereHas('attempt', fn ($query) => $query->where('status', AttemptStatus::Completed))
            ->with(['score', 'attempt'])
            ->orderByDesc('attempt_id')
            ->limit(self::RECENT_SCORES_LIMIT)
            ->get();

        return view('questions.show', [
            'question' => $question->load('section.category'),
            'number' => $this->numberOf($question),
            'recentAnswers' => $recentAnswers,
            // 削除の確認モーダルで、一緒に消える回答の件数を出す(screens.md 2.8)
            'answerCount' => $question->answers()->count(),
        ]);
    }

    public function edit(Question $question): View
    {
        return view('questions.edit', [
            'question' => $question->load('section.category'),
            'number' => $this->numberOf($question),
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $question->update($request->validated());

        return redirect()->route('questions.show', $question)
            ->with('toast', ['type' => 'success', 'message' => 'Question saved.']);
    }

    public function destroy(Question $question): RedirectResponse
    {
        // 回答が0件になった挑戦も一緒に消す(architecture.md 4.6)。削除後は1つ上の階層へ(screens.md 3章)
        $this->questions->delete($question);

        return redirect()->route('sections.show', $question->section_id)
            ->with('toast', ['type' => 'success', 'message' => 'Question deleted.']);
    }

    /**
     * セクションの中で何問目か(画面の「Question 2」の番号)。問題は作成順に並ぶ。
     */
    private function numberOf(Question $question): int
    {
        return $question->section->questions()->where('id', '<', $question->id)->count() + 1;
    }
}
