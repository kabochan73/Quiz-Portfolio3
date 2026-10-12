<?php

namespace App\Http\Requests;

use App\Enums\AttemptMode;
use App\Enums\GradingLevel;
use App\Models\Section;
use App\Queries\WeakQuestions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * 回答の送信(requirements.md 3.3、architecture.md 4.2)。
 * 1回の送信で、対象の問題すべてに回答する。answers の並び順が、挑戦の中での順番(position)になる。
 */
class StoreAnswersRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'grading_level' => ['required', Rule::enum(GradingLevel::class)],
            'mode' => ['required', Rule::enum(AttemptMode::class)],
            'answers' => ['required', 'array', 'list'],
            'answers.*' => ['array:question_id,body'],
            'answers.*.question_id' => ['required', 'integer', 'distinct'],
            // 回答は 5000 文字まで(architecture.md 2.5)
            'answers.*.body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // screens.md 4.3「回答フォーム」の未入力の文言
            'answers.*.body.required' => 'Please answer all questions.',
        ];
    }

    /**
     * 送られた問題の組み合わせが、種別から決まる対象の問題とちょうど一致するかを確かめる。
     * 別のセクションの問題が混ざっていたり、フォームを開いたあとに問題が追加・削除されたりした場合に止める。
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // 形のチェックで失敗していれば、組み合わせまでは見ない
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $sent = collect($this->input('answers'))->pluck('question_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                $targets = collect($this->targetQuestionIds())->sort()->values()->all();

                if ($sent !== $targets) {
                    $validator->errors()->add('answers', 'The questions have changed. Please reload the page.');
                }
            },
        ];
    }

    /**
     * 種別から決まる、回答すべき問題(all → セクションの全問、weak → 送信時点の苦手問題)。
     *
     * @return list<int>
     */
    public function targetQuestionIds(): array
    {
        /** @var Section $section */
        $section = $this->route('section');

        return match (AttemptMode::from($this->input('mode'))) {
            AttemptMode::All => $section->questions()->pluck('id')->all(),
            AttemptMode::Weak => app(WeakQuestions::class)->idsFor($section),
        };
    }
}
