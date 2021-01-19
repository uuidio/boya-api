<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsStockLog extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['shop_name','goods_name','type_text'];

    /**
     * 店铺名称
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getShopNameAttribute()
    {
        $shop = Shop::find($this->shop_id);
        return isset($shop->shop_name)?$shop->shop_name:'';
    }


    /**
     * 追加商品名称
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getGoodsNameAttribute()
    {
        $info = Goods::find($this->goods_id);
        return isset($info['goods_name'])?$info['goods_name']:'';
    }


    /**
     * 追加类型说明
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getTypeTextAttribute()
    {
        $res='';
        switch ($this->type) {
            case 'add':
                $res = '商品添加';
                break;
            case 'edit':
                $res = '商品编辑';
                break;
            case 'inc':
                $res = '订单增加';
                break;
            case 'dec':
                $res = '订单扣减';
                break;
            case 'seckill':
                $res = '秒杀活动';
                break;
        }

        return $res;
    }


}
