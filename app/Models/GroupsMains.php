<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GroupsMains extends Model
{
    //
    protected $guarded = [];

    protected $appends = ['shop_name', 'count_group', 'group_stock'];


    /**
     * 店铺名称
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getShopNameAttribute()
    {
        $shop_name = DB::table('shops')->where('id', $this->shop_id)->value('shop_name');
        return $shop_name ?? '';
    }


    /**
     * 拼团成功数量
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getCountGroupAttribute()
    {
        $return = 0;
        $count = Group::where('main_id', $this->id)->select('id')->get();
        if (count($count) > 0) {
            $count = $count->toArray();
            $return = array_sum(array_column($count, 'count_group'));
        }

        return $return;
    }


    /**
     * 团购库存
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getGroupStockAttribute()
    {
        $info = Goods::where('id',$this->goods_id)->select('id')->first();

        return $info['goods_stock']??0;
    }


    /**
     * 团购价格
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getGroupPriceAttribute($value)
    {
        $group_price = DB::table('groups')->where('main_id', $this->id)->orderBy('group_price', 'asc')->value('group_price');

        return $group_price??$value;
    }



}
