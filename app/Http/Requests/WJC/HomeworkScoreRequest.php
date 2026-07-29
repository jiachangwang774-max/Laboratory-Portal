<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class HomeworkScoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'score'  => 'required|integer|min:0|max:100',
            'remark' => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'score.required' => '分数不能为空',
            'score.min'      => '分数不能小于0',
            'score.max'      => '分数不能超过100',
        ];
    }
}
