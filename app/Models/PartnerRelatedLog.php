<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerRelatedLog extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['wx_info', 'reward_info'];


    /**
     * 微信信息
     * @Author hfh_wind
     * @return int|string
     */
    public function getWxInfoAttribute()
    {
        $info = WxUserinfo::where('user_id', $this->user_id)->select('nickname', 'headimgurl')->first();
        return $info;
    }

    /**
     * (小店分成部分)成交额
     * @Author hfh_wind
     * @return int|string
     */
    public function getRewardInfoAttribute()
    {
        $value = TradeEstimates::where(['pid'=> $this->partner_id,'user_id'=>$this->user_id])->where('type','=',3)->where('status',
            0)->selectRaw('IFNULL(sum(reward_value),0) as reward_value,count(distinct tid) as count ')->first();

        return $value;
    }

}
