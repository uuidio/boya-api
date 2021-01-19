<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsSku extends Model
{
    //
    protected $guarded = [];





    /**
     * 反序列化规格值
     * @Author hfh_wind
     * @return mixed|null
     */
    public function getGoodsSpecAttribute($value)
    {
        return empty($value) ? null : unserialize($value);
    }
}
