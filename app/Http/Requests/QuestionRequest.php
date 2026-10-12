<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 問題の作成・編集(requirements.md 3.2)。編集できるのは問題文だけ。所属するセクションは URL で決まる。
 */
class QuestionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // 問題文は 2000 文字まで(architecture.md 2.3)
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        // エラーメッセージで「body」ではなく、画面のラベル(screens.md 4.3)と同じ「question」と出す
        return ['body' => 'question'];
    }
}
