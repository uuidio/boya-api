<?php

namespace ShopEM\Observers;

use ShopEM\Models\TradeDaySettleAccountDetail;

class TradeDaySettleAccountDetailObserver
{
    /**
     * Handle the trade day settle account detail "created" event.
     *
     * @param  \ShopEM\Models\TradeDaySettleAccountDetail  $tradeDaySettleAccountDetail
     * @return void
     */
    public function created(TradeDaySettleAccountDetail $model)
    {
        if (isset($model->shop_id) && isset($model->tid)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$model->shop_id)->value('gm_id');
            TradeDaySettleAccountDetail::where('tid',$model->tid)->where('shop_id',$model->shop_id)->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the trade day settle account detail "updated" event.
     *
     * @param  \ShopEM\Models\TradeDaySettleAccountDetail  $tradeDaySettleAccountDetail
     * @return void
     */
    public function updated(TradeDaySettleAccountDetail $tradeDaySettleAccountDetail)
    {
        //
    }

    /**
     * Handle the trade day settle account detail "deleted" event.
     *
     * @param  \ShopEM\Models\TradeDaySettleAccountDetail  $tradeDaySettleAccountDetail
     * @return void
     */
    public function deleted(TradeDaySettleAccountDetail $tradeDaySettleAccountDetail)
    {
        //
    }

    /**
     * Handle the trade day settle account detail "restored" event.
     *
     * @param  \ShopEM\Models\TradeDaySettleAccountDetail  $tradeDaySettleAccountDetail
     * @return void
     */
    public function restored(TradeDaySettleAccountDetail $tradeDaySettleAccountDetail)
    {
        //
    }

    /**
     * Handle the trade day settle account detail "force deleted" event.
     *
     * @param  \ShopEM\Models\TradeDaySettleAccountDetail  $tradeDaySettleAccountDetail
     * @return void
     */
    public function forceDeleted(TradeDaySettleAccountDetail $tradeDaySettleAccountDetail)
    {
        //
    }
}
