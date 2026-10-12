<?php

use App\Services\Grading\FakeGradingService;
use App\Services\Grading\GradingService;

test('GRADING_DRIVER=fake ならフェイクの採点を使う', function () {
    config(['services.grading.driver' => 'fake']);

    expect(app(GradingService::class))->toBeInstanceOf(FakeGradingService::class);
});

test('テストでは常にフェイクの採点になっている', function () {
    // phpunit.xml で GRADING_DRIVER=fake にしている。テストで本物の API を呼ばないため(CLAUDE.md)
    expect(config('services.grading.driver'))->toBe('fake');
});

test('GRADING_DRIVER が知らない値ならエラーにする', function () {
    config(['services.grading.driver' => 'unknown']);

    app(GradingService::class);
})->throws(InvalidArgumentException::class, 'GRADING_DRIVER');
