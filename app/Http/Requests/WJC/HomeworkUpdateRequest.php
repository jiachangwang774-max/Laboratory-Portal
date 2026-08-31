<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class HomeworkUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'courseId'        => 'nullable|integer|exists:train_course,course_id',
            'homeworkTitle'   => 'nullable|string|max:100',
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
}
