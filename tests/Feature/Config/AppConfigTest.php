<?php

use Illuminate\Support\Facades\Validator;

test('仕様で決めた数値が既定値になっている', function () {
    // requirements.md 3.2 / 3.3 / 3.4、architecture.md 2.7
    expect(config('quiz.max_questions_per_section'))->toBe(10)
        ->and(config('quiz.answer_retention_limit'))->toBe(10)
        ->and(config('quiz.weak_threshold'))->toBe(60)
        ->and(config('quiz.display_timezone'))->toBe('Asia/Tokyo');
});

test('日時は UTC で扱う', function () {
    // architecture.md 2.7: 保存は UTC、表示のときだけ変換する
    expect(config('app.timezone'))->toBe('UTC');
});

test('バリデーションのメッセージが英語で出る', function () {
    // requirements.md 5.5: 画面の文言はすべて英語
    $validator = Validator::make(['name' => ''], ['name' => 'required']);

    expect(app()->getLocale())->toBe('en')
        ->and($validator->errors()->first('name'))->toBe('The name field is required.');
});
