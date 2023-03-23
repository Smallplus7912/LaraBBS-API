<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VerificationCodesController;
use App\Http\Controllers\Api\UsersController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//L03-API学习
Route::prefix('v1')->name('api.v1.')->group(function () {
    //使用sign配置的频率限制:5/1
    Route::middleware('throttle:' . config('api.rate_limits.sign'))->group(function () {
        //短信验证码
        Route::post('verificationCodes', [VerificationCodesController::class,'send'])->name('verificationCodes.send');
        //用户注册
        Route::post('users', [UsersController::class,'store'])->name('users.store');
    });
    //使用access配置的频率限制:30/1
    Route::middleware('throttle' . config('api.rate_limits.access'))->group(function () {
        //
    });
});
