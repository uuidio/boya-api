<?php

namespace ShopEM\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class GroupManageUser extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $guarded = [];
    protected $appends = ['is_root_text' ,'status_text'];

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

     public function getIsRootTextAttribute()
    {
        $text = [
            0 => '否',    // 否
            1 => '是',    // 是
        ];
        return isset($text[$this->is_root]) ? $text[$this->is_root] : '';
    }

    public function getStatusTextAttribute()
    {
        $text = [
            0 => '否',    // 否
            1 => '是',    // 是
        ];
        return isset($text[$this->status]) ? $text[$this->status] : '';
    }

}