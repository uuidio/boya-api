<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class UserBuyLimitLog extends Model
{
    protected $guarded = [];
    protected $appends = ['limit_info'];

    public function getLimitInfoAttribute()
    {
        //取商品规格表价格
        $goods = GoodsSku::find($this->sku_id);

        return BuyLimit::find($this->limit_id);
    }
}
