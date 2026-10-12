<?php

namespace App\Http\Controllers;

use App\Enums\AttemptMode;
use App\Enums\GradingLevel;
use App\Http\Requests\StoreAnswersRequest;
use App\Jobs\GradeAttempt;
use App\Models\Attempt;
use App\Models\Section;
use App\Queries\WeakQuestions;
use App\Services\AnswerRetentionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 回答フォームと回答の送信(requirements.md 3.3、architecture.md 4.2、screens.md 2.9)。
 */
class AnswerController extends Controller
{
    public function create(Request $request, Section $section, WeakQuestions $weakQuestions): View|RedirectResponse
    {
        // ?mode=all|weak。指定がない・知らない値なら全問モードにする
        $mode = AttemptMode::tryFrom((string) $request->query('mode')) ?? AttemptMode::All;

        $questions = match ($mode) {
            AttemptMode::All => $section->questions()->get(),
            AttemptMode::Weak => $section->questions()->whereIn('id', $weakQuestions->idsFor($section))->get(),
        };

        // 画面ではボタンを無効にしているが、URL を直接開かれたときのために戻す(screens.md 2.6)
        if ($questions->isEmpty()) {
            return redirect()->route('sections.show', $section)->with('toast', [
                'type' => 'error',
                'message' => $mode === AttemptMode::Weak ? 'No weak questions.' : 'No questions yet.',
            ]);
        }

        return view('answers.create', [
            'section' => $section->load('category'),
            'mode' => $mode,
            'questions' => $questions,
            'gradingLevels' => GradingLevel::cases(),
            'defaultGradingLevel' => GradingLevel::Normal,
            'weakThreshold' => config('quiz.weak_threshold'),
        ]);
    }

    public function store(StoreAnswersRequest $request, Section $section, AnswerRetentionService $retention): RedirectResponse
    {
        $answers = $request->validated('answers');

        // 挑戦と回答の保存・古い回答の整理は、途中で止まっても半端に残らないよう1つのトランザクションで行う
        $attempt = DB::transaction(function () use ($request, $section, $answers, $retention) {
            /** @var Attempt $attempt */
            $attempt = $section->attempts()->create([
                'grading_level' => $request->enum('grading_level', GradingLevel::class),
                'mode' => $request->enum('mode', AttemptMode::class),
            ]);

            // 送られた順番が、挑戦の中での順番(position)になる
            foreach ($answers as $position => $answer) {
                $attempt->answers()->create([
                    'question_id' => $answer['question_id'],
                    'position' => $position,
                    'body' => $answer['body'],
                ]);
            }

            $retention->prune(array_column($answers, 'question_id'));

            return $attempt;
        });

        // トランザクションが確定してから Job を入れる。確定前に worker が動き出すと、まだない挑戦を読もうとしてしまう
        GradeAttempt::dispatch($attempt)->afterCommit();

        // 結果画面(採点中の表示)へ移る。リロードしても再送信されない(PRG)
        return redirect()->route('sections.history.show', [$section, $attempt]);
    }
}
