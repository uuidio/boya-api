<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PointActivityGoods extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['shop_name','point_unit','write_off_time','active_time','active_status','active_status_name','good_stock','exchange'];

    public function getShopNameAttribute()
    {
        $shop = Shop::find($this->shop_id);
        return isset($shop->shop_name) ? $shop->shop_name : '';
    }

    public function getPointUnitAttribute()
    {
        if ($this->gm_id == GmPlatform::gmSelf()) {
            return '牛币';
        }
        return '积分';
    }

    public function getWriteOffTimeAttribute()
    {
        if (!empty($this->write_off_start) || !empty($this->write_off_end)) {
            return $this->write_off_start . '~' . $this->write_off_end;
        }
        return '不限制';
    }

    public function getActiveTimeAttribute()
    {
        if (!empty($this->active_start) || !empty($this->active_end)) {
            return $this->active_start . '~' . $this->active_end;
        }
        return '已结束';
    }

    public function getActiveStatusAttribute()
    {
        $active = self::getActiveStatus($this);
        return $active['status'];
    }

    public function getActiveStatusNameAttribute()
    {
        $active = self::getActiveStatus($this);
        return $active['name'];
    }


    public static function getActiveStatus($data)
    {
        if (!empty($data->active_start) && !empty($data->active_end)) 
        {
            if (strtotime($data->active_start) > time()) 
            {
                return ['status'=>0,'name'=>'待开始'];
            }
            if (strtotime($data->active_start) <= time() && strtotime($data->active_end) >= time()) 
            {
                return ['status'=>1,'name'=>'已开始'];
            }
            if (strtotime($data->active_end) < time()) 
            {
                return ['status'=>2,'name'=>'已结束'];
            }
        }
        return ['status'=>2,'name'=>'已结束'];
    }

    public function getGoodStockAttribute()
    {
        $good_stock = DB::table('goods_skus')->where('goods_id',$this->goods_id)->where('gm_id',$this->gm_id)->sum('goods_stock');

        return empty($good_stock) ? 0 : $good_stock;
    }

    /**
     *  等级限制转化
     *
     * @Author Huiho
     * @return mixed|string
     */
    public function getGradeLimitAttribute()
    {
        return empty($this->attributes['grade_limit']) ? '' : explode(',', $this->attributes['grade_limit']);
    }
    
    public function getExchangeAttribute()
    {
        $count = TradeOrder::where('trade_orders.goods_id',$this->goods_id)
                        ->select('trade_orders.tid')
                            ->leftJoin('trades as b', 'b.tid', '=', 'trade_orders.tid')
                                ->where('b.gm_id',$this->gm_id)
                                ->where('trade_orders.activity_sign',$this->id)
                                ->whereNotIn('b.status',  ['TRADE_CLOSED', 'TRADE_CLOSED_BY_SYSTEM','TRADE_CLOSED_AFTER_PAY'])
                                    ->sum('quantity');
        if(!$count)
        {
            $result = 0;
        }
        else
        {
            $result =$count;

        }
        return $result;
    }

}
