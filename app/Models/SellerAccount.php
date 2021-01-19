<?php
/**
 * @Filename        SellerAccount.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class SellerAccount extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $guarded = [];

    protected $appends = ['seller_type_text' ,'status_text'];

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

    /**
     * 重置验证password字段，如果密码不需要重置，则不用管一下代码
     *
     * @Author moocde <mo@mocode.cn>
     * @param $password
     * @return bool
     */
//    public function validateForPassportPasswordGrant($password)
//    {
//        //如果请求密码等于数据库密码 返回true（此为实例，根据自己需求更改）
//        if($password == $this->password){
//            return true;
//        }
//        return false;
//    }

    public function getStatusTextAttribute()
    {
        $text = [
            0 => '否',    // 否
            1 => '是',    // 是
        ];
        return isset($text[$this->status]) ? $text[$this->status] : '';
    }

    public function getSellerTypeTextAttribute()
    {
        $text = [
            0 => '店主',    // 店主
            1 => '店员',    // 店员
        ];
        return isset($text[$this->seller_type]) ? $text[$this->seller_type] : '';
    }
}
