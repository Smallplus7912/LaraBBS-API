<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AuthorizationRequest extends FormRequest
{

//    public function authorize()
//    {
//        return false;
//    }

    public function rules()
    {
        return [
            'username' => 'required|string',
            'password' => 'required|min:6',
        ];
    }
}
