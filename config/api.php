<?php

//接口频率限制
return [
    'rate_limits' => [
        //访问限制，30次1分钟
        'access' => env('RATE_LIMITS', '30,1'),
        //登录相关，10次1分钟
        'sign' => env('SIGN_RATE_LIMITS', '10,1')
    ],
];
