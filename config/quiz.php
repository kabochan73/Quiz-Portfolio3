<?php

/*
|--------------------------------------------------------------------------
| アプリ固有の設定
|--------------------------------------------------------------------------
|
| 仕様で決まっている数値をここに集め、コード中に直接書かないようにする。
| どれも .env で上書きできる(テストで上限を小さくし、境界値を確かめやすくするため)。
|
*/

return [

    // requirements.md 3.2: 回答はセクション内の全問を1回の API リクエストで一括採点するため、
    // 1セクションに作れる問題数に上限を設ける。
    'max_questions_per_section' => (int) env('QUIZ_MAX_QUESTIONS_PER_SECTION', 10),

    // requirements.md 3.4: 同じ問題の回答は直近この件数だけ残し、古いものは自動で削除する。
    'answer_retention_limit' => (int) env('QUIZ_ANSWER_RETENTION_LIMIT', 10),

    // requirements.md 3.3: 最新の点数がこの値未満の問題を「苦手問題」とする。
    // 点数バッジの色分け(design-guide.md 2.3)の赤の区切りにも同じ値を使う。
    'weak_threshold' => (int) env('QUIZ_WEAK_THRESHOLD', 60),

    // architecture.md 2.7: 日時は UTC で保存し、画面に出すときだけこのタイムゾーンに変換する。
    'display_timezone' => env('QUIZ_DISPLAY_TIMEZONE', 'Asia/Tokyo'),

    /*
    |--------------------------------------------------------------------------
    | Claude API の単価(architecture.md 5章)
    |--------------------------------------------------------------------------
    |
    | 推定コストの表示に使う。料金が変わっても設定だけ直せばよいよう、値はコードに書かず
    | .env で Anthropic の公式料金表の値(ドル)を設定する。
    | 未設定(null)のモデルはコストを表示せず、トークン数だけを表示する。
    | Claude Haiku 5.5 は、1回の入力が10万トークンを超えると単価が上がる。採点の入力はふだん数万トークンなので、
    | 10万以下の単価だけを設定する(超えたときの推定コストは、実際より少なく出る)。
    |
    */

    'pricing' => [
        'claude-haiku-5-5' => [
            'input_per_mtok' => env('QUIZ_PRICE_INPUT_PER_MTOK'),   // 100万入力トークンあたり
            'output_per_mtok' => env('QUIZ_PRICE_OUTPUT_PER_MTOK'), // 100万出力トークンあたり
        ],
    ],

    'web_search_per_request' => env('QUIZ_PRICE_WEB_SEARCH'), // Web検索1回あたり

    /*
    |--------------------------------------------------------------------------
    | 管理者アカウント(requirements.md 3.1)
    |--------------------------------------------------------------------------
    |
    | Seeder(AdminUserSeeder)が、この値で唯一のログインユーザーを作る。
    | リポジトリは公開するので、メールアドレスとパスワードはコードに書かず .env で設定する。
    |
    */

    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
