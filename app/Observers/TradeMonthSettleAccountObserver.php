<?php

namespace ShopEM\Observers;

use ShopEM\Models\TradeMonthSettleAccount;

class TradeMonthSettleAccountObserver
{
    /**
     * Handle the trade month settle account "created" event.
     *
     * @param  \ShopEM\Models\TradeMonthSettleAccount  $tradeMonthSettleAccount
     * @return void
     */
    public function created(TradeMonthSettleAccount $model)
    {
        if (isset($model->shop_id)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$model->shop_id)->value('gm_id');
            TradeMonthSettleAccount::where('shop_id',$model->shop_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the trade month settle account "updated" event.
     *
     * @param  \ShopEM\Models\TradeMonthSettleAccount  $tradeMonthSettleAccount
     * @return void
     */
    public function updated(TradeMonthSettleAccount $tradeMonthSettleAccount)
    {
        //
    }

    /**
     * Handle the trade month settle account "deleted" event.
     *
     * @param  \ShopEM\Models\TradeMonthSettleAccount  $tradeMonthSettleAccount
     * @return void
     */
    public function deleted(TradeMonthSettleAccount $tradeMonthSettleAccount)
    {
        //
    }

    /**
     * Handle the trade month settle account "restored" event.
     *
     * @param  \ShopEM\Models\TradeMonthSettleAccount  $tradeMonthSettleAccount
     * @return void
     */
    public function restored(TradeMonthSettleAccount $tradeMonthSettleAccount)
    {
        //
    }

    /**
     * Handle the trade month settle account "force deleted" event.
     *
     * @param  \ShopEM\Models\TradeMonthSettleAccount  $tradeMonthSettleAccount
     * @return void
     */
    public function forceDeleted(TradeMonthSettleAccount $tradeMonthSettleAccount)
    {
        //
    }
}
