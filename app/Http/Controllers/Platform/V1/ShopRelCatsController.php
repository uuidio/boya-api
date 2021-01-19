<?php
/**
 * @Filename ShopRelCatsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\ShopRelCat;
use ShopEM\Traits\ListsTree;
use ShopEM\Repositories\ShopRelCatsRepository;
use ShopEM\Http\Requests\Platform\ShopRelCatsRequest;


class ShopRelCatsController extends BaseController
{

    use ListsTree;

    /**
     * 商场分类列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(ShopRelCatsRepository $repository)
    {
        //获取店铺分类列表
        $lists = $repository->listItems($this->GMID);

        $lists = $this->toFormatTree($lists->toArray(), 'cat_name');
        if (empty($lists)) {
            $lists = [];
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     *  分类树
     * @Author hfh_wind
     * @param ShopRelCatsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function allShopRelCatsTree(ShopRelCatsRepository $repository)
    {
        $Class = $repository->listItems($this->GMID);
        if (empty($Class)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess($this->shopCatsToTree($Class->toArray()));
    }



    /**
     *  分类详情
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $id = intval($request->id);

        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = ShopRelCat::find($id);

        if (empty($detail) || $detail->gm_id != $this->GMID) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 创建分类
     * @Author hfh_wind
     * @param ArticleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCat(ShopRelCatsRequest $request)
    {
        $data = $request->only('cat_name', 'parent_id', 'is_show', 'order');

        if ($data['parent_id'] == 0) {
            $data['level'] = 1;
        } else {
            $data['level'] = 2;
        }

        try {
            $data['gm_id'] = $this->GMID;
            ShopRelCat::create($data);

        } catch (\Exception $e) {

            return $this->resFailed(702, $e->getMessage());
        }
        // 清空分类缓存
//        Cache::forget('all_shop_rel_cats_tree');

        return $this->resSuccess([], "创建成功!");
    }

    /**
     *  更新分类
     *
     * @Author hfh_wind
     * @param ShopFloorRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCat(ShopRelCatsRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $data = $request->only('cat_name', 'parent_id', ' is_show', 'order');

        try {
            if ($data['parent_id'] == 0) {
                $data['level'] = 1;
            } else {
                $data['level'] = 2;
            }

            $shoprelcats = ShopRelCat::find($id);

            if (empty($shoprelcats)) {
                return $this->resFailed(701);
            }

            if ($data['parent_id'] == $shoprelcats->id) {
                return $this->resFailed(701, '不能选择自己的作为父类');
            }

            $shoprelcats->update($data);

        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        // 清空分类缓存
//        Cache::forget('all_shop_rel_cats_tree');

        return $this->resSuccess([], "更新成功!");
    }

    /**
     * 删除分类
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $hasChild = ShopRelCat::where('parent_id', $id)->count();

        if ($hasChild > 0) {
            return $this->resFailed(701, '该分类下存在子类，无法直接删除');
        }

        try {
            ShopRelCat::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess([], "删除成功!");
    }

}