<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class CourseListRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'page'       => 'nullable|integer|min:1',
            'size'       => 'nullable|integer|min:1|max:100',
            'courseName' => 'nullable|string|max:100',
            'status'     => 'nullable|integer|in:0,1',
        ];
    }
}
