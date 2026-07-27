<?php
// 报名课程请求
namespace App\Http\Requests\LX;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'courseId' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('train_course', 'course_id')->where('status', 1),
            ],
            'signInfo' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'courseId.required' => '课程ID不能为空',
            'courseId.integer'  => '课程ID必须为整数',
            'courseId.exists'   => '课程不存在或已下架',
            'signInfo.max'      => '报名备注不能超过500个字符',
        ];
    }
}
