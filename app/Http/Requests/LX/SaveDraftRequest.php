<?php
// 报名草稿保存请求（允许部分字段）
namespace App\Http\Requests\LX;

use Illuminate\Foundation\Http\FormRequest;

class SaveDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id'        => 'required|string|max:50',
            'name'              => 'nullable|string|max:50',
            'department'        => 'nullable|integer|in:1,2',
            'college'           => 'nullable|string|max:100',
            'major'             => 'nullable|string|max:100',
            'class_name'        => 'nullable|string|max:100',
            'phone'             => 'nullable|string|max:20',
            'self_introduction' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required'      => '学号不能为空',
            'student_id.max'           => '学号不能超过50个字符',
            'name.max'                 => '姓名不能超过50个字符',
            'department.in'            => '报名部门选择有误',
            'college.max'              => '学院不能超过100个字符',
            'major.max'                => '专业不能超过100个字符',
            'class_name.max'           => '班级不能超过100个字符',
            'phone.max'                => '手机号不能超过20个字符',
            'self_introduction.max'    => '自我介绍不能超过2000个字符',
        ];
    }
}
