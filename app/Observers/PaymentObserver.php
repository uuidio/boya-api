<?php

namespace ShopEM\Observers;

use ShopEM\Models\Payment;

class PaymentObserver
{
    /**
     * Handle the payment "created" event.
     *
     * @param  \ShopEM\Models\Payment  $payment
     * @return void
     */
    public function created(Payment $model)
    {
        if (isset($model->payment_id)) 
        {
            $gm_id = \ShopEM\Models\TradePaybill::where('payment_id',$model->payment_id)->value('gm_id');
            Payment::where('payment_id',$model->payment_id)->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the payment "updated" event.
     *
     * @param  \ShopEM\Models\Payment  $payment
     * @return void
     */
    public function updated(Payment $payment)
    {
        //
    }

    /**
     * Handle the payment "deleted" event.
     *
     * @param  \ShopEM\Models\Payment  $payment
     * @return void
     */
    public function deleted(Payment $payment)
    {
        //
    }

    /**
     * Handle the payment "restored" event.
     *
     * @param  \ShopEM\Models\Payment  $payment
     * @return void
     */
    public function restored(Payment $payment)
    {
        //
    }

    /**
     * Handle the payment "force deleted" event.
     *
     * @param  \ShopEM\Models\Payment  $payment
     * @return void
     */
    public function forceDeleted(Payment $payment)
    {
        //
    }
}
