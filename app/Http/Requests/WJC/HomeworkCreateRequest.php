<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class HomeworkCreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'courseId'        => 'required|integer|exists:train_course,course_id',
            'homeworkTitle'   => 'required|string|max:100',
            'homeworkContent' => 'nullable|string',
            'deadline'        => 'nullable|date',
            'groupName'       => 'nullable|string|in:一班,二班,三班',
        ];
    }

    public function messages(): array
    {
        return [
            'courseId.required'        => '课程ID不能为空',
            'courseId.exists'          => '课程不存在',
            'homeworkTitle.required'   => '作业标题不能为空',
        ];
    }
}
