<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsSpreadQrs extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['goods_infos'];

    /**
     * 追加商品信息
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getGoodsInfosAttribute()
    {
        $info = Goods::find($this->goods_id);
        return !empty($info)?$info:'';
    }
}
