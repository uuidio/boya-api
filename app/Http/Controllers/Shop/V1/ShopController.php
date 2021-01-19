<?php
/**
 * @Filename        ShopController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Seller\ShopTmplRequest;
use ShopEM\Repositories\ShopAreaClassesRepository;
use ShopEM\Repositories\ShopCatsRepository;
use ShopEM\Repositories\ShopRepository;
use ShopEM\Repositories\ShopSiteConfigRepository;
use ShopEM\Traits\ListsTree;

class ShopController extends BaseController
{

    /**
     * 店铺列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request        $request
     * @param ShopRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, ShopRepository $repository)
    {
        $data = $request->all();
        $data['shop_state'] = 1;

        return $this->resSuccess($repository->search($data,1));
    }

    /**
     * 店铺信息
     *
     * @Author moocde <mo@mocode.cn>
     * @param int            $id
     * @param ShopRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0, ShopRepository $repository)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = $repository->detail($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        $favorite = \ShopEM\Services\User\UserShopFavoriteService::existFavorite($this->user['id'], $id);
        $detail['is_favorite'] = $favorite ? true : false;

        return $this->resSuccess($detail);
    }


    /**
     * 获取店铺挂件配置
     * @Author hfh
     * @param ShopTmplRequest $request
     * @param ShopSiteConfigRepository $repository
     * @return mixed
     */
    public function GetTmplInfo(ShopTmplRequest $request, ShopSiteConfigRepository $repository)
    {
        $data = $request->only('page','type','shop_id');

        if(!isset($data['shop_id'])){
            return $this->resFailed(414,'参数错误，店铺id 必传！');
        }

        $config_items = $repository->configItems_v1($data);

        return $this->resSuccess($config_items);
    }



    //引用分类树
    use ListsTree;

    /**
     * 分类列表
     * @Author hfh
     * @param ShopAreaClassesRepository $repository
     * @return mixed
     */
    public function ShopAreaClassesLists(Request $request,ShopAreaClassesRepository $repository)
    {
        $input_data = $request->all();
        //获取店铺分类列表
        $lists = $repository->listItems($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);

    }


    //引用分类树
    use ListsTree;


    /**
     * 店铺分类列表
     * @Author hfh
     * @param ShopCatsRepository $repository
     * @return mixed
     */
    public function ShopCatslists(Request $request,ShopCatsRepository $repository)
    {
        //获取商家店铺id
        $shopId = $request['shop_id']??0;
        if ($shopId <=0) {
            return $this->resFailed(414,'店铺id必传');
        }

        //获取店铺分类列表
        $lists = $repository->listItems($shopId);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);

    }

    /**
     * 店铺分类树
     * @Author hfh
     * @param ShopCatsRepository $repository
     * @return mixed
     */
    public function ShopCatsAllClassTree(Request  $request,shopCatsRepository $repository)
    {
        //获取商家店铺id
        $shopId = $request['shop_id']??0;
        if ($shopId <=0) {
            return $this->resFailed(414,'店铺id必传');
        }

        $shopCats = $repository->listItems($shopId);

        if (isset($request['class_level'])) {
            $retrun = $this->resSuccess($shopCats->toArray());
        } else {
            $retrun = $this->resSuccess($this->shopCatsToTree($shopCats->toArray()));
        }
        return $retrun;
    }
}