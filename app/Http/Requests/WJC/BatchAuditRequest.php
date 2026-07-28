<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class BatchAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signIdList'  => 'required|array|min:1',
            'signIdList.*' => 'required|integer|min:1',
            'auditStatus' => 'required|integer|in:1,2',
            'auditRemark' => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'signIdList.required'    => '报名ID列表不能为空',
            'signIdList.array'       => '报名ID列表格式不正确',
            'signIdList.min'         => '至少选择一个报名记录',
            'signIdList.*.required'  => '报名ID不能为空',
            'signIdList.*.integer'   => '报名ID必须为整数',
            'auditStatus.required'   => '审核状态不能为空',
            'auditStatus.in'         => '审核状态只能为1(通过)或2(驳回)',
            'auditRemark.max'        => '审核备注不能超过200个字符',
        ];
    }
}
