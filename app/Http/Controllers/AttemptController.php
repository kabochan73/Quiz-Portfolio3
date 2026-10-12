<?php

namespace App\Http\Controllers;

use App\Enums\AttemptStatus;
use App\Jobs\GradeAttempt;
use App\Models\Attempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * 挑戦の採点状態の確認と、再採点(architecture.md 4.7 / 4.8)。
 */
class AttemptController extends Controller
{
    /**
     * 結果画面が3秒ごとに呼び、completed か failed になったらページを再読み込みする。
     */
    public function status(Attempt $attempt): JsonResponse
    {
        return response()->json(['status' => $attempt->status->value]);
    }

    /**
     * 失敗した挑戦を、回答を入力し直さずに採点し直す。
     */
    public function regrade(Attempt $attempt): RedirectResponse
    {
        // failed のときだけ受け付ける。採点中や完了済みでボタンを押し直しても、二重に採点しない
        if ($attempt->status === AttemptStatus::Failed) {
            $attempt->update(['status' => AttemptStatus::Pending, 'error_message' => null]);

            GradeAttempt::dispatch($attempt);
        }

        return redirect()->route('sections.history.show', [$attempt->section_id, $attempt]);
    }
}
