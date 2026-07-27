<?php
// 获取用户签到列表请求
namespace App\Http\Requests\LX;

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
            'page' => 'nullable|integer|min:1',
            'size' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => '页码必须为整数',
            'page.min'     => '页码最小为1',
            'size.integer' => '每页条数必须为整数',
            'size.min'     => '每页条数最小为1',
            'size.max'     => '每页条数最大为100',
        ];
    }
}
