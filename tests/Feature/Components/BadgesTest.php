<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;

// バッジ類の共通コンポーネント(design-guide.md 2.3 / 7.4)。見た目は /dev/components(5-6)で確かめる

test('点数バッジは、点数の帯ごとに色を変える', function (?int $score, string $text, string $color) {
    $this->blade('<x-ui.score-badge :score="$score" />', ['score' => $score])
        ->assertSee(">{$text}</span>", false)
        ->assertSee($color, false);
})->with([
    '100点は緑' => [100, '100', 'bg-emerald-50'],
    '80点ちょうどは緑' => [80, '80', 'bg-emerald-50'],
    '79点は黄' => [79, '79', 'bg-amber-50'],
    '60点ちょうどは黄(苦手ではない)' => [60, '60', 'bg-amber-50'],
    '59点は赤(苦手)' => [59, '59', 'bg-rose-50'],
    '0点は赤' => [0, '0', 'bg-rose-50'],
    'まだ点数がなければ「—」' => [null, '—', 'bg-neutral-100'],
]);

test('点数バッジの赤の区切りは、苦手の基準点と同じ', function () {
    // design-guide.md 2.3: 苦手の基準点(config)を変えると、色の区切りも変わる
    config(['quiz.weak_threshold' => 70]);

    $this->blade('<x-ui.score-badge :score="65" />')->assertSee('bg-rose-50', false);
});

test('平均点のような小数は、四捨五入して出す', function () {
    $this->blade('<x-ui.score-badge :score="57.5" />')->assertSee('>58</span>', false);
});

test('前回比は、増えたら▲・減ったら▼・同じなら±0、初回は New', function (?float $previous, string $text, string $color) {
    $this->blade('<x-ui.score-delta :current="78" :previous="$previous" />', ['previous' => $previous])
        ->assertSee(">{$text}</span>", false)
        ->assertSee($color, false);
})->with([
    '上がった' => [62, '▲16', 'text-emerald-600'],
    '下がった' => [86, '▼8', 'text-rose-600'],
    '同じ' => [78, '±0', 'text-neutral-500'],
    '初回' => [null, 'New', 'text-neutral-500'],
]);

test('平均点の前回比は、画面に出す点数どうしの差にする', function () {
    // 72.4 → 72、65.6 → 66 なので、差は ▲6(小数のまま引くと 6.8 → 7 になってしまう)
    $this->blade('<x-ui.score-delta :current="72.4" :previous="65.6" />')->assertSee('>▲6</span>', false);
});

test('状態バッジは、状態ごとに文言と色を変える', function (AttemptStatus $status, string $text, string $color) {
    $this->blade('<x-ui.status-badge :status="$status" />', ['status' => $status])
        ->assertSee(">{$text}</span>", false)
        ->assertSee($color, false);
})->with([
    [AttemptStatus::Pending, 'Waiting', 'bg-sky-50'],
    [AttemptStatus::Grading, 'Grading', 'bg-sky-50'],
    [AttemptStatus::Completed, 'Done', 'bg-emerald-50'],
    [AttemptStatus::Failed, 'Failed', 'bg-rose-50'],
]);

test('種別は、控えめなグレーのバッジで出す', function () {
    $this->blade('<x-ui.badge>{{ $mode->label() }}</x-ui.badge>', ['mode' => AttemptMode::Weak])
        ->assertSee('>Weak</span>', false)
        ->assertSee('bg-neutral-100', false);
});
