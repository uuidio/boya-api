<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class UserCowCoinLog extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['before_gm_name', 'mobile'];

    /**
     * 追加项目名称
     * @Author swl
     * @return string
     */
    public function getBeforeGmNameAttribute()
    {
        $platform_name = GmPlatform::where('gm_id', '=', $this->before_gm_id)->value('platform_name');

        return !empty($platform_name) ? $platform_name : '';
    }
    /**
     * 追加手机号码
     * @Author swl
     * @return string
     */
    public function getMobileAttribute()
    {
        $mobile =UserAccount::where('id',$this->user_id)->value('mobile');

        return !empty($mobile) ? $mobile : '';
    }
}
