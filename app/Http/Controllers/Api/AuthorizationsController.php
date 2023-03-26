<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Overtrue\LaravelSocialite\Socialite;
use Illuminate\Auth\AuthenticationException;
use App\Http\Requests\Api\AuthorizationRequest;
use App\Http\Requests\Api\SocialAuthorizationRequest;

class AuthorizationsController extends Controller
{
    //用户通过微信（第三方）登录
    public function socialStore($type, SocialAuthorizationRequest $request)
    {
        // 暂且称$driver为社交登录驱动
        $driver = Socialite::create($type);

        try {
            //如果请求中提交了code,要么通过wechat方式，提供openid和access token
            if ($code = $request->code) {
                //$oauthUser被赋值为对应用户的详细信息
                //userFromCode($code)通过用户提交的code，去数据库中获取获取code对应的用户信息
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
        //使用login()方法生成token
        $token = auth('api')->login($user);
        return $this->responedWithToken($token)->setStatusCode('201');
    }

    //用户输入用户名密码登录
    public function store(AuthorizationRequest $request)
    {
        //用户提交的可能是手机号也可能是邮箱
        $username = $request->username;
        //filter_var验证：验证$username是否为FILTER_VALIDATE_EMAIL
        filter_var($username, FILTER_VALIDATE_EMAIL) ?
            //是邮箱的话，存入$credentials中的email字段
            $credentials['email'] = $username :
            //不是邮箱的话默认为是手机号，并存入phone中
            $credentials['phone'] = $username;

        $credentials['password'] = $request->password;
        /*调用一个Auth::guard()方法，获取一个名为api的认证guard实例
         * 然后用attempt()方法，将$credentials数组里的内容与数据库中的数据进行比较
         * 成功的话，返回一个$token令牌,用于后续API请求中身份校验和授权
         * 失败则抛出异常*/
        if (! $token = Auth::guard('api')->attempt($credentials)) {
            throw new AuthenticationException('用户名或密码错误');
        }
        return $this->responedWithToken($token)->setStatusCode(201);
    }

    //将响应信息封装到函数中
    protected function responedWithToken($token)
    {
        //expires_in：token的过期时间
        //每个guard对象都有一个factory()方法，它返回一个Token Generator类实例，Token Generator类是负责生成和管理令牌token的类
        //getTTL()是获取guard对象的默认过期时间，即api对象的过期时间，默认是60分钟，*60即转换成秒返回给用户
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    //刷新token
    public function update(){
        $token = \auth('api')->refresh();
        return $this->responedWithToken($token);
    }

    //删除token
    public function destroy(){
        \auth('api')->logout();
        return response(null,204);
    }
}
