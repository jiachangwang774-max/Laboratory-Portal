<?php
// 重置密码请求
namespace App\Http\Requests\LX;

use App\Helpers\PhoneHelper;
use App\Models\SysUser;
use App\Rules\PasswordStrength;
use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class ResetPwdRequest extends FormRequest
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
        // 根据手机号查找用户，用于密码关联信息检测
        $phone  = $this->input('phone', '');
        $user   = SysUser::where('phone', $phone)->first();

        return [
            'phone'  => ['required', new PhoneNumber],
            'code'   => 'required|string|size:6',
            'newPwd' => [
                'required',
                'string',
                new PasswordStrength([
                    'username' => $user?->username ?? '',
                    'phone'    => $phone,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'  => '手机号不能为空',
            'code.required'   => '验证码不能为空',
            'code.size'       => '验证码必须为6位',
            'newPwd.required' => '新密码不能为空',
        ];
    }
}
