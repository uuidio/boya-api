<?php

namespace ShopEM\Observers;

use ShopEM\Models\Activity;

class ActivityObserver
{
    /**
     * Handle the activity "created" event.
     *
     * @param  \ShopEM\Models\Activity  $activity
     * @return void
     */
    public function created(Activity $activity)
    {
        if (isset($activity->shop_id)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$activity->shop_id)->value('gm_id');
            Activity::where('shop_id',$activity->shop_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the activity "updated" event.
     *
     * @param  \ShopEM\Models\Activity  $activity
     * @return void
     */
    public function updated(Activity $activity)
    {
        //
    }

    /**
     * Handle the activity "deleted" event.
     *
     * @param  \ShopEM\Models\Activity  $activity
     * @return void
     */
    public function deleted(Activity $activity)
    {
        //
    }

    /**
     * Handle the activity "restored" event.
     *
     * @param  \ShopEM\Models\Activity  $activity
     * @return void
     */
    public function restored(Activity $activity)
    {
        //
    }

    /**
     * Handle the activity "force deleted" event.
     *
     * @param  \ShopEM\Models\Activity  $activity
     * @return void
     */
    public function forceDeleted(Activity $activity)
    {
        //
    }
}
