<?php

namespace ShopEM\Observers;

use ShopEM\Models\RateTraderate;

class RateTraderateObserver
{
    /**
     * Handle the rate traderate "created" event.
     *
     * @param  \ShopEM\Models\RateTraderate  $rateTraderate
     * @return void
     */
    public function created(RateTraderate $rateTraderate)
    {
        if (isset($rateTraderate->shop_id) && isset($rateTraderate->tid)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$rateTraderate->shop_id)->value('gm_id');
            RateTraderate::where('tid',$rateTraderate->tid)->where('shop_id',$rateTraderate->shop_id)->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the rate traderate "updated" event.
     *
     * @param  \ShopEM\Models\RateTraderate  $rateTraderate
     * @return void
     */
    public function updated(RateTraderate $rateTraderate)
    {
        //
    }

    /**
     * Handle the rate traderate "deleted" event.
     *
     * @param  \ShopEM\Models\RateTraderate  $rateTraderate
     * @return void
     */
    public function deleted(RateTraderate $rateTraderate)
    {
        //
    }

    /**
     * Handle the rate traderate "restored" event.
     *
     * @param  \ShopEM\Models\RateTraderate  $rateTraderate
     * @return void
     */
    public function restored(RateTraderate $rateTraderate)
    {
        //
    }

    /**
     * Handle the rate traderate "force deleted" event.
     *
     * @param  \ShopEM\Models\RateTraderate  $rateTraderate
     * @return void
     */
    public function forceDeleted(RateTraderate $rateTraderate)
    {
        //
    }
}
