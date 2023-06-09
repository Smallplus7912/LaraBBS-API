<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

//验证码表单验证类
class VerificationCodeRequest extends FormRequest
{
    public function rules()
    {
        return [
            'captcha_key' => 'required|string',
            'captcha_code' => 'required|string',
        ];
    }
    public function attributes()
    {
        return[
            'captcha_key' => '图片验证码key',
            'captcha_code' => '图片验证码',
        ];
    }
}
