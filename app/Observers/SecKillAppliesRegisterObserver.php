<?php

namespace ShopEM\Observers;

use ShopEM\Models\SecKillAppliesRegister;

class SecKillAppliesRegisterObserver
{
    /**
     * Handle the sec kill applies register "created" event.
     *
     * @param  \ShopEM\Models\SecKillAppliesRegister  $secKillAppliesRegister
     * @return void
     */
    public function created(SecKillAppliesRegister $model)
    {
        if (isset($model->shop_id)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$model->shop_id)->value('gm_id');
            SecKillAppliesRegister::where('shop_id',$model->shop_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the sec kill applies register "updated" event.
     *
     * @param  \ShopEM\Models\SecKillAppliesRegister  $secKillAppliesRegister
     * @return void
     */
    public function updated(SecKillAppliesRegister $secKillAppliesRegister)
    {
        //
    }

    /**
     * Handle the sec kill applies register "deleted" event.
     *
     * @param  \ShopEM\Models\SecKillAppliesRegister  $secKillAppliesRegister
     * @return void
     */
    public function deleted(SecKillAppliesRegister $secKillAppliesRegister)
    {
        //
    }

    /**
     * Handle the sec kill applies register "restored" event.
     *
     * @param  \ShopEM\Models\SecKillAppliesRegister  $secKillAppliesRegister
     * @return void
     */
    public function restored(SecKillAppliesRegister $secKillAppliesRegister)
    {
        //
    }

    /**
     * Handle the sec kill applies register "force deleted" event.
     *
     * @param  \ShopEM\Models\SecKillAppliesRegister  $secKillAppliesRegister
     * @return void
     */
    public function forceDeleted(SecKillAppliesRegister $secKillAppliesRegister)
    {
        //
    }
}
