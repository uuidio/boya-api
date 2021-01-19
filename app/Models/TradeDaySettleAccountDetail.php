<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class TradeDaySettleAccountDetail extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['shop_name','pay_type_name','settle_type_text','refund_fee_text'];

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

    public function getPayTypeNameAttribute()
    {
        return Payment::$payAppMap[$this->pay_type]??'';
    }

    public function getSettleTypeTextAttribute()
    {
        if(isset($this->attributes['settle_type']))
        {
            if ($this->attributes['settle_type'] == 1) {
                return '普通结算';
            } elseif ($this->attributes['settle_type'] == 2) {
                return '退货结算';
            }
            else
            {
                return '--';
            }
        }
        else
        {
            return '--';
        }

    }

    /**
     * 追加退款金额负数显示
     * @Author Huiho
     * @return string
     */
    public function getRefundFeeTextAttribute()
    {

        if(isset($this->attributes['settle_type']))
        {
            if($this->attributes['settle_type'] == 1)
            {
                return $this->attributes['refund_fee'];
            }
            elseif ($this->attributes['settle_type'] == 2)
            {
                return '-'.$this->attributes['refund_fee'];
            }
            else
            {
                return '--';
            }

        }
        else
        {
            return '--';
        }

    }


}
