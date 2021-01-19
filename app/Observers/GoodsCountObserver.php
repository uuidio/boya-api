<?php

namespace ShopEM\Observers;

use ShopEM\Models\GoodsCount;

class GoodsCountObserver
{
    /**
     * Handle the goods count "created" event.
     *
     * @param  \ShopEM\Models\GoodsCount  $goodsCount
     * @return void
     */
    public function created(GoodsCount $model)
    {
        if (isset($model->goods_id)) 
        {
            $gm_id = \ShopEM\Models\Goods::where('id',$model->goods_id)->value('gm_id');
            GoodsCount::where('goods_id',$model->goods_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the goods count "updated" event.
     *
     * @param  \ShopEM\Models\GoodsCount  $goodsCount
     * @return void
     */
    public function updated(GoodsCount $goodsCount)
    {
        //
    }

    /**
     * Handle the goods count "deleted" event.
     *
     * @param  \ShopEM\Models\GoodsCount  $goodsCount
     * @return void
     */
    public function deleted(GoodsCount $goodsCount)
    {
        //
    }

    /**
     * Handle the goods count "restored" event.
     *
     * @param  \ShopEM\Models\GoodsCount  $goodsCount
     * @return void
     */
    public function restored(GoodsCount $goodsCount)
    {
        //
    }

    /**
     * Handle the goods count "force deleted" event.
     *
     * @param  \ShopEM\Models\GoodsCount  $goodsCount
     * @return void
     */
    public function forceDeleted(GoodsCount $goodsCount)
    {
        //
    }
}
