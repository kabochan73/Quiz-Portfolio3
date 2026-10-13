<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * 日時を画面に出す形にする(architecture.md 2.7、requirements.md 5.5)。
 * 日時は UTC で保存し、表示するときだけ表示用のタイムゾーン(config の quiz.display_timezone)に変える。
 * 変換はここ1か所にまとめ、<x-ui.datetime> やパンくずから使う。
 */
final class DisplayTime
{
    /** 一覧など: Oct 7 */
    public const SHORT = 'M j';

    /** 結果画面など: Oct 8, 14:32 */
    public const LONG = 'M j, H:i';

    public static function local(CarbonInterface $value): CarbonInterface
    {
        // 元の日時を書き換えないよう、コピーしてから変える
        return $value->copy()->setTimezone(config('quiz.display_timezone'));
    }

    public static function format(CarbonInterface $value, string $format = 'short'): string
    {
        return self::local($value)->format($format === 'long' ? self::LONG : self::SHORT);
    }
}
