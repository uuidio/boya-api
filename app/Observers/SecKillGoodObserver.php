<?php

namespace ShopEM\Observers;

use ShopEM\Models\SecKillGood;

class SecKillGoodObserver
{
    /**
     * Handle the sec kill good "created" event.
     *
     * @param  \ShopEM\Models\SecKillGood  $secKillGood
     * @return void
     */
    public function created(SecKillGood $model)
    {
        if (isset($model->shop_id)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$model->shop_id)->value('gm_id');
            SecKillGood::where('shop_id',$model->shop_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the sec kill good "updated" event.
     *
     * @param  \ShopEM\Models\SecKillGood  $secKillGood
     * @return void
     */
    public function updated(SecKillGood $secKillGood)
    {
        //
    }

    /**
     * Handle the sec kill good "deleted" event.
     *
     * @param  \ShopEM\Models\SecKillGood  $secKillGood
     * @return void
     */
    public function deleted(SecKillGood $secKillGood)
    {
        //
    }

    /**
     * Handle the sec kill good "restored" event.
     *
     * @param  \ShopEM\Models\SecKillGood  $secKillGood
     * @return void
     */
    public function restored(SecKillGood $secKillGood)
    {
        //
    }

    /**
     * Handle the sec kill good "force deleted" event.
     *
     * @param  \ShopEM\Models\SecKillGood  $secKillGood
     * @return void
     */
    public function forceDeleted(SecKillGood $secKillGood)
    {
        //
    }
}
