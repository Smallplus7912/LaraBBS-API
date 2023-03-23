<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\Api\VerificationCodeRequest;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class VerificationCodesController extends Controller
{
    public function store(VerificationCodeRequest $request, EasySms $easySms)
    {
        $phone = $request->phone;

        //如果非生产环境，code=1234
        if (!app()->environment('production')) {
            $code = '1234';
        } else {
        //生成4位随机数，左侧补0
            $code = Str_pad(random_int(1, 9999), 4, 0, STR_PAD_LEFT);

        //使用send方法，register短信模板，把code通过阿里云发送到用户手机
            try {
                $request = $easySms->send($phone, [
                'template' => config('easysms.gateways.aliyun.templates.register'),
                'data' => [
                    'code' => $code
                ],
                ]);
            } catch (NoGatewayAvailableException $exception) {
                $message = $exception->getException('aliyun')->getMessage();
                abort(500, $message ?: '短信发送异常');
            }
        }
        //生成个15位随机数key
        $key = Str::random(15);

        //配置缓存key、缓存过期时间：5分钟
        $cacheKey = 'verificationCode_' . $key;
        $expiredAt = now()->addMinutes(5);
        //Cache::put（$key, $value, $expiration）键值、过期时间
        Cache::put($cacheKey, ['phone' => $phone, 'code' => $code], $expiredAt);

        //返回201，15位随机数key和过期时间
        return response()->json([
            'key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
        ])->setStatusCode(201);
        //return response()->json(['test_message' => 'store verification code']);
    }
}
