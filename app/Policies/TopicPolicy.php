<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Topic;

class TopicPolicy extends Policy
{

    public function update(User $user, Topic $topic)
    {
        //仅允许作者编辑和删除，
        //return true  为允许访问
        //return false  为禁止访问
        return $user->isAuthorOf($topic);
    }

    public function destroy(User $user, Topic $topic)
    {
        return $user->isAuthorOf($topic);
    }
}
