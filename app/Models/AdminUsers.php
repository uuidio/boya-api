<?php
/**
 * @Filename AdminUsers.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUsers extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $guarded = [];
    protected $appends = ['is_root_text' ,'status_text','gm_text'];

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

    /**
     * 追加项目说明
     * @Author hfh
     * @return mixed|string
     */
    public function getGmTextAttribute()
    {
        $text=GmPlatform::where('gm_id',$this->gm_id)->value('platform_name');
        return $text ? $text : '-';
    }
}
