<?php

namespace ShopEM\Observers;

use ShopEM\Models\CouponStockOnline;

class CouponStockOnlineObserver
{
    /**
     * Handle the coupon stock online "created" event.
     *
     * @param  \ShopEM\Models\CouponStockOnline  $couponStockOnline
     * @return void
     */
    public function created(CouponStockOnline $model)
    {
        if (isset($model->coupon_id)) 
        {
            $gm_id = \ShopEM\Models\Coupon::where('id',$model->coupon_id)->value('gm_id');
            CouponStockOnline::where('coupon_id',$model->coupon_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the coupon stock online "updated" event.
     *
     * @param  \ShopEM\Models\CouponStockOnline  $couponStockOnline
     * @return void
     */
    public function updated(CouponStockOnline $couponStockOnline)
    {
        //
    }

    /**
     * Handle the coupon stock online "deleted" event.
     *
     * @param  \ShopEM\Models\CouponStockOnline  $couponStockOnline
     * @return void
     */
    public function deleted(CouponStockOnline $couponStockOnline)
    {
        //
    }

    /**
     * Handle the coupon stock online "restored" event.
     *
     * @param  \ShopEM\Models\CouponStockOnline  $couponStockOnline
     * @return void
     */
    public function restored(CouponStockOnline $couponStockOnline)
    {
        //
    }

    /**
     * Handle the coupon stock online "force deleted" event.
     *
     * @param  \ShopEM\Models\CouponStockOnline  $couponStockOnline
     * @return void
     */
    public function forceDeleted(CouponStockOnline $couponStockOnline)
    {
        //
    }
}
