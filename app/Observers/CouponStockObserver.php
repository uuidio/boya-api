<?php

namespace ShopEM\Observers;

use ShopEM\Models\CouponStock;

class CouponStockObserver
{
    /**
     * Handle the coupon stock "created" event.
     *
     * @param  \ShopEM\Models\CouponStock  $couponStock
     * @return void
     */
    public function created(CouponStock $model)
    {
        // if (isset($model->coupon_id)) 
        // {
        //     $gm_id = \ShopEM\Models\Coupon::where('id',$model->coupon_id)->value('gm_id');
        //     CouponStock::where('coupon_id',$model->coupon_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        // }
    }

    /**
     * Handle the coupon stock "updated" event.
     *
     * @param  \ShopEM\Models\CouponStock  $couponStock
     * @return void
     */
    public function updated(CouponStock $couponStock)
    {
        //
    }

    /**
     * Handle the coupon stock "deleted" event.
     *
     * @param  \ShopEM\Models\CouponStock  $couponStock
     * @return void
     */
    public function deleted(CouponStock $couponStock)
    {
        //
    }

    /**
     * Handle the coupon stock "restored" event.
     *
     * @param  \ShopEM\Models\CouponStock  $couponStock
     * @return void
     */
    public function restored(CouponStock $couponStock)
    {
        //
    }

    /**
     * Handle the coupon stock "force deleted" event.
     *
     * @param  \ShopEM\Models\CouponStock  $couponStock
     * @return void
     */
    public function forceDeleted(CouponStock $couponStock)
    {
        //
    }
}
