<?php

use Illuminate\Support\Carbon;

// 日時の表示(design-guide.md 7.13、architecture.md 2.7)

test('UTC の日時を日本時間にして、英語の短い書式で出す', function () {
    // UTC の 10/7 20:00 は、日本時間では 10/8 5:00
    $this->blade('<x-ui.datetime :value="$value" />', ['value' => Carbon::parse('2026-10-07 20:00:00', 'UTC')])
        ->assertSee('<time datetime="2026-10-08T05:00:00+09:00"', false)
        ->assertSee('>Oct 8</time>', false);
});

test('長い書式では、時刻も出す', function () {
    $this->blade('<x-ui.datetime :value="$value" format="long" />', ['value' => Carbon::parse('2026-10-08 05:32:00', 'UTC')])
        ->assertSee('>Oct 8, 14:32</time>', false);
});

test('表示用のタイムゾーンは config で変えられる', function () {
    config(['quiz.display_timezone' => 'UTC']);

    $this->blade('<x-ui.datetime :value="$value" />', ['value' => Carbon::parse('2026-10-07 20:00:00', 'UTC')])
        ->assertSee('>Oct 7</time>', false);
});

test('元の日時は書き換えない', function () {
    $value = Carbon::parse('2026-10-07 20:00:00', 'UTC');

    $this->blade('<x-ui.datetime :value="$value" />', ['value' => $value]);

    expect($value->timezoneName)->toBe('UTC');
});
