<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\Api\VerificationCodeRequest;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class VerificationCodesController extends Controller
{
    public function send(VerificationCodeRequest $request, EasySms $easySms)
    {
        $captchaCacheKey = 'captcha_'.$request->captcha_key;
        $captchaData = Cache::get($captchaCacheKey);
        if (!$captchaData){
            abort(403,'图片验证码已失效');
        }
        if(!hash_equals(strtolower($captchaData['code']),$request->captcha_code)){
            Cache::forget($captchaCacheKey);
            throw new AuthenticationException('验证码错误');
        }
        $phone = $captchaData['phone'];

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
        $smsKey = 'verificationCode_'.Str::random(15);
        $smsCacheKey = 'verificationCode_'.$smsKey;
        $expiredAt = now()->addMinutes(5);
        //Cache::put（$key, $value, $expiration）键值、过期时间
        Cache::put($smsCacheKey,['phone'=>$phone,'code'=>$code],$expiredAt);
        Cache::forget($captchaCacheKey);
        //返回201，15位随机数key和过期时间
        return response()->json([
            'key' => $smsKey,
            'expired_at' => $expiredAt->toDateTimeString(),
        ])->setStatusCode(201);
        //return response()->json(['test_message' => 'store verification code']);
    }
}
