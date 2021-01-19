<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class RelatedLogs extends Model
{
    //
    protected $guarded = [];


    protected $appends = ['wx_info', 'status_text', 'order_infos', 'remaining_time', 'mobile'];


    /**
     * 追加微信会员信息
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getWxInfoAttribute()
    {
        $info = WxUserinfo::where('user_id', $this->user_id)->select('nickname', 'headimgurl')->first();
        return !empty($info) ? $info : '';
    }


    /**
     * 追加状态信息
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getStatusTextAttribute()
    {

        return $this->status ? "绑定" : "解绑";
    }


    public function getMobileAttribute()
    {
        $user = UserAccount::where('id', $this->user_id)->select('mobile')->first();
        return $user->mobile;
    }


    /**
     * 追加订单数
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getOrderInfosAttribute()
    {
        $value=TradeEstimates::where(['user_id'=>$this->user_id,'pid'=>$this->pid])->selectRaw('IFNULL(sum(reward_value),0) as reward_value,count(distinct tid) as trade_count ')->first();

        $res = TradeEstimates::where(['user_id' => $this->user_id, 'pid' => $this->pid])->where('status',0)->selectRaw('distinct oid
as oid')->get();
        $seller_price = 0;
        if (count($res) > 0) {
            foreach ($res as $key) {
                $res = TradeOrder::where('oid', $key['oid'])->select('amount')->first();
                if (isset($res['amount'])) {
                    $seller_price += $res['amount'];
                }
            }
        }

        $value['reward_value']=round($seller_price,3);

        return !empty($value)?$value:'';
    }


    /**
     * 返回剩余时间
     * @Author hfh_wind
     * @return int
     */
    public function getRemainingTimeAttribute()
    {
        $time = $this->diffBetweenTwoDays($this->created_at);
        return $time;
    }


    public function diffBetweenTwoDays($day)
    {
//        $platform_last_day = Redis::get('platform_last_day');

        $conf = Config::where('group', 'platform_attrs')->first();
        $conf_value = json_decode($conf['value'], true);
        $platform_last_day = $conf_value['platform_attrs']['last_day']??0;

        $second1 = strtotime($day);
        $second1 = strtotime("+" . $platform_last_day . "day", $second1); //创建时间的x天后时间戳

        $second2 = time(); //当前时间

        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        $res = floor($second1 - $second2) / 86400;

        return intval(round($res));
    }
}
