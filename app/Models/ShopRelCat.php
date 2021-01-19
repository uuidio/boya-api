<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ShopRelCat extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['count_shop'];

    /**
     *  分类名称
     *
     * @Author hfh_wind
     * @return string
     */

    public function getCountShopAttribute()
    {
        $count = Shop::where(['rel_cat_id' => $this->id])->count();

        return empty($count) ? 0 : $count;
    }



}
