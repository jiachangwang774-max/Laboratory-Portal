<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class SignAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'remark' => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'remark.max' => '审核备注不能超过200字',
        ];
    }
}
