<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Section;
use App\Queries\AttemptAverages;
use App\Queries\LatestScores;
use App\Queries\WeakQuestions;
use App\Support\CostCalculator;
use Illuminate\View\View;

/**
 * セクションの履歴一覧と、履歴詳細 = 採点結果(requirements.md 3.4、screens.md 2.10 / 2.11)。
 * 採点の直後も、あとから履歴で開いたときも、同じ結果画面を使う。
 */
class HistoryController extends Controller
{
    // 履歴一覧は20件ずつ(screens.md 2.11)
    private const PER_PAGE = 20;

    public function index(Section $section, AttemptAverages $averages): View
    {
        // 新しい順。「Newer / Older」のページ送りにする(screens.md 2.11)
        $attempts = $section->attempts()
            ->withCount('answers')
            ->orderByDesc('id')
            ->simplePaginate(self::PER_PAGE);

        return view('history.index', [
            'section' => $section->load('category'),
            'attempts' => $attempts,
            // 採点が終わっていない挑戦は平均点がないので、画面では状態のバッジを出す
            'averages' => $averages->forAttempts($attempts->getCollection()->modelKeys()),
        ]);
    }

    /**
     * URL の挑戦が、URL のセクションのものでなければ 404 にする(ルートの scopeBindings。architecture.md 6章)。
     */
    public function show(
        Section $section,
        Attempt $attempt,
        AttemptAverages $averages,
        LatestScores $latestScores,
        WeakQuestions $weakQuestions,
        CostCalculator $costs,
    ): View {
        // 問題文は回答の写し(question_body)を使うので、問題は読まない(architecture.md 2.5)
        $attempt->load('answers.score');
        $questionIds = $attempt->answers->pluck('question_id');

        return view('history.show', [
            'section' => $section->load('category'),
            'attempt' => $attempt,
            'average' => $averages->forAttempts([$attempt->id])[$attempt->id] ?? null,
            // 同じ種別のひとつ前の挑戦の平均点。初回なら null(screens.md 2.10 (b))
            'previousAverage' => $averages->previous($attempt),
            // 問題ごとの前回の点数。初回の問題は含まない(requirements.md 3.4)
            'previousScores' => $latestScores->forQuestions($questionIds, beforeAttemptId: $attempt->id),
            // 「Retry weak (2)」のボタンに出す、今の苦手問題の数
            'weakCount' => count($weakQuestions->idsFor($section)),
            // 推定コスト。単価が未設定などで計算できなければ null(architecture.md 5章)
            'cost' => $attempt->model === null ? null : $costs->estimate(
                $attempt->model,
                $attempt->input_tokens ?? 0,
                $attempt->output_tokens ?? 0,
                $attempt->web_search_requests ?? 0,
            ),
        ]);
    }
}
