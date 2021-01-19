<?php

namespace ShopEM\Observers;

use ShopEM\Models\UserShopFavorite;

class UserShopFavoriteObserver
{
    /**
     * Handle the user shop favorite "created" event.
     *
     * @param  \ShopEM\Models\UserShopFavorite  $userShopFavorite
     * @return void
     */
    public function created(UserShopFavorite $model)
    {
        if (isset($model->shop_id)) 
        {
            $gm_id = \ShopEM\Models\Shop::where('id',$model->shop_id)->value('gm_id');
            UserShopFavorite::where('shop_id',$model->shop_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the user shop favorite "updated" event.
     *
     * @param  \ShopEM\Models\UserShopFavorite  $userShopFavorite
     * @return void
     */
    public function updated(UserShopFavorite $userShopFavorite)
    {
        //
    }

    /**
     * Handle the user shop favorite "deleted" event.
     *
     * @param  \ShopEM\Models\UserShopFavorite  $userShopFavorite
     * @return void
     */
    public function deleted(UserShopFavorite $userShopFavorite)
    {
        //
    }

    /**
     * Handle the user shop favorite "restored" event.
     *
     * @param  \ShopEM\Models\UserShopFavorite  $userShopFavorite
     * @return void
     */
    public function restored(UserShopFavorite $userShopFavorite)
    {
        //
    }

    /**
     * Handle the user shop favorite "force deleted" event.
     *
     * @param  \ShopEM\Models\UserShopFavorite  $userShopFavorite
     * @return void
     */
    public function forceDeleted(UserShopFavorite $userShopFavorite)
    {
        //
    }
}
