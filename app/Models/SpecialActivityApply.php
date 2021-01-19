<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class SpecialActivityApply extends Model
{
	protected $table = 'special_activity_applies';
    protected $guarded = [];
    protected $appends = ['act_info','goods_info','shop_info'];


	public function getActInfoAttribute()
    {
        return SpecialActivity::find($this->act_id);
    }

    public function getGoodsInfoAttribute()
    {
        $where = [
            'shop_id'   => $this->shop_id,
            'act_id'    => $this->act_id
        ];
        return SpecialActivityItem::where($where)->get();
    }

    public function getShopInfoAttribute()
    {
        return Shop::find($this->shop_id);
    }
}
