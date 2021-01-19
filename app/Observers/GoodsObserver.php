<?php

namespace ShopEM\Observers;

use ShopEM\Models\Goods;
use ShopEM\Models\PointActivityGoods;

class GoodsObserver
{
    /**
     * Handle the goods "created" event.
     *
     * @param  \ShopEM\Models\Goods  $goods
     * @return void
     */
    public function created(Goods $goods)
    {
        if (isset($goods->shop_id)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$goods->shop_id)->value('gm_id');
            Goods::where('shop_id',$goods->shop_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the goods "updated" event.
     *
     * @param  \ShopEM\Models\Goods  $goods
     * @return void
     */
    public function updated(Goods $goods)
    {
        if (isset($goods->id)) 
        {
            cache()->forget('cache_detail_goods_id_' . $goods->id);
            
            $pointActivity = PointActivityGoods::where('goods_id',$goods->id)->first();
            if ($pointActivity) {
                $save = 0;
                if (isset($goods->goods_name)) {
                    $save = 1;
                    $pointActivity->goods_name = $goods->goods_name;
                }
                if (isset($goods->goods_price)) {
                    $save = 1;
                    $pointActivity->goods_price = $goods->goods_price;
                }
                if (isset($goods->goods_image)) {
                    $save = 1;
                    $pointActivity->goods_image = $goods->goods_image;
                }
                if ($save > 0) {
                    $pointActivity->save();
                }
            }
            
        }
    }

    /**
     * Handle the goods "deleted" event.
     *
     * @param  \ShopEM\Models\Goods  $goods
     * @return void
     */
    public function deleted(Goods $goods)
    {
        //
    }

    /**
     * Handle the goods "restored" event.
     *
     * @param  \ShopEM\Models\Goods  $goods
     * @return void
     */
    public function restored(Goods $goods)
    {
        //
    }

    /**
     * Handle the goods "force deleted" event.
     *
     * @param  \ShopEM\Models\Goods  $goods
     * @return void
     */
    public function forceDeleted(Goods $goods)
    {
        //
    }
}
