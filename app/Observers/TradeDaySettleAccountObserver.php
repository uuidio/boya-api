<?php

namespace ShopEM\Observers;

use ShopEM\Models\TradeDaySettleAccount;

class TradeDaySettleAccountObserver
{
    /**
     * Handle the trade day settle account "created" event.
     *
     * @param  \ShopEM\Models\TradeDaySettleAccount  $tradeDaySettleAccount
     * @return void
     */
    public function created(TradeDaySettleAccount $model)
    {
        if (isset($model->shop_id)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$model->shop_id)->value('gm_id');
            TradeDaySettleAccount::where('shop_id',$model->shop_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the trade day settle account "updated" event.
     *
     * @param  \ShopEM\Models\TradeDaySettleAccount  $tradeDaySettleAccount
     * @return void
     */
    public function updated(TradeDaySettleAccount $tradeDaySettleAccount)
    {
        //
    }

    /**
     * Handle the trade day settle account "deleted" event.
     *
     * @param  \ShopEM\Models\TradeDaySettleAccount  $tradeDaySettleAccount
     * @return void
     */
    public function deleted(TradeDaySettleAccount $tradeDaySettleAccount)
    {
        //
    }

    /**
     * Handle the trade day settle account "restored" event.
     *
     * @param  \ShopEM\Models\TradeDaySettleAccount  $tradeDaySettleAccount
     * @return void
     */
    public function restored(TradeDaySettleAccount $tradeDaySettleAccount)
    {
        //
    }

    /**
     * Handle the trade day settle account "force deleted" event.
     *
     * @param  \ShopEM\Models\TradeDaySettleAccount  $tradeDaySettleAccount
     * @return void
     */
    public function forceDeleted(TradeDaySettleAccount $tradeDaySettleAccount)
    {
        //
    }
}
