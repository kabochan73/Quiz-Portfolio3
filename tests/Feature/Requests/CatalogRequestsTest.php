<?php

use App\Http\Requests\CategoryRequest;
use App\Http\Requests\QuestionRequest;
use App\Http\Requests\SectionRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

/**
 * FormRequest のルールで入力をチェックし、エラーを返す(コントローラはまだないので、ルールを直接使う)。
 *
 * @param  class-string<FormRequest>  $requestClass
 * @return array<string, list<string>>
 */
function validationErrors(string $requestClass, array $input): array
{
    $request = new $requestClass;

    return Validator::make($input, $request->rules(), $request->messages(), $request->attributes())
        ->errors()
        ->toArray();
}

test('カテゴリ名とセクション名は、1〜100文字で必須', function (string $requestClass) {
    expect(validationErrors($requestClass, ['name' => 'Networking']))->toBe([])
        ->and(validationErrors($requestClass, ['name' => str_repeat('a', 100)]))->toBe([])
        ->and(validationErrors($requestClass, ['name' => '']))->toBe(['name' => ['The name field is required.']])
        ->and(validationErrors($requestClass, ['name' => str_repeat('a', 101)]))
        ->toBe(['name' => ['The name field must not be greater than 100 characters.']]);
})->with([CategoryRequest::class, SectionRequest::class]);

test('問題文は、1〜2000文字で必須。エラーでは「question」と出す', function () {
    expect(validationErrors(QuestionRequest::class, ['body' => 'TCPとUDPの違いは?']))->toBe([])
        ->and(validationErrors(QuestionRequest::class, ['body' => str_repeat('あ', 2000)]))->toBe([])
        ->and(validationErrors(QuestionRequest::class, ['body' => '']))->toBe(['body' => ['The question field is required.']])
        ->and(validationErrors(QuestionRequest::class, ['body' => str_repeat('あ', 2001)]))
        ->toBe(['body' => ['The question field must not be greater than 2000 characters.']]);
});

test('所属を変える項目は、ルールにないので受け取らない', function () {
    // requirements.md 3.2: 所属は作成時に決まったまま変えられない。validated() に残らない
    expect(array_keys((new SectionRequest)->rules()))->toBe(['name'])
        ->and(array_keys((new QuestionRequest)->rules()))->toBe(['body']);
});
