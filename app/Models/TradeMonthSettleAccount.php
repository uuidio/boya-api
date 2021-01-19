<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class TradeMonthSettleAccount extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['shop_name', 'status_text','gm_name', 'refund_fee_amount_text'];


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
     * 追加结算状态
     * @Author hfh_wind
     * @return string
     */
    public function getStatusTextAttribute()
    {
        if ($this->status == '1') {
            $text = '已结算';
        } else {
            $text = '未结算';
        }
        return $text;
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


}
