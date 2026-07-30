<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class CheckinCreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'courseId'  => 'required|integer|exists:train_course,course_id',
            'sessionId' => 'nullable|integer|exists:course_sessions,session_id',
            'duration'  => 'nullable|integer|min:1|max:120',
        ];
    }

    public function messages(): array
    {
        return [
            'courseId.required' => '课程ID不能为空',
            'duration.max'      => '签到时长不能超过120分钟',
        ];
    }
}
