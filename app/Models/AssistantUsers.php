<?php

namespace ShopEM\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AssistantUsers extends Authenticatable
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
