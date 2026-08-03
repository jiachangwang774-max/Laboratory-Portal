<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class CourseCreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'courseName' => 'required|string|max:100',
            'courseDesc' => 'nullable|string',
            'instructor' => 'nullable|string|max:50',
            'courseDate' => 'nullable|string|max:100',
            'location'   => 'nullable|string|max:200',
            'coverImg'   => 'nullable|string|max:255',
            'cover'      => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'startTime'  => 'nullable|date',
            'endTime'    => 'nullable|date',
            'maxSign'    => 'nullable|integer|min:1',
            'groupCount' => 'nullable|integer|min:1|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'courseName.required' => '课程名称不能为空',
            'courseName.max'      => '课程名称不能超过100个字符',
        ];
    }
}
