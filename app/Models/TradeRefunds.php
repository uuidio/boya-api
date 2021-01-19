<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class TradeRefunds extends Model
{
    protected $guarded = [];

    protected $appends = ['payment_id', 'refunds_type_name', 'status_name', 'return_freight_name', 'shop_name','trade_type','gm_name'];

    public function getPaymentIdAttribute()
    {
        $paybill = TradePaybill::where('tid', $this->tid)->first();

        return empty($paybill) ? '' : $paybill->payment_id;
    }

    public function getRefundsTypeNameAttribute()
    {
    	switch ($this->refunds_type) {
    		case '0':
    			return '售后申请退款';
    			break;
    		case '1':
    			return '取消订单退款';
    			break;
    		case '2':
    			return '拒收订单退款';
    			break;
    	}
    }
    public function getStatusNameAttribute()
    {
    	switch ($this->status) {
    		case '0':
    			return '未审核';
    			break;
    		case '1':
    			return '已完成退款';
    			break;
    		case '2':
    			return '已驳回';
    			break;
    		case '3':
    			return '商家审核通过';
    			break;
    		case '4':
    			return '商家审核不通过';
    			break;
    		case '5':
    			return '等待退款';
    			break;
    		case '6':
    			return '等待退款';
    			break;
    	}
    }
    public function getReturnFreightNameAttribute()
    {
    	switch ($this->return_freight) {
    		case '1':
    			return '是';
    			break;
    		case '2':
    			return '否';
    			break;
    	}
    }

    /**
     * 店铺信息
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getShopNameAttribute()
    {
        $shop = Shop::select('shop_name')->where('id', $this->shop_id)->first();
        return $shop['shop_name'] ?? '';
    }

    /**
     * 订单类型
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getTradeTypeAttribute()
    {
        $tradeType = TradeRefundLog::select('type')->where('tid', $this->tid)->first();

        return $tradeType['type'] ?? '--';
    }
    
    /**
     * 追加项目名称
     * @Author swl
     * @return string
     */
    public function getGmNameAttribute()
    {
        $platform_name = GmPlatform::where('gm_id', '=', $this->gm_id)->value('platform_name');

        return !empty($platform_name) ? $platform_name : '';
    }

}
