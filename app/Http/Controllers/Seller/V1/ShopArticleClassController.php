<?php
/**
 * @Filename ShopArticleClassController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Repositories\ShopArticleClassRepository;
use ShopEM\Traits\ListsTree;
use ShopEM\Http\Requests\Seller\ShopArticleClassRequest;
use ShopEM\Models\ShopArticleClass;
use ShopEM\Services\ArticleService;
use Illuminate\Support\Facades\DB;

class ShopArticleClassController extends BaseController
{
    use ListsTree;

    /**
     * 获取全部数据
     *
     * @Author djw
     * @return mixed
     */
    public function lists(ShopArticleClassRepository $shopArticleClassRepository)
    {
        $data['shop_id'] = $this->shop->id;
        $lists = $shopArticleClassRepository->listItems($data);
        $lists = $this->toFormatTree($lists->toArray(), 'name');

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $shopArticleClassRepository->listShowFields(),
        ]);
    }


    /**
     * 文章分类树
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */

    public function allClassTree(ShopArticleClassRepository $shopArticleClassRepository)
    {
        $data['shop_id'] = $this->shop->id;
        $goodsClass = $shopArticleClassRepository->listItems($data);
        if (empty($goodsClass)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess($this->platformArticleClassToTree($goodsClass->toArray()));
    }

    /**
     *  文章分类详情
     *
     * @Author djw
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = ShopArticleClass::where('id', $id)->where('shop_id', $this->shop->id)->first();

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 创建分类
     *
     * @Author djw
     * @param ShopArticleClassRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ShopArticleClassRequest $request)
    {
        $data =$request->only('name', 'parent_id', 'listorder');
        $data['parent_id'] = isset($data['parent_id']) && $data['parent_id'] ? $data['parent_id'] : 0;
        $data['listorder'] = isset($data['listorder']) && $data['listorder'] ? $data['listorder'] : 0;
        $data['shop_id'] = $this->shop->id;
        DB::beginTransaction();
        try {
            ArticleService::shopClassesCheckData($data);
            ShopArticleClass::create($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 更新文章分类
     *
     * @Author djw
     * @param ShopArticleClassRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ShopArticleClassRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $data =$request->only('name', 'parent_id', 'listorder');

        try {
            $class = ShopArticleClass::where('id', $id)->where('shop_id', $this->shop->id)->first();
            if(empty($class)) {
                return $this->resFailed(701);
            }

            $data['parent_id'] = isset($data['parent_id']) && $data['parent_id'] ? $data['parent_id'] : 0;
            $data['listorder'] = isset($data['listorder']) && $data['listorder'] ? $data['listorder'] : 0;
            $data['shop_id'] = $this->shop->id;
            ArticleService::shopClassesCheckData($data, $id);
            ShopArticleClass::where('id', $id)->where('shop_id', $this->shop->id)->update($data);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 删除文章分类
     * @Author djw
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }


        $class = ShopArticleClass::where('id', $id)->where('shop_id', $this->shop->id)->first();
        if(empty($class)) {
            return $this->resFailed(414);
        }

        $hasChild = ShopArticleClass::where('parent_id', $id)->count();

        if ($hasChild > 0) {
            return $this->resFailed(701, '该分类下存在子类，无法直接删除');
        }

        try {
            ShopArticleClass::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }
}