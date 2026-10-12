<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * セクションの作成・編集(requirements.md 3.2)。編集できるのは名前だけ。所属するカテゴリは URL で決まる。
 */
class SectionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // sections.name は varchar(100)(architecture.md 2.2)
            'name' => ['required', 'string', 'max:100'],
        ];
    }
}
