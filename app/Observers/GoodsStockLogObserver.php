<?php

namespace ShopEM\Observers;

use ShopEM\Models\GoodsStockLog;

class GoodsStockLogObserver
{
    /**
     * Handle the goods stock log "created" event.
     *
     * @param  \ShopEM\Models\GoodsStockLog  $goodsStockLog
     * @return void
     */
    public function created(GoodsStockLog $model)
    {
        if (isset($model->goods_id)) 
        {
            $gm_id = \ShopEM\Models\Goods::where('id',$model->goods_id)->value('gm_id');
            GoodsStockLog::where('goods_id',$model->goods_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the goods stock log "updated" event.
     *
     * @param  \ShopEM\Models\GoodsStockLog  $goodsStockLog
     * @return void
     */
    public function updated(GoodsStockLog $goodsStockLog)
    {
        //
    }

    /**
     * Handle the goods stock log "deleted" event.
     *
     * @param  \ShopEM\Models\GoodsStockLog  $goodsStockLog
     * @return void
     */
    public function deleted(GoodsStockLog $goodsStockLog)
    {
        //
    }

    /**
     * Handle the goods stock log "restored" event.
     *
     * @param  \ShopEM\Models\GoodsStockLog  $goodsStockLog
     * @return void
     */
    public function restored(GoodsStockLog $goodsStockLog)
    {
        //
    }

    /**
     * Handle the goods stock log "force deleted" event.
     *
     * @param  \ShopEM\Models\GoodsStockLog  $goodsStockLog
     * @return void
     */
    public function forceDeleted(GoodsStockLog $goodsStockLog)
    {
        //
    }
}
