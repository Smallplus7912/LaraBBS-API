<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, MustVerifyEmailTrait;

    use Notifiable {
        notify as protected laravelNotify;
    }

    //加载hasroles
    use HasRoles;
    //边缘活跃用户
    use Traits\ActiveUserHelper;

    //最后登录时间
    use Traits\LastActivedAtHelper;

    public function notify($instance)
    {
        // 如果要通知的人是当前用户，就不必通知了！
        if ($this->id == Auth::id()) {
            return;
        }

        // 只有数据库类型通知才需提醒，直接发送 Email 或者其他的都 Pass
        if (method_exists($instance, 'toDatabase')) {
            $this->increment('notification_count');
        }

        $this->laravelNotify($instance);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'introduction',
        'phone'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    //新增与话题模型的关联
    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    //判断是否为作者
    //引用位置：topicpolilcy
    public function isAuthorOf($model)
    {
        return $this->id == $model->user_id;
    }

    //用户可以拥有多条评论
    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    public function markAsRead()
    {
        $this->notification_count = 0;
        $this->save();
        $this->unreadNotifications->markAsRead();
    }

    public function setPasswordAttribute($value)
    {
        // 如果值的长度等于 60，即认为是已经做过加密的情况
        if (strlen($value) != 60) {

            // 不等于 60，做密码加密处理
            $value = bcrypt($value);
        }

        $this->attributes['password'] = $value;
    }

    public function setAvatarAttribute($path)
    {
        // 如果不是 `http` 子串开头，那就是从后台上传的，需要补全 URL
        if ( ! Str::startsWith($path, 'http')) {

            // 拼接完整的 URL
            $path = config('app.url') . "/uploads/images/avatars/$path";
        }

        $this->attributes['avatar'] = $path;
    }
}
