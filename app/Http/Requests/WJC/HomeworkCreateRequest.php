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
            'questions'       => 'nullable|array',
            'questions.*.id'   => 'required|string|max:50',
            'questions.*.type' => 'required|in:choice,judge,essay',
            'questions.*.title'=> 'required|string|max:500',
            'questions.*.options' => 'required_if:questions.*.type,choice|array|min:2',
            'questions.*.answer'  => 'required_if:questions.*.type,choice,judge,essay',
            'questions.*.score'   => 'nullable|integer|min:0',
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
