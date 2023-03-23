<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Requests\Api\UserRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Cache;

class UsersController extends Controller
{
    //手机短信验证码注册
    public function store(UserRequest $request)
    {
        //获取用户提交的verification_key，赋值给变量$cachekey
        $cachekey = 'verificationCode_' . $request->verification_key;
        //从缓存中取出正确的verificationCode
        $verifyData = Cache::get($cachekey);
        //如果缓存中取不到，返回403
        if (!$verifyData) {
            abort(403, '验证码已失效');
        }
        //缓存中取到以后，对比缓存中的四位code码与用户提交的四位code码的hash值，匹配则继续，不匹配则报错
        if (!hash_equals($verifyData['code'], $request->verification_code)) {
            throw new AuthenticationException('验证码错误');
        }
        //通过验证后，create创建用户
        $user = User::create([
            'name' => $request->name,
            'phone' => $verifyData['phone'],
            'password' => $request->password,
        ]);
        //清除缓存
        Cache::forget($cachekey);
        //返回新建的用户信息
        return new UserResource($user);
    }
}
