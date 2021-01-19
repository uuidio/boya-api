<?php
/**
 * @Filename        FavoriteController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\UserGoodsFavorite;
use ShopEM\Models\UserShopFavorite;
use ShopEM\Models\Goods;
use ShopEM\Models\Shop;
use ShopEM\Services\User\UserGoodsFavoriteService;
use ShopEM\Services\User\UserShopFavoriteService;

class FavoriteController extends BaseController
{


    /**
     * 用户收藏商品
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeGoodsFavorite(Request $request)
    {
        $goods_id = intval($request->goods_id);
        if ($goods_id <= 0) {
            return $this->resFailed(406);
        }
        //判断商品是否存在
        $goods = Goods::find($goods_id);
        if (empty($goods)) {
            return $this->resFailed(406);
        }
        //获取收藏相关数据
        $data = $goods;
        $data['user_id'] = $this->user['id'];
        $favorite_data = UserGoodsFavoriteService::makeFavoriteInfo($data);
        //判断是否已添加
        $hasFavorite = UserGoodsFavoriteService::existFavorite($favorite_data['user_id'], $favorite_data['goods_id']);
        if (empty($hasFavorite)) {
            $favorite = UserGoodsFavorite::create($favorite_data);
        } else {
            return $this->resFailed(406, '请勿重复收藏');
        }

        return $this->resSuccess($favorite);
    }

    /**
     * 用户删除商品收藏记录
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|int
     */
    public function deleteGoodsFavorite(Request $request)
    {
        if (!$request->has('goods_id')) {
            return $this->resFailed(406);
        }

        $favorite = UserGoodsFavoriteService::existFavorite($this->user['id'], $request->goods_id);

        if (empty($favorite)) {
            return $this->resFailed(406);
        }

        return $this->resSuccess($favorite->delete());
    }

    /**
     * 用户商品收藏列表
     *
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function goodsFavoriteLists(Request $request)
    {
        $per_page=$request['per_page']??0;
        $per_page =$per_page?$per_page:config('app.per_page');
        $model = new UserGoodsFavorite;
        $model = $model->where(['gm_id' => $this->GMID])->where('user_id', $this->user['id']);
        $lists = $model->orderBy('id', 'desc')->paginate($per_page);

        return $this->resSuccess($lists);
    }

    /**
     * 用户收藏店铺
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeShopFavorite(Request $request)
    {
        $shop_id = intval($request->shop_id);
        if ($shop_id <= 0) {
            return $this->resFailed(406);
        }
        //判断店铺是否存在
        $shop = Shop::find($shop_id);
        if (empty($shop)) {
            return $this->resFailed(406);
        }
        //获取收藏相关数据
        $data = $shop;
        $data['user_id'] = $this->user['id'];
        $favorite_data = UserShopFavoriteService::makeFavoriteInfo($data);
        //判断是否已添加
        $hasFavorite = UserShopFavoriteService::existFavorite($favorite_data['user_id'], $favorite_data['shop_id']);
        if (empty($hasFavorite)) {
            $favorite = UserShopFavorite::create($favorite_data);
        } else {
            return $this->resFailed(406, '请勿重复收藏');
        }

        return $this->resSuccess($favorite);
    }

    /**
     * 用户删除店铺收藏记录
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|int
     */
    public function deleteShopFavorite(Request $request)
    {
        if (!$request->has('shop_id')) {
            return $this->resFailed(406);
        }

        $favorite = UserShopFavoriteService::existFavorite($this->user['id'], $request->shop_id);

        if (empty($favorite)) {
            return $this->resFailed(406);
        }

        return $this->resSuccess($favorite->delete());
    }

    /**
     * 用户店铺收藏列表
     *
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function shopFavoriteLists(Request $request)
    {
        $per_page = config('app.per_page');
        $model = new UserShopFavorite;
        $model = $model->where(['gm_id' => $this->GMID])->where('user_id', $this->user['id']);
        $lists = $model->orderBy('id', 'desc')->paginate($per_page);

        return $this->resSuccess($lists);
    }
}