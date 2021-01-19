<?php

namespace ShopEM\Observers;

use ShopEM\Models\SecKillStockLog;

class SecKillStockLogObserver
{
    /**
     * Handle the sec kill stock log "created" event.
     *
     * @param  \ShopEM\Models\SecKillStockLog  $secKillStockLog
     * @return void
     */
    public function created(SecKillStockLog $mdoel)
    {
        if (isset($model->shop_id)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$model->shop_id)->value('gm_id');
            SecKillStockLog::where('shop_id',$model->shop_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the sec kill stock log "updated" event.
     *
     * @param  \ShopEM\Models\SecKillStockLog  $secKillStockLog
     * @return void
     */
    public function updated(SecKillStockLog $secKillStockLog)
    {
        //
    }

    /**
     * Handle the sec kill stock log "deleted" event.
     *
     * @param  \ShopEM\Models\SecKillStockLog  $secKillStockLog
     * @return void
     */
    public function deleted(SecKillStockLog $secKillStockLog)
    {
        //
    }

    /**
     * Handle the sec kill stock log "restored" event.
     *
     * @param  \ShopEM\Models\SecKillStockLog  $secKillStockLog
     * @return void
     */
    public function restored(SecKillStockLog $secKillStockLog)
    {
        //
    }

    /**
     * Handle the sec kill stock log "force deleted" event.
     *
     * @param  \ShopEM\Models\SecKillStockLog  $secKillStockLog
     * @return void
     */
    public function forceDeleted(SecKillStockLog $secKillStockLog)
    {
        //
    }
}
