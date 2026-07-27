<?php
// 统一发送验证码请求（注册 / 重置密码 / 注销账号）
namespace App\Http\Requests\LX;

use App\Enums\VerifyCodeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:50',
            'type'  => ['required', 'integer', Rule::enum(VerifyCodeType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => '邮箱不能为空',
            'email.email'    => '邮箱格式不正确',
            'email.max'      => '邮箱不能超过50个字符',
            'type.required'  => '验证码类型不能为空',
            'type.integer'   => '验证码类型格式不正确',
        ];
    }
}
