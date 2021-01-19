<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ShopAttr extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['shop_name'];


    /**
     * 追加店铺信息
     * @Author huiho
     * @return string
     */
    public function getShopNameAttribute()
    {
        $info = Shop::where('id', $this->shop_id)->value('shop_name');

        return !empty($info) ? $info : '';
    }


}
