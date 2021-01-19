<?php

namespace ShopEM\Observers;

use ShopEM\Models\TradeAftersales;

class TradeAftersalesObserver
{
    /**
     * Handle the trade aftersales "created" event.
     *
     * @param  \ShopEM\Models\TradeAftersales  $tradeAftersales
     * @return void
     */
    public function created(TradeAftersales $tradeAftersales)
    {
        if (isset($tradeAftersales->shop_id) && isset($tradeAftersales->tid)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$tradeAftersales->shop_id)->value('gm_id');
            TradeAftersales::where('tid',$tradeAftersales->tid)->where('shop_id',$tradeAftersales->shop_id)->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the trade aftersales "updated" event.
     *
     * @param  \ShopEM\Models\TradeAftersales  $tradeAftersales
     * @return void
     */
    public function updated(TradeAftersales $tradeAftersales)
    {
        //
    }

    /**
     * Handle the trade aftersales "deleted" event.
     *
     * @param  \ShopEM\Models\TradeAftersales  $tradeAftersales
     * @return void
     */
    public function deleted(TradeAftersales $tradeAftersales)
    {
        //
    }

    /**
     * Handle the trade aftersales "restored" event.
     *
     * @param  \ShopEM\Models\TradeAftersales  $tradeAftersales
     * @return void
     */
    public function restored(TradeAftersales $tradeAftersales)
    {
        //
    }

    /**
     * Handle the trade aftersales "force deleted" event.
     *
     * @param  \ShopEM\Models\TradeAftersales  $tradeAftersales
     * @return void
     */
    public function forceDeleted(TradeAftersales $tradeAftersales)
    {
        //
    }
}
