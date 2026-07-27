<?php
// 注册请求
namespace App\Http\Requests\LX;

use App\Helpers\PhoneHelper;
use App\Rules\PasswordStrength;
use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 验证前预处理：清洗手机号（剔除空格、横杠、括号、+86 前缀等）
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => PhoneHelper::clean($this->input('phone')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string|max:50',
            'password' => [
                'required',
                'string',
                new PasswordStrength([
                    'username' => $this->input('username', ''),
                    'phone'    => $this->input('phone', ''),
                ]),
            ],
            'email' => 'required|email|max:50',
            'phone' => ['required', new PhoneNumber],
            'grade' => 'required|string|max:50',
            'major' => 'required|string|max:100',
            'code'  => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => '用户名不能为空',
            'username.max'      => '用户名不能超过50个字符',
            'password.required' => '密码不能为空',
            'email.required'    => '邮箱不能为空',
            'email.email'       => '邮箱格式不正确',
            'email.max'         => '邮箱不能超过50个字符',
            'phone.required'    => '手机号不能为空',
            'grade.required'    => '年级不能为空',
            'grade.max'         => '年级不能超过50个字符',
            'major.required'    => '专业不能为空',
            'major.max'         => '专业不能超过100个字符',
            'code.required'     => '验证码不能为空',
            'code.size'         => '验证码必须为6位',
        ];
    }
}
