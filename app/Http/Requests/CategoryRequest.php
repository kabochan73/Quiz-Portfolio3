<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * カテゴリの作成・編集(requirements.md 3.2)。
 */
class CategoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // categories.name は varchar(100)(architecture.md 2.1)
            'name' => ['required', 'string', 'max:100'],
        ];
    }
}
