<?php

namespace ShopEM\Observers;

use ShopEM\Models\TradeOrder;
use ShopEM\Models\Shop;

class TradeOrderObserver
{
    /**
     * Handle the trade "created" event.
     *
     * @param  \ShopEM\Trade  $trade
     * @return void
     */
    public function created(TradeOrder $order)
    {
       	if (isset($order->shop_id) && isset($order->tid)) 
        {
            $gm_id = Shop::where('id',$order->shop_id)->value('gm_id');
            TradeOrder::where('tid',$order->tid)->update(['gm_id'=>$gm_id]);
        }
    }
}
