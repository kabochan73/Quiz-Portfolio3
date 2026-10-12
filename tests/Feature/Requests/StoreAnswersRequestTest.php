<?php

use App\Http\Requests\StoreAnswersRequest;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;

/**
 * セクションへの回答の送信として入力をチェックし、エラーを返す(コントローラはまだないので、FormRequest を直接動かす)。
 *
 * @return array<string, list<string>>
 */
function answerErrors(Section $section, array $input): array
{
    $request = StoreAnswersRequest::create("/sections/{$section->id}/answers", 'POST', $input);
    // ルートモデルバインディングの代わりに、URL の {section} にセクションを入れておく
    $route = (new Route('POST', 'sections/{section}/answers', []))->bind($request);
    $route->setParameter('section', $section);
    $request->setRouteResolver(fn () => $route);
    $request->setContainer(app())->setRedirector(app('redirect'));

    try {
        $request->validateResolved();
    } catch (ValidationException $e) {
        return $e->errors();
    }

    return [];
}

/**
 * @param  iterable<Question>  $questions
 */
function answersFor(iterable $questions, string $body = '回答です'): array
{
    return collect($questions)->map(fn (Question $q) => ['question_id' => $q->id, 'body' => $body])->values()->all();
}

beforeEach(function () {
    $this->section = Section::factory()->create();
    $this->questions = Question::factory()->count(3)->for($this->section)->create();
});

test('全問モードで、セクションの全問に回答していれば通る', function () {
    expect(answerErrors($this->section, [
        'grading_level' => 'normal',
        'mode' => 'all',
        'answers' => answersFor($this->questions),
    ]))->toBe([]);
});

test('問題の順番が入れ替わっていても、組み合わせが同じなら通る', function () {
    expect(answerErrors($this->section, [
        'grading_level' => 'strict',
        'mode' => 'all',
        'answers' => answersFor($this->questions->reverse()),
    ]))->toBe([]);
});

test('採点レベルと種別は、決められた値だけ', function () {
    $errors = answerErrors($this->section, [
        'grading_level' => 'hard',
        'mode' => 'random',
        'answers' => answersFor($this->questions),
    ]);

    expect(array_keys($errors))->toBe(['grading_level', 'mode']);
});

test('未入力の回答があれば「Please answer all questions.」を出す', function () {
    // screens.md 4.3
    $answers = answersFor($this->questions);
    $answers[1]['body'] = '';

    expect(answerErrors($this->section, ['grading_level' => 'normal', 'mode' => 'all', 'answers' => $answers]))
        ->toBe(['answers.1.body' => ['Please answer all questions.']]);
});

test('回答は 5000 文字まで', function () {
    $answers = answersFor($this->questions, str_repeat('あ', 5000));
    $tooLong = answersFor($this->questions, str_repeat('あ', 5001));

    expect(answerErrors($this->section, ['grading_level' => 'normal', 'mode' => 'all', 'answers' => $answers]))->toBe([])
        ->and(answerErrors($this->section, ['grading_level' => 'normal', 'mode' => 'all', 'answers' => $tooLong]))
        ->toHaveKeys(['answers.0.body', 'answers.1.body', 'answers.2.body']);
});

test('問題の組み合わせが対象と違えば止める', function (string $case) {
    $answered = match ($case) {
        'missing' => $this->questions->take(2),
        'foreign' => $this->questions->take(2)->push(Question::factory()->create()),
    };

    $errors = answerErrors($this->section, [
        'grading_level' => 'normal',
        'mode' => 'all',
        'answers' => answersFor($answered),
    ]);

    expect($errors['answers'] ?? [])->toBe(['The questions have changed. Please reload the page.']);
})->with([
    '1問足りない' => 'missing',
    '別のセクションの問題が混ざる' => 'foreign',
]);

test('同じ問題への回答が重なっていたら止める', function () {
    $answers = answersFor($this->questions);
    $answers[2]['question_id'] = $answers[0]['question_id'];

    expect(answerErrors($this->section, ['grading_level' => 'normal', 'mode' => 'all', 'answers' => $answers]))
        ->toHaveKeys(['answers.0.question_id', 'answers.2.question_id']);
});

test('回答が1つもなければ止める', function () {
    expect(answerErrors($this->section, ['grading_level' => 'normal', 'mode' => 'all', 'answers' => []]))
        ->toHaveKey('answers');
});

test('苦手モードでは、送信時点の苦手問題だけに回答していれば通る', function () {
    [$q1, $q2, $q3] = $this->questions;
    scoredAttempt($this->section, [$q1->id => 30, $q2->id => 90, $q3->id => 50]);

    $weakOnly = ['grading_level' => 'normal', 'mode' => 'weak', 'answers' => answersFor([$q1, $q3])];
    $all = ['grading_level' => 'normal', 'mode' => 'weak', 'answers' => answersFor($this->questions)];

    expect(answerErrors($this->section, $weakOnly))->toBe([])
        ->and(answerErrors($this->section, $all))->toHaveKey('answers');
});
