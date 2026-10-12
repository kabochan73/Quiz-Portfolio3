<?php

namespace App\Enums;

/**
 * 採点の厳しさ(requirements.md 3.3)。
 * 回答を送信するときに選び、挑戦(Attempt)ごとに1つ記録する。その挑戦の全問を同じ厳しさで採点するため。
 */
enum GradingLevel: string
{
    case Easy = 'easy';
    case Normal = 'normal';
    // v2 の hard から変更し、画面の表示 Strict とそろえた(requirements.md 9章)
    case Strict = 'strict';

    /**
     * 画面に出すラベル(screens.md 4.4)。
     */
    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Easy',
            self::Normal => 'Normal',
            self::Strict => 'Strict',
        };
    }

    /**
     * 採点の system プロンプトに入れる採点方針(architecture.md 4.4)。
     * AI への指示なので日本語。レベルごとに文言を固定し、採点の基準がぶれないようにする。
     */
    public function policyText(): string
    {
        return match ($this) {
            self::Easy => '優しめに採点してください。多少の言葉足らずや表現の粗さは減点せず、趣旨が伝わっていれば高めの点数をつけてください。',
            self::Normal => '標準的な基準で採点してください。過度に甘くも厳しくもしないでください。',
            self::Strict => '厳しめに採点してください。曖昧な表現・論理の飛躍・説明不足を見逃さず、完成度が高い解答でなければ高得点をつけないでください。',
        };
    }
}
