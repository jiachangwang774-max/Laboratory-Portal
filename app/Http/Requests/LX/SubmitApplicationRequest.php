<?php
// 报名提交请求（所有字段必填）
namespace App\Http\Requests\LX;

use Illuminate\Foundation\Http\FormRequest;

class SubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => 'required|string|max:50',
            'student_id'        => 'required|string|max:50',
            'department'        => 'required|integer|in:1,2',
            'college'           => 'required|string|max:100',
            'major'             => 'required|string|max:100',
            'class_name'        => 'required|string|max:100',
            'phone'             => 'required|string|max:20',
            'self_introduction' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'               => '姓名不能为空',
            'name.max'                    => '姓名不能超过50个字符',
            'student_id.required'         => '学号不能为空',
            'student_id.max'              => '学号不能超过50个字符',
            'department.required'         => '请选择报名部门',
            'department.in'               => '报名部门选择有误',
            'college.required'            => '学院不能为空',
            'college.max'                 => '学院不能超过100个字符',
            'major.required'              => '专业不能为空',
            'major.max'                   => '专业不能超过100个字符',
            'class_name.required'         => '班级不能为空',
            'class_name.max'              => '班级不能超过100个字符',
            'phone.required'              => '手机号不能为空',
            'phone.max'                   => '手机号不能超过20个字符',
            'self_introduction.required'  => '自我介绍不能为空',
            'self_introduction.max'       => '自我介绍不能超过2000个字符',
        ];
    }
}
