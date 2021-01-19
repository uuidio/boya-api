<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TradeDaySettleAccount extends Model
{
    
    protected $guarded = [];

    protected $appends = ['shop_name','gm_name','refund_fee_amount_text','refund_count'];



    /**
     * 追加店铺名称
     * @Author hfh_wind
     * @return string
     */
    public function getShopNameAttribute()
    {
        $shop_info = Shop::where('id', '=', $this->shop_id)->select('shop_name')->first();

        return isset($shop_info['shop_name']) ? $shop_info['shop_name'] : '';
    }

    /**
     * 追加项目名称
     * @Author swl 2020-3-12
     * @return string
     */
    public function getGmNameAttribute()
    {
        $shop_info = GmPlatform::where('gm_id', '=', $this->gm_id)->select('platform_name')->first();

        return isset($shop_info['platform_name']) ? $shop_info['platform_name'] : '';
    }

    /**
     * 追加退款金额负数显示
     * @Author Huiho
     * @return string
     */
    public function getRefundFeeAmountTextAttribute()
    {
        return '-'.$this->attributes['refund_fee_amount'];
    }


    /**
     * 追加已退款数查询
     * @Author Huiho
     * @return string
     */
    public function getrefundCountAttribute()
    {

        $search_time['start']  = date('Y-m-d 00:00:00', strtotime($this->settle_time));
        $search_time['end']  = date('Y-m-d 23:59:59', strtotime($this->settle_time));

        $result = DB::table('trade_day_settle_account_details')
                    ->whereDate('settle_time', '>=', $search_time['start'])
                        ->whereDate('settle_time', '<=', $search_time['end'])
                            ->where('settle_type', '=', '2')
                            ->where('shop_id', '=', $this->shop_id)
                                ->count();
        return $result;
    }

}
