<?php
/**
 * @Filename        UserGoodsFavoriteService.php
 *
 */
namespace ShopEM\Services\User;

use ShopEM\Models\UserGoodsFavorite;

class UserGoodsFavoriteService
{

    /**
     * 获取收藏商品的相关数据
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
            'goods_id' => $data['id'],
            'shop_id' => $data['shop_id'],
            'gc_id' => $data['gc_id'],
            'gm_id' => $data['gm_id'], //记录项目id
            'goods_name' => $data['goods_name'],
            'goods_price' => $data['goods_price'],
            'goods_image' => $data['goods_image'],
            'created_at' => time(),
            'updated_at' => time(),
        ];
        return $favorite_data;
    }

    /**
     * 商品收藏里是否存在同样的商品
     *
     * @Author djw
     * @param $user_id
     * @param $goods_id
     * @return mixed
     */
    public static function existFavorite($user_id, $goods_id)
    {
        return UserGoodsFavorite::where('user_id', $user_id)
            ->where('goods_id', $goods_id)
            ->first();
    }



}