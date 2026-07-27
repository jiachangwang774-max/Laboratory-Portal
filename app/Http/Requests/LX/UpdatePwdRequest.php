<?php
// 更新密码请求
namespace App\Http\Requests\LX;

use App\Models\SysUser;
use App\Rules\PasswordStrength;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePwdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var SysUser|null $user */
        $user = auth('user_api')->user();

        return [
            'oldPwd' => 'required|string',
            'newPwd' => [
                'required',
                'string',
                new PasswordStrength([
                    'username' => $user?->username ?? '',
                    'phone'    => $user?->phone ?? '',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'oldPwd.required' => '原密码不能为空',
            'newPwd.required' => '新密码不能为空',
        ];
    }
}
