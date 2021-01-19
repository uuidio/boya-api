<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class SecKillApplie extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['is_apply', 'validstatus','validstatus_sign'];


    /**
     * 是否可报名
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getIsApplyAttribute()
    {
        $nowTime = date('Y-m-d H:i:s', time());

        if ($this->apply_end_time > $nowTime) {
            $res = '1';
        } else {
            $res = '0';
        }
        return $res;
    }


    /**
     * 是否在活动中
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getValidstatusAttribute()
    {
        $nowTime = date('Y-m-d H:i:s', time());

        if ( $this->start_time < $nowTime && $nowTime <= $this->end_time ) {
            $res = '抢购中';
        }elseif($this->apply_end_time > $nowTime) {
            $res = '报名中';
        }elseif($this->start_time > $nowTime && $this->apply_end_time < $nowTime) {
            $res = '未开始';
        }else{
            $res = '活动已经结束';
        }
        return $res;
    }



    /**
     * 活动标识
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getValidstatusSignAttribute()
    {
        $nowTime = date('Y-m-d H:i:s', time());

        if ( $this->start_time < $nowTime && $nowTime <= $this->end_time ) {
            $res['sign'] = '1';
            $res['sign_text'] = '抢购中';
        }elseif($this->start_time > $nowTime) {
            $res['sign'] = '-1';
            $res['sign_text'] = '未开始';
        }else{
            $res['sign'] = '0';
            $res = '已结束';
        }
        return $res;
    }


}
