<?php

namespace ShopEM\Observers;

use ShopEM\Models\TradeLog;

class TradeLogObserver
{
    /**
     * Handle the trade log "created" event.
     *
     * @param  \ShopEM\Models\TradeLog  $tradeLog
     * @return void
     */
    public function created(TradeLog $tradeLog)
    {
        if (isset($tradeLog->rel_id)) 
        {
            $gm_id = \ShopEM\Models\Trade::where('tid',$tradeLog->rel_id)->value('gm_id');
            TradeLog::where('rel_id',$tradeLog->rel_id)->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the trade log "updated" event.
     *
     * @param  \ShopEM\Models\TradeLog  $tradeLog
     * @return void
     */
    public function updated(TradeLog $tradeLog)
    {
        //
    }

    /**
     * Handle the trade log "deleted" event.
     *
     * @param  \ShopEM\Models\TradeLog  $tradeLog
     * @return void
     */
    public function deleted(TradeLog $tradeLog)
    {
        //
    }

    /**
     * Handle the trade log "restored" event.
     *
     * @param  \ShopEM\Models\TradeLog  $tradeLog
     * @return void
     */
    public function restored(TradeLog $tradeLog)
    {
        //
    }

    /**
     * Handle the trade log "force deleted" event.
     *
     * @param  \ShopEM\Models\TradeLog  $tradeLog
     * @return void
     */
    public function forceDeleted(TradeLog $tradeLog)
    {
        //
    }
}
