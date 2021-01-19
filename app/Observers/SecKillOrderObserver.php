<?php

namespace ShopEM\Observers;

use ShopEM\Models\SecKillOrder;

class SecKillOrderObserver
{
    /**
     * Handle the sec kill order "created" event.
     *
     * @param  \ShopEM\Models\SecKillOrder  $secKillOrder
     * @return void
     */
    public function created(SecKillOrder $model)
    {
        if (isset($model->goods_id)) 
        {
            $gm_id = \ShopEM\Models\Goods::where('id',$model->goods_id)->value('gm_id');
            SecKillOrder::where('goods_id',$model->goods_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the sec kill order "updated" event.
     *
     * @param  \ShopEM\Models\SecKillOrder  $secKillOrder
     * @return void
     */
    public function updated(SecKillOrder $secKillOrder)
    {
        //
    }

    /**
     * Handle the sec kill order "deleted" event.
     *
     * @param  \ShopEM\Models\SecKillOrder  $secKillOrder
     * @return void
     */
    public function deleted(SecKillOrder $secKillOrder)
    {
        //
    }

    /**
     * Handle the sec kill order "restored" event.
     *
     * @param  \ShopEM\Models\SecKillOrder  $secKillOrder
     * @return void
     */
    public function restored(SecKillOrder $secKillOrder)
    {
        //
    }

    /**
     * Handle the sec kill order "force deleted" event.
     *
     * @param  \ShopEM\Models\SecKillOrder  $secKillOrder
     * @return void
     */
    public function forceDeleted(SecKillOrder $secKillOrder)
    {
        //
    }
}
