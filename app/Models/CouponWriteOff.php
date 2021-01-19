<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class CouponWriteOff extends Model
{
    protected $guarded = [];

    protected $appends = ['coupon_name','source_type','source_name'];

    public function getCouponNameAttribute()
    {
    	$name = Coupon::where('id',$this->coupon_id)->value('name');
    	return empty($name) ? '' : $name;
    }


    public function getSourceTypeAttribute()
    {
    	switch ($this->source_shop_id) {
    		case '0':
    			return '平台';
    			break;
    		
    		default:
    			return '商家';
    			break;
    	}
    }

    public function getSourceNameAttribute()
    {
        if ($this->source_shop_id > 0) 
        {
            return Shop::where('id',$this->source_shop_id)->value('shop_name');
        }
        return '平台';
    }
}
