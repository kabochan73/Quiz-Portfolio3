<?php

namespace App\Services\Grading;

use App\Enums\GradingLevel;

/**
 * 回答を採点する係(architecture.md 4.3 / 4.4)。
 * 外部 API(Claude)を呼ぶ処理はこのインターフェースの後ろに置き、テストや画面の作り込みではフェイクに差し替える(CLAUDE.md)。
 * 入出力はモデルではなく専用の型にして、DB がなくても使える・テストできるようにしている。
 */
interface GradingService
{
    /**
     * 1回の挑戦の全問をまとめて採点する。
     *
     * @param  list<GradingItem>  $items  採点する問題と回答(出題順)
     */
    public function grade(array $items, GradingLevel $level): GradingResult;
}
