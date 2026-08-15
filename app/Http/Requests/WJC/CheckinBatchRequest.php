<?php

namespace App\Http\Requests\WJC;

use Illuminate\Foundation\Http\FormRequest;

class CheckinBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checkinId'    => 'required|integer|exists:course_checkins,checkin_id',
            'studentIds'   => 'required|array|min:1',
            'studentIds.*' => 'required|string|max:30',
        ];
    }

    public function messages(): array
    {
        return [
            'checkinId.required'      => '签到ID不能为空',
            'checkinId.exists'        => '签到不存在',
            'studentIds.required'     => '学号列表不能为空',
            'studentIds.array'        => '学号列表格式不正确',
            'studentIds.min'          => '学号列表不能为空',
            'studentIds.*.required'   => '学号不能为空',
            'studentIds.*.max'        => '学号不能超过30个字符',
        ];
    }
}
