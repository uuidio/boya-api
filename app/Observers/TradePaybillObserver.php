<?php

namespace ShopEM\Observers;

use ShopEM\Models\TradePaybill;

class TradePaybillObserver
{
    /**
     * Handle the trade paybill "created" event.
     *
     * @param  \ShopEM\Models\TradePaybill  $tradePaybill
     * @return void
     */
    public function created(TradePaybill $model)
    {
        if (isset($model->tid)) 
        {
            $gm_id = \ShopEM\Models\Trade::where('tid',$model->tid)->value('gm_id');
            TradePaybill::where('tid',$model->tid)->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the trade paybill "updated" event.
     *
     * @param  \ShopEM\Models\TradePaybill  $tradePaybill
     * @return void
     */
    public function updated(TradePaybill $tradePaybill)
    {
        //
    }

    /**
     * Handle the trade paybill "deleted" event.
     *
     * @param  \ShopEM\Models\TradePaybill  $tradePaybill
     * @return void
     */
    public function deleted(TradePaybill $tradePaybill)
    {
        //
    }

    /**
     * Handle the trade paybill "restored" event.
     *
     * @param  \ShopEM\Models\TradePaybill  $tradePaybill
     * @return void
     */
    public function restored(TradePaybill $tradePaybill)
    {
        //
    }

    /**
     * Handle the trade paybill "force deleted" event.
     *
     * @param  \ShopEM\Models\TradePaybill  $tradePaybill
     * @return void
     */
    public function forceDeleted(TradePaybill $tradePaybill)
    {
        //
    }
}
