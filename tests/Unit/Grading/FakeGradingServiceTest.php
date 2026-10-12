<?php

use App\Enums\GradingLevel;
use App\Services\Grading\FakeGradingService;
use App\Services\Grading\Grade;
use App\Services\Grading\GradingItem;

function fakeGradingItems(): array
{
    return [
        new GradingItem(position: 0, question: 'TCPとUDPの違いは?', answer: 'TCPは信頼性が高い。'),
        new GradingItem(position: 1, question: 'DNSとは?', answer: '名前をIPアドレスに変換する仕組み。'),
    ];
}

test('全問に、出題順で採点結果を返す', function () {
    $result = (new FakeGradingService)->grade(fakeGradingItems(), GradingLevel::Normal);

    expect($result->grades)->toHaveCount(2)
        ->each->toBeInstanceOf(Grade::class)
        ->and(array_column($result->grades, 'position'))->toBe([0, 1]);
});

test('点数は 0〜100点 で、同じ回答なら毎回同じ', function () {
    $service = new FakeGradingService;

    $first = $service->grade(fakeGradingItems(), GradingLevel::Normal);
    $second = $service->grade(fakeGradingItems(), GradingLevel::Strict);

    expect(array_column($first->grades, 'score'))
        ->each->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100)
        ->and(array_column($first->grades, 'score'))->toBe(array_column($second->grades, 'score'));
});

test('フィードバックの3項目がすべて入っている', function () {
    $grade = (new FakeGradingService)->grade(fakeGradingItems(), GradingLevel::Normal)->grades[0];

    expect($grade->goodPoints)->not->toBeEmpty()
        ->and($grade->weakPoints)->not->toBeEmpty()
        ->and($grade->modelAnswer)->not->toBeEmpty();
});

test('API を呼ばないので、利用量は 0 でモデル名は fake', function () {
    $result = (new FakeGradingService)->grade(fakeGradingItems(), GradingLevel::Normal);

    expect($result->model)->toBe('fake')
        ->and([$result->inputTokens, $result->outputTokens, $result->webSearchRequests])->toBe([0, 0, 0]);
});

test('問題がなければ、採点結果も空', function () {
    expect((new FakeGradingService)->grade([], GradingLevel::Normal)->grades)->toBe([]);
});
