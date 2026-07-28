<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class SignListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'        => 'nullable|integer|min:1',
            'size'        => 'nullable|integer|min:1|max:100',
            'auditStatus' => 'nullable|integer|in:0,1,2',
            'courseId'    => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer'       => '页码必须为整数',
            'size.max'           => '每页条数不能超过100',
            'auditStatus.in'     => '审核状态只能为0、1或2',
            'courseId.integer'   => '课程ID必须为整数',
        ];
    }
}
