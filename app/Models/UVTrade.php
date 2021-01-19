<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class UVTrade extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['shop_name', 'gm_name'];

    public function getShopNameAttribute()
    {
        $shop_name = Shop::where('id', '=', $this->shop_id)->value('shop_name');

        return !empty($shop_name) ? $shop_name : '--';
    }

    /**
     * 追加项目名称
     * @Author Huiho
     * @return string
     */
    public function getGmNameAttribute()
    {
        $platform_name = GmPlatform::where('gm_id', '=', $this->gm_id)->value('platform_name');

        return !empty($platform_name) ? $platform_name : '--';
    }
}
