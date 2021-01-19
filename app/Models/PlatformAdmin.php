<?php
/**
 * @Filename        PlatformAdmin.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;


class PlatformAdmin extends Authenticatable
{
    use HasMultiAuthApiTokens, Notifiable;

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
        return $this->where('username', $username)->first();
    }
}
