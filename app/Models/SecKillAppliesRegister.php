<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class SecKillAppliesRegister extends Model
{
    //
    protected $guarded = [];

    protected $appends = ['shop_name', 'apply_status', 'activity_name', 'verify_status_text', 'is_apply'];


    /**
     * 是否有效
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getShopNameAttribute()
    {
        $shop_info = Shop::where('id', '=', $this->shop_id)->first();

        return isset($shop_info->shop_name) ? $shop_info->shop_name : '';
    }


    /**
     * 活动名称
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getActivityNameAttribute()
    {
        $info = SecKillApplie::where('id', '=', $this->seckill_ap_id)->first();

        return isset($info->activity_name) ? $info->activity_name : '';
    }


    /**
     * 审核状态
     *
     * @Author djw
     * @return mixed
     */
    public function getVerifyStatusTextAttribute()
    {
//        $status_text = [
//            0 => '待审核',
//            1 => '审核被拒绝',
//            2 => '审核通过'
//        ];
        $status_text = '';
        switch ($this->verify_status) {
            case '0':
                $status_text = '待审核';
                break;
            case '1':
                $status_text = '审核被拒绝';
                break;
            case '2':
                $status_text = '审核通过';
                break;
        }

        return $status_text;
    }

//    /**
//     * 有效状态
//     *
//     * @Author djw
//     * @return mixed
//     */
//    public function getValidStatusTextAttribute()
//    {
//        if ($this->valid_status == '0') {
//            $status_text = '失效';
//        } else {
//            $status_text = '有效';
//        }
//
//        return $status_text;
//    }

    /**
     * 是否可以申请
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getApplyStatusAttribute()
    {
        if($this->verify_status != 2){
            $info = SecKillApplie::where('id', '=', $this->seckill_ap_id)->first();
            $time = date('Y-m-d H:i:s', time());


            if ($info['apply_begin_time'] <= $time && $time < $info['apply_end_time']) {
                    $apply_status['staus'] = 1;
                    $apply_status['text'] = '有效';
            }else{
                $apply_status['staus'] = 0;
                $apply_status['text'] = '失效';
            }
        }else{
            $apply_status['staus'] = 0;
            $apply_status['text'] = '失效';
        }
        return $apply_status;
    }

    /**
     * 是否可报名
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getIsApplyAttribute()
    {
        $info = SecKillApplie::where('id', '=', $this->seckill_ap_id)->first();

        if (!$info) {
            return 0;
        }

        $nowTime = date('Y-m-d H:i:s', time());

        if ($info['apply_end_time'] > $nowTime) {
            $res = '1';
        } else {
            $res = '0';
        }
        return $res;
    }

}
