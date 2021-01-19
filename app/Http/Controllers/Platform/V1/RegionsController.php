<?php
/**
 * @Filename RegionsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\ShopRegion;
use ShopEM\Repositories\RegionsClassRepository;
use ShopEM\Traits\RegionsTree;

class RegionsController extends BaseController
{

    use RegionsTree;

    protected $regionsClassRepository;

    public function __construct(RegionsClassRepository $regionsClassRepository)
    {
        parent::__construct();
        $this->regionsClassRepository = $regionsClassRepository;
    }



    /**
     * 获取全部数据
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function lists()
    {
        $lists = $this->regionsClassRepository->listItems();
        $lists = $this->toFormatTree($lists->toArray(), 'region_name');

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $this->regionsClassRepository->listShowFields(),
        ]);
    }


    /**
     * 地区分类树
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */

    public function allClassTree()
    {
        $goodsClass = $this->regionsClassRepository->listItems();
        if (empty($goodsClass)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess($this->RegionsClassToTree($goodsClass->toArray()));
    }

    /**
     *  地区详情
     *
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = ShopRegion::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 创建分类
     *
     * @Author hfh_wind
     * @param GoodsClassRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(GoodsClassRequest $request)
    {
        $data =$request->only('region_name', 'parent_id', 'order_sort', 'disabled');
        if ($data['parent_id'] == 0) {
            $data['level'] = 1;
        } else {
            $parent = ShopRegion::find($data['parent_id']);
            $data['level'] = $parent->level + 1;
        }
        ShopRegion::create($data);

        // 清空地区缓存
        Cache::forget('all_regions_tree');

        return $this->resSuccess();
    }





    /**
     * 更新地区数据
     *
     * @Author hfh_wind
     * @param GoodsClassRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(GoodsClassRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $data = $request->only('region_name', 'parent_id', 'order_sort', 'disabled');

        try {
            if ($data['parent_id'] == 0) {
                $data['level'] = 1;
            } else {
                $parent = ShopRegion::find($data['parent_id']);
                $data['level'] = $parent->level + 1;
            }
            $goodsClass = ShopRegion::find($id);
            if (empty($goodsClass)) {
                return $this->resFailed(701);
            }

            if ($data['parent_id'] == $goodsClass->id) {
                return $this->resFailed(701, '不能选择自己的作为父类');
            }

            $goodsClass->update($data);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        // 清空地区缓存
        Cache::forget('all_regions_tree');
        return $this->resSuccess();
    }

    /**
     * 删除地区
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $hasChild = ShopRegion::where('parent_id', $id)->count();

        if ($hasChild > 0) {
            return $this->resFailed(701, '该地区下存在子类，无法直接删除');
        }

        try {
            ShopRegion::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
        // 清空地区缓存
        Cache::forget('all_regions_tree');

        return $this->resSuccess();
    }

}