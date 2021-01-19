<?php

namespace ShopEM\Observers;

use ShopEM\Models\GoodsSku;

class GoodsSkuObserver
{
    /**
     * Handle the goods sku "created" event.
     *
     * @param  \ShopEM\Models\GoodsSku  $goodsSku
     * @return void
     */
    public function created(GoodsSku $goodsSku)
    {
        if (isset($goodsSku->shop_id)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$goodsSku->shop_id)->value('gm_id');
            GoodsSku::where('shop_id',$goodsSku->shop_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the goods sku "updated" event.
     *
     * @param  \ShopEM\Models\GoodsSku  $goodsSku
     * @return void
     */
    public function updated(GoodsSku $goodsSku)
    {
        if (isset($goodsSku->id)) {
            cache()->forget('cache_goods_sku_id_' . $goodsSku->id);
        }
    }

    /**
     * Handle the goods sku "deleted" event.
     *
     * @param  \ShopEM\Models\GoodsSku  $goodsSku
     * @return void
     */
    public function deleted(GoodsSku $goodsSku)
    {
        //
    }

    /**
     * Handle the goods sku "restored" event.
     *
     * @param  \ShopEM\Models\GoodsSku  $goodsSku
     * @return void
     */
    public function restored(GoodsSku $goodsSku)
    {
        //
    }

    /**
     * Handle the goods sku "force deleted" event.
     *
     * @param  \ShopEM\Models\GoodsSku  $goodsSku
     * @return void
     */
    public function forceDeleted(GoodsSku $goodsSku)
    {
        //
    }
}
