<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class HomeworkListRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'page'     => 'nullable|integer|min:1',
            'size'     => 'nullable|integer|min:1|max:100',
            'courseId' => 'nullable|integer|min:1',
        ];
    }
}
