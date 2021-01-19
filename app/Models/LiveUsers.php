<?php

/**
 * @Filename        LiveUsers.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;


class liveUsers extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $guarded = [];

    /**
     * 账号登录验证字段设置，还可以通orWhere增加多个账号登录验证字段
     *
     * @Author moocde <mo@mocode.cn>
     * @param $username
     * @return mixed
     */
    public function findForPassport($username)
    {
        return $this->where('login_account', $username)->first();
    }
}
