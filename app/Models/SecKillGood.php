<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class SecKillGood extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['sold_out', 'percent', 'shop_name', 'verify_status_text','activity_status'];


    /**
     * 是否卖完
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getSoldOutAttribute()
    {

        $record_key = "seckill_" . $this->sku_id . "_good_record_" . $this->seckill_ap_id;//用来对比购买的库存
        $record=Redis::get($record_key)?Redis::get($record_key):0;

        $soldOut = (($record >= $this->seckills_stock) && ($this->seckills_stock > 0)) ? 'yes' : 'no';

        return $soldOut;
    }


    /**
     * 百分比
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getPercentAttribute()
    {

//        $record_key = "seckill_" . $this->sku_id . "_good_record_" . $this->seckill_ap_id;//用来对比购买的库存
//        $record=Redis::get($record_key)?Redis::get($record_key):0;

        $user_queue_key = "seckill_" . $this->sku_id  . "_user_" . $this->seckill_ap_id;//当前商品队列的用户情况

        $record = Redis::hlen($user_queue_key);
        if ($this->seckills_stock > 0) {
            $soldOut = $record / $this->seckills_stock;
        }else{
            $soldOut = 0;
        }
        $soldOut = intval(sprintf("%.2f", round($soldOut, 2))*100);
        return $soldOut;
    }


    /**
     * 店铺名称
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getShopNameAttribute()
    {
        $shop = Shop::find($this->shop_id);
        return isset($shop->shop_name) ? $shop->shop_name : '';
    }

    /**
     * 追加审核状态
     * @Author hfh_wind
     * @return array
     */
    public function getVerifyStatusTextAttribute()
    {
        if ($this->verify_status == '3') {
            $text = "已经停止";
        } else {
            $text = "活动中";
        }

        return $text;
    }

    /**
     * [joinSecKill 参与秒杀活动的商品]
     * @param [type] $goods_id [商品id]
     * @param [type] $start [是否展示正在开始的]
     */
    public static function joinSecKill($goods_id, $start = false)
    {
        $model = SecKillGood::where('goods_id',$goods_id)
                ->where('verify_status','2')
                ->where('end_time','>=',nowTimeString())
                ->whereNotNull('end_time');
        if ($start) {
            $model = $model->where('start_time','<=',nowTimeString());
        }
        $seckill_goods = $model->orderBy('seckill_price','asc')->first();
        return $seckill_goods;
    }


     // 追加商品活动状态
    public function getActivityStatusAttribute(){
        $status = SecKillApplie::where(['id' => $this->seckill_ap_id])->first()->toArray();
        return isset($status['validstatus']) ? $status['validstatus'] : '';
    }
}
