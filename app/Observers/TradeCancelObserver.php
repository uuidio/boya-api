<?php

namespace ShopEM\Observers;

use ShopEM\Models\TradeCancel;

class TradeCancelObserver
{
    /**
     * Handle the trade cancel "created" event.
     *
     * @param  \ShopEM\Models\TradeCancel  $tradeCancel
     * @return void
     */
    public function created(TradeCancel $tradeCancel)
    {
        if (isset($tradeCancel->shop_id) && isset($tradeCancel->tid)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$tradeCancel->shop_id)->value('gm_id');
            TradeCancel::where('tid',$tradeCancel->tid)->where('shop_id',$tradeCancel->shop_id)->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the trade cancel "updated" event.
     *
     * @param  \ShopEM\Models\TradeCancel  $tradeCancel
     * @return void
     */
    public function updated(TradeCancel $tradeCancel)
    {
        //
    }

    /**
     * Handle the trade cancel "deleted" event.
     *
     * @param  \ShopEM\Models\TradeCancel  $tradeCancel
     * @return void
     */
    public function deleted(TradeCancel $tradeCancel)
    {
        //
    }

    /**
     * Handle the trade cancel "restored" event.
     *
     * @param  \ShopEM\Models\TradeCancel  $tradeCancel
     * @return void
     */
    public function restored(TradeCancel $tradeCancel)
    {
        //
    }

    /**
     * Handle the trade cancel "force deleted" event.
     *
     * @param  \ShopEM\Models\TradeCancel  $tradeCancel
     * @return void
     */
    public function forceDeleted(TradeCancel $tradeCancel)
    {
        //
    }
}
