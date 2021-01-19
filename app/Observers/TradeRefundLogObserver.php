<?php

namespace ShopEM\Observers;

use ShopEM\Models\TradeRefundLog;

class TradeRefundLogObserver
{
    /**
     * Handle the trade refund log "created" event.
     *
     * @param  \ShopEM\Models\TradeRefundLog  $tradeRefundLog
     * @return void
     */
    public function created(TradeRefundLog $model)
    {
        if (isset($model->shop_id) && isset($model->tid)) 
        {
            $gm_id = \ShopEM\Models\Trade::where('tid',$model->tid)->value('gm_id');
            TradeRefundLog::where('tid',$model->tid)->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the trade refund log "updated" event.
     *
     * @param  \ShopEM\Models\TradeRefundLog  $tradeRefundLog
     * @return void
     */
    public function updated(TradeRefundLog $tradeRefundLog)
    {
        //
    }

    /**
     * Handle the trade refund log "deleted" event.
     *
     * @param  \ShopEM\Models\TradeRefundLog  $tradeRefundLog
     * @return void
     */
    public function deleted(TradeRefundLog $tradeRefundLog)
    {
        //
    }

    /**
     * Handle the trade refund log "restored" event.
     *
     * @param  \ShopEM\Models\TradeRefundLog  $tradeRefundLog
     * @return void
     */
    public function restored(TradeRefundLog $tradeRefundLog)
    {
        //
    }

    /**
     * Handle the trade refund log "force deleted" event.
     *
     * @param  \ShopEM\Models\TradeRefundLog  $tradeRefundLog
     * @return void
     */
    public function forceDeleted(TradeRefundLog $tradeRefundLog)
    {
        //
    }
}
