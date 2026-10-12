<?php

namespace App\Enums;

/**
 * 挑戦(Attempt)の採点の状態(requirements.md 3.3、architecture.md 4.3)。
 * 回答を送信すると pending で作られ、worker が採点を始めると grading、
 * 終われば completed、リトライの上限まで失敗すると failed になる。
 */
enum AttemptStatus: string
{
    case Pending = 'pending';
    case Grading = 'grading';
    case Completed = 'completed';
    case Failed = 'failed';

    /**
     * 画面に出すラベル(screens.md 4.4)。
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting',
            self::Grading => 'Grading',
            self::Completed => 'Done',
            self::Failed => 'Failed',
        };
    }

    /**
     * 採点がまだ終わっていないか。
     * pending と grading はどちらも画面では「採点中」として扱う(screens.md 2.10 (a))。
     */
    public function isInProgress(): bool
    {
        return match ($this) {
            self::Pending, self::Grading => true,
            self::Completed, self::Failed => false,
        };
    }
}
