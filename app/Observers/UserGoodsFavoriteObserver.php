<?php

namespace ShopEM\Observers;

use ShopEM\Models\UserGoodsFavorite;

class UserGoodsFavoriteObserver
{
    /**
     * Handle the user goods favorite "created" event.
     *
     * @param  \ShopEM\Models\UserGoodsFavorite  $userGoodsFavorite
     * @return void
     */
    public function created(UserGoodsFavorite $model)
    {
        if (isset($model->goods_id)) 
        {
            $gm_id = \ShopEM\Models\Goods::where('id',$model->goods_id)->value('gm_id');
            UserGoodsFavorite::where('goods_id',$model->goods_id)->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }
    }

    /**
     * Handle the user goods favorite "updated" event.
     *
     * @param  \ShopEM\Models\UserGoodsFavorite  $userGoodsFavorite
     * @return void
     */
    public function updated(UserGoodsFavorite $userGoodsFavorite)
    {
        //
    }

    /**
     * Handle the user goods favorite "deleted" event.
     *
     * @param  \ShopEM\Models\UserGoodsFavorite  $userGoodsFavorite
     * @return void
     */
    public function deleted(UserGoodsFavorite $userGoodsFavorite)
    {
        //
    }

    /**
     * Handle the user goods favorite "restored" event.
     *
     * @param  \ShopEM\Models\UserGoodsFavorite  $userGoodsFavorite
     * @return void
     */
    public function restored(UserGoodsFavorite $userGoodsFavorite)
    {
        //
    }

    /**
     * Handle the user goods favorite "force deleted" event.
     *
     * @param  \ShopEM\Models\UserGoodsFavorite  $userGoodsFavorite
     * @return void
     */
    public function forceDeleted(UserGoodsFavorite $userGoodsFavorite)
    {
        //
    }
}
