<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class TradeCancel extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['pay_type_name','process_name', 'cancel_from_name', 'shop_name', 'user_name','is_refund','gm_name'];
    
    public function getPayTypeNameAttribute()
    {
    	switch ($this->pay_type) {
    		case 'online':
    			return '线上付款';
    			break;
    		case 'offline':
    			return '货到付款';
    			break;
    	}
    }
    public function getProcessNameAttribute()
    {
    	switch ($this->process) {
    		case '0':
    			return '提交申请';
    			break;
    		case '1':
    			return '取消处理';
    			break;
    		case '2':
    			return '退款处理';
    			break;
    		case '3':
    			return '完成';
    			break;
    	}
    }
    public function getCancelFromNameAttribute()
    {
    	switch ($this->cancel_from) {
    		case 'buyer':
    			return '用户取消订单';
    			break;
    		case 'shop':
    			return '商家取消订单';
    			break;
    		case 'admin':
    			return '平台取消订单';
    			break;
    	}
    }
    public function getIsRefundAttribute()
    {
        $trade = TradeRefunds::where(['tid'=>$this->tid])->count();
        return $trade? true : false;
    }

    public function getShopNameAttribute()
    {
    	$shop = Shop::find($this->shop_id);
    	return $shop->shop_name;
    }
    public function getUserNameAttribute()
    {
    	$user = UserAccount::find($this->user_id);
    	return $user->mobile ?? '匿名';
    }

    /**
     * 追加项目名称
     * @Author swl
     * @return string
     */
    public function getGmNameAttribute()
    {
        $shop_info = GmPlatform::where('gm_id', '=', $this->gm_id)->select('platform_name')->first();

        return isset($shop_info['platform_name']) ? $shop_info['platform_name'] : '';
    }
}
