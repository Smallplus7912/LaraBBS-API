<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Http\Requests\Api\CaptchaRequest;

class CaptchasController extends Controller
{
    //生成图片验证码
    public function store(CaptchaRequest $request, CaptchaBuilder $captchaBuilder)
    {
        $key = Str::random(15);
        //用户手机号
        $phone = $request->phone;
        //构建一个验证码图像
        $captcha = $captchaBuilder->build();
        //配置缓存
        $cacheKey = 'captcha_' . $key;
        $cacheExpired = now()->addMinutes(2);
        //getPhrase()方法获取验证码原始文本
        //inline()方法获取base64图片验证码
        Cache::put($cacheKey, ['phone' => $phone,'code' => $captcha->getPhrase()], $cacheExpired);
        $request = [
            'captcha_key' => $key,
            'expired_at' => $cacheExpired->toDateTimeString(),
            'captcha_image_content' => $captcha->inline()
        ];
        //相应信息：
        return response()->json($request)->setStatusCode(201);
    }
}
