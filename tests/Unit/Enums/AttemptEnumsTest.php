<?php

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\GradingLevel;

test('Enum の値はマイグレーションの CHECK 制約と同じ', function () {
    // マイグレーションは Enum を参照しないので(2026_10_12_000004)、値がずれていないかをここで確かめる
    $values = fn (string $enum) => array_column($enum::cases(), 'value');

    expect($values(GradingLevel::class))->toBe(['easy', 'normal', 'strict'])
        ->and($values(AttemptMode::class))->toBe(['all', 'weak'])
        ->and($values(AttemptStatus::class))->toBe(['pending', 'grading', 'completed', 'failed']);
});

test('ラベルは画面設計の英語の文言と同じ', function () {
    // screens.md 4.4
    $labels = fn (string $enum) => array_map(fn ($case) => $case->label(), $enum::cases());

    expect($labels(GradingLevel::class))->toBe(['Easy', 'Normal', 'Strict'])
        ->and($labels(AttemptMode::class))->toBe(['All', 'Weak'])
        ->and($labels(AttemptStatus::class))->toBe(['Waiting', 'Grading', 'Done', 'Failed']);
});

test('採点方針の文言はレベルごとに違い、どれも空ではない', function () {
    $policies = array_map(fn (GradingLevel $level) => $level->policyText(), GradingLevel::cases());

    expect($policies)->each->not->toBeEmpty()
        ->and(array_unique($policies))->toHaveCount(3);
});

test('採点方針は厳しさの段階が伝わる文言になっている', function (GradingLevel $level, string $keyword) {
    // architecture.md 4.4: AI への指示なので日本語
    expect($level->policyText())->toContain($keyword);
})->with([
    [GradingLevel::Easy, '優しめ'],
    [GradingLevel::Normal, '標準的'],
    [GradingLevel::Strict, '厳しめ'],
]);

test('採点中かどうかは pending と grading のときだけ true', function (AttemptStatus $status, bool $expected) {
    expect($status->isInProgress())->toBe($expected);
})->with([
    [AttemptStatus::Pending, true],
    [AttemptStatus::Grading, true],
    [AttemptStatus::Completed, false],
    [AttemptStatus::Failed, false],
]);
