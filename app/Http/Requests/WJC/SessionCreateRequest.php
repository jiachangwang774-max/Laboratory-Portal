<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class SessionCreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'courseId'    => 'required|integer|exists:train_course,course_id',
            'title'       => 'required|string|max:100',
            'content'     => 'nullable|string',
            'sessionDate' => 'nullable|date',
            'endTime'     => 'nullable|date',
            'location'    => 'nullable|string|max:200',
            'instructor'  => 'nullable|string|max:50',
            'sortOrder'   => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'courseId.required' => '课程ID不能为空',
            'title.required'    => '安排标题不能为空',
        ];
    }
}
