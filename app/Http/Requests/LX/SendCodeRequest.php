<?php
// 发送验证码请求
namespace App\Http\Requests\LX;

use App\Helpers\PhoneHelper;
use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class SendCodeRequest extends FormRequest
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
            'phone' => ['required', new PhoneNumber],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => '手机号不能为空',
        ];
    }
}
