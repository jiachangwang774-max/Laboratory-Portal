<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class SingleAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auditStatus' => 'required|integer|in:1,2',
            'auditRemark' => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'auditStatus.required' => '审核状态不能为空',
            'auditStatus.in'       => '审核状态只能为1(通过)或2(驳回)',
            'auditRemark.max'      => '审核备注不能超过200个字符',
        ];
    }
}
