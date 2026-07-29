<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class CourseStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['status' => 'required|integer|in:0,1'];
    }

    public function messages(): array
    {
        return ['status.required' => '状态不能为空', 'status.in' => '状态只能为0或1'];
    }
}
