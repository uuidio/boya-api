<?php
/**
 * @Filename        UserShopFavoriteService.php
 *
 */
namespace ShopEM\Services\User;

use ShopEM\Models\UserShopFavorite;

class UserShopFavoriteService
{

    /**
     * 获取收藏店铺的相关数据
     *
     * @Author djw
     * @param $data
     * @return array
     * @throws \Exception
     */
    public static function makeFavoriteInfo($data)
    {
        $favorite_data = [
            'user_id' => $data['user_id'],
            'shop_id' => $data['id'],
            'shop_name' => $data['shop_name'],
            'shop_logo' => $data['shop_logo'],
            'created_at' => time(),
            'updated_at' => time(),
        ];
        return $favorite_data;
    }

    /**
     * 店铺收藏里是否存在同样的店铺
     *
     * @Author djw
     * @param $user_id
     * @param $shop_id
     * @return mixed
     */
    public static function existFavorite($user_id, $shop_id)
    {
        return UserShopFavorite::where('user_id', $user_id)
            ->where('shop_id', $shop_id)
            ->first();
    }



}