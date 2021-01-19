<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ShopRelBrand extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['brand_info'];


    /**
     * 品牌信息
     * @Author hfh_wind
     * @return null
     */
    public function getBrandInfoAttribute()
    {
        $res=Brand::where('id','=',$this->brand_id)->first();
        return empty($res) ? null : $res;
    }


//    /**
//     * 店铺名称
//     * @Author hfh_wind
//     * @return null
//     */
//    public function getShopNameAttribute()
//    {
//        $res=Shop::where('id','=',$this->shop_id)->select('shop_name')->first();
//        return empty($res) ? null : $res->shop_name;
//    }

}
