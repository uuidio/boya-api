<?php

namespace ShopEM\Observers;

use ShopEM\Models\TradeSplit;

class TradeSplitObserver
{
    /**
     * Handle the trade split "created" event.
     *
     * @param  \ShopEM\Models\TradeSplit  $tradeSplit
     * @return void
     */
    public function created(TradeSplit $tradeSplit)
    {
        if (isset($tradeSplit->tid)) 
        {
            $gm_id = \ShopEM\Models\Trade::where('tid',$tradeSplit->tid)->value('gm_id');
            TradeSplit::where('tid',$tradeSplit->tid)->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the trade split "updated" event.
     *
     * @param  \ShopEM\Models\TradeSplit  $tradeSplit
     * @return void
     */
    public function updated(TradeSplit $tradeSplit)
    {
        //
    }

    /**
     * Handle the trade split "deleted" event.
     *
     * @param  \ShopEM\Models\TradeSplit  $tradeSplit
     * @return void
     */
    public function deleted(TradeSplit $tradeSplit)
    {
        //
    }

    /**
     * Handle the trade split "restored" event.
     *
     * @param  \ShopEM\Models\TradeSplit  $tradeSplit
     * @return void
     */
    public function restored(TradeSplit $tradeSplit)
    {
        //
    }

    /**
     * Handle the trade split "force deleted" event.
     *
     * @param  \ShopEM\Models\TradeSplit  $tradeSplit
     * @return void
     */
    public function forceDeleted(TradeSplit $tradeSplit)
    {
        //
    }
}
