<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

//用户登录表单验证规则
class UserRequest extends FormRequest
{
//    public function authorize()
//    {
//        return false;
//    }

    public function rules()
    {
        return [
            'name' => 'required|between:3,25|regex:/^[A-Za-z0-9\-\_]+$/|unique:users,name',
            'password' => 'required|alpha_dash|min:6',
            'verification_key' => 'required|string',
            'verification_code' => 'required|string'
        ];
    }

    //为上面两个verification字段提供翻译信息
    public function attributes()
    {
        return [
            'verification_key' => '短信验证码key',
            'verification_code' => '短信验证码'
        ];
    }
}
