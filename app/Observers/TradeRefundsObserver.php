<?php

namespace ShopEM\Observers;

use ShopEM\Models\TradeRefunds;

class TradeRefundsObserver
{
    /**
     * Handle the trade refunds "created" event.
     *
     * @param  \ShopEM\Models\TradeRefunds  $tradeRefunds
     * @return void
     */
    public function created(TradeRefunds $tradeRefunds)
    {
        if (isset($tradeRefunds->shop_id) && isset($tradeRefunds->tid)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$tradeRefunds->shop_id)->value('gm_id');
            TradeRefunds::where('tid',$tradeRefunds->tid)->where('shop_id',$tradeRefunds->shop_id)->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the trade refunds "updated" event.
     *
     * @param  \ShopEM\Models\TradeRefunds  $tradeRefunds
     * @return void
     */
    public function updated(TradeRefunds $tradeRefunds)
    {
        //
    }

    /**
     * Handle the trade refunds "deleted" event.
     *
     * @param  \ShopEM\Models\TradeRefunds  $tradeRefunds
     * @return void
     */
    public function deleted(TradeRefunds $tradeRefunds)
    {
        //
    }

    /**
     * Handle the trade refunds "restored" event.
     *
     * @param  \ShopEM\Models\TradeRefunds  $tradeRefunds
     * @return void
     */
    public function restored(TradeRefunds $tradeRefunds)
    {
        //
    }

    /**
     * Handle the trade refunds "force deleted" event.
     *
     * @param  \ShopEM\Models\TradeRefunds  $tradeRefunds
     * @return void
     */
    public function forceDeleted(TradeRefunds $tradeRefunds)
    {
        //
    }
}
