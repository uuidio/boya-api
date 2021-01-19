<?php

namespace ShopEM\Observers;

use ShopEM\Models\Trade;
use ShopEM\Models\Shop;

class TradeObserver
{
    /**
     * Handle the trade "created" event.
     *
     * @param  \ShopEM\Trade  $trade
     * @return void
     */
    public function created(Trade $trade)
    {
        if (isset($trade->shop_id) && isset($trade->tid) && $trade->shop_id > 0)
        {
            $gm_id = Shop::where('id',$trade->shop_id)->value('gm_id');
            Trade::where('tid',$trade->tid)->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the trade "updated" event.
     *
     * @param  \ShopEM\Trade  $trade
     * @return void
     */
    public function updated(Trade $trade)
    {
        //
    }

    /**
     * Handle the trade "deleted" event.
     *
     * @param  \ShopEM\Trade  $trade
     * @return void
     */
    public function deleted(Trade $trade)
    {
        //
    }

    /**
     * Handle the trade "restored" event.
     *
     * @param  \ShopEM\Trade  $trade
     * @return void
     */
    public function restored(Trade $trade)
    {
        //
    }

    /**
     * Handle the trade "force deleted" event.
     *
     * @param  \ShopEM\Trade  $trade
     * @return void
     */
    public function forceDeleted(Trade $trade)
    {
        //
    }
}
