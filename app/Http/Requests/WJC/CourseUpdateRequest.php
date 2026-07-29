<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class CourseUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'courseName' => 'nullable|string|max:100',
            'courseDesc' => 'nullable|string',
            'coverImg'   => 'nullable|string|max:255',
            'startTime'  => 'nullable|date',
            'endTime'    => 'nullable|date',
            'maxSign'    => 'nullable|integer|min:1',
            'status'     => 'nullable|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return ['status.in' => '状态只能为0或1'];
    }
}
