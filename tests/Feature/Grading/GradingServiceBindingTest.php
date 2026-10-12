<?php

use App\Services\Grading\ClaudeGradingService;
use App\Services\Grading\FakeGradingService;
use App\Services\Grading\GradingService;

test('GRADING_DRIVER=fake ならフェイクの採点を使う', function () {
    config(['services.grading.driver' => 'fake']);

    expect(app(GradingService::class))->toBeInstanceOf(FakeGradingService::class);
});

test('GRADING_DRIVER=claude なら Claude API での採点を使う', function () {
    config(['services.grading.driver' => 'claude', 'services.anthropic.api_key' => 'test-key']);

    expect(app(GradingService::class))->toBeInstanceOf(ClaudeGradingService::class);
});

test('GRADING_DRIVER=claude で API キーがなければエラーにする', function () {
    config(['services.grading.driver' => 'claude', 'services.anthropic.api_key' => null]);

    app(GradingService::class);
})->throws(InvalidArgumentException::class, 'ANTHROPIC_API_KEY');

test('テストでは常にフェイクの採点になっている', function () {
    // phpunit.xml で GRADING_DRIVER=fake にしている。テストで本物の API を呼ばないため(CLAUDE.md)
    expect(config('services.grading.driver'))->toBe('fake');
});

test('GRADING_DRIVER が知らない値ならエラーにする', function () {
    config(['services.grading.driver' => 'unknown']);

    app(GradingService::class);
})->throws(InvalidArgumentException::class, 'GRADING_DRIVER');
