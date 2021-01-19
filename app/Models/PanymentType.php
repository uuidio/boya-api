<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;


class PanymentType extends Model
{
    protected $guarded = [];
    protected $appends = ['pay_gm_name'];

    public function getPayGmNameAttribute()
    {
        $platform_name = GmPlatform::where('gm_id',$this->pay_gm_id)->value('platform_name');
        return empty($platform_name) ? '' : $platform_name;
    }

    //获取支付代码
    public static function getPayCode($gm_id,$pay_type)
    {
    	$key = $pay_type. '_panyment_type_get_pay_code_' . $gm_id ;
    	$pay_type_code = Cache::remember($key, cacheExpires(), function () use ($gm_id,$pay_type) {
            $code = self::where('pay_gm_id',$gm_id)
            			->where('pay_type',$pay_type)
            			->value('pay_type_code');
            return $code;
        });
        if (empty($pay_type_code)) Cache::forget($key);
    	return $pay_type_code;
    }

    //删除-获取支付代码-缓存
    public function delCachePayCode($gm_id,$pay_type)
    {
    	$key = $pay_type. '_panyment_type_get_pay_code_' . $gm_id ;
    	Cache::forget($key);
    	return true;
    }
}
