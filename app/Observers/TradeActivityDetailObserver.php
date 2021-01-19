<?php

namespace ShopEM\Observers;

use ShopEM\Models\TradeActivityDetail;

class TradeActivityDetailObserver
{
    /**
     * Handle the trade activity detail "created" event.
     *
     * @param  \ShopEM\Models\TradeActivityDetail  $tradeActivityDetail
     * @return void
     */
    public function created(TradeActivityDetail $model)
    {
        if (isset($model->rel_id)) 
        {
            $gm_id = \ShopEM\Models\Trade::where('tid',$model->tid)->value('gm_id');
            TradeActivityDetail::where('tid',$model->tid)->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the trade activity detail "updated" event.
     *
     * @param  \ShopEM\Models\TradeActivityDetail  $tradeActivityDetail
     * @return void
     */
    public function updated(TradeActivityDetail $tradeActivityDetail)
    {
        //
    }

    /**
     * Handle the trade activity detail "deleted" event.
     *
     * @param  \ShopEM\Models\TradeActivityDetail  $tradeActivityDetail
     * @return void
     */
    public function deleted(TradeActivityDetail $tradeActivityDetail)
    {
        //
    }

    /**
     * Handle the trade activity detail "restored" event.
     *
     * @param  \ShopEM\Models\TradeActivityDetail  $tradeActivityDetail
     * @return void
     */
    public function restored(TradeActivityDetail $tradeActivityDetail)
    {
        //
    }

    /**
     * Handle the trade activity detail "force deleted" event.
     *
     * @param  \ShopEM\Models\TradeActivityDetail  $tradeActivityDetail
     * @return void
     */
    public function forceDeleted(TradeActivityDetail $tradeActivityDetail)
    {
        //
    }
}
