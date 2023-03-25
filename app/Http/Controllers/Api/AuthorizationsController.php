<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Overtrue\LaravelSocialite\Socialite;
use Illuminate\Auth\AuthenticationException;
use App\Http\Requests\Api\SocialAuthorizationRequest;

class AuthorizationsController extends Controller
{
    //第三方登录
    public function socialStore($type, SocialAuthorizationRequest $request)
    {
        // $type在路由中只定义了wechat
        $driver = Socialite::create($type);

        try {
            //客户端要么提交授权码code，要么通过wechat方式，提供openid和access token
            if ($code = $request->code) {
                $oauthUser = $driver->userFromCode($code);
            } else {
                if ($type == 'wechat') {
                    $driver->withOpenid($request->openid);
                }
                $oauthUser = $driver->userFromToken($request->access_token);
            }
        } catch (\Exception $e) {
            throw new AuthenticationException('参数错误，获取用户信息失败');
        }
        //getId()方法获取openid
        if (!$oauthUser->getId()) {
            throw new AuthenticationException('参数错误，获取用户信息失败');
        }
        switch ($type) {
            case 'wechat':
                //获取unionid，没有则赋值为null
                $unionid = $oauthUser->getRaw()['unionid'] ?? null;
                //如果有unionid，取unionid，没有的话取openid
                if ($unionid) {
                    //where方法：where(查询的字段名，需要匹配的值)
                    //first方法：获取符合条件的第一条记录，如果没有符合条件的，返回null
                    $user = User::where('weixin_unionid', $unionid)->first();
                } else {
                    $user = User::where('weixin_openid', $oauthUser->getId())->first();
                }
                //如果数据库中查询不到，则创建用户
                if (!$user) {
                    $user = User::create([
                        'name' => $oauthUser->getNickname(),
                        'avatar' => $oauthUser->getAvatar(),
                        'weixin_openid' => $oauthUser->getId(),
                        'weixin_unionid' => $unionid,
                    ]);
                }
                break;
        }
        //返回用户id
        return response()->json(['token' => $user->id]);
    }
}
