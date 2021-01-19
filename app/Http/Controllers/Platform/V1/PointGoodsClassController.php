<?php

/**
 * PointGoodsClassController.php
 * @Author: swl
 * @Date:   2020-03-19
 */
namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Repositories\PointGoodsClassRepository;
use ShopEM\Models\PointGoodsClass;
use ShopEM\Models\PointActivityGoods;
use ShopEM\Http\Requests\Group\PointGoodsClassRequest;


class PointGoodsClassController extends BaseController
{
     
     /**
     * 积分商品分类列表
     *
     * @Author swl
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(PointGoodsClassRepository $repository,Request $request)
    {      
        $data = $request->all();
        $data['per_page'] = isset($data['per_page']) ? $data['per_page'] : config('app.per_page');
        $data['gm_id'] = $this->GMID;
        $lists = $repository->listItems($data);
        if (empty($lists)) {
            $lists = [];
        }
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

	  /**
     * 创建分类
     * @Author swl
     * @param PointGoodsClassRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(PointGoodsClassRequest $request)
    {
        $data = $request->only('cat_name','is_show', 'order');
        $data['gm_id'] = $this->GMID;
        try {
            PointGoodsClass::create($data);

        } catch (\Exception $e) {

            return $this->resFailed(702, $e->getMessage());
        }
        return $this->resSuccess([], "创建成功!");
    }

     /**
     *  更新分类
     *
     * @Author swl
     * @param ShopFloorRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(PointGoodsClassRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $data = $request->only('cat_name', 'is_show', 'order');

        try {
            $class = PointGoodsClass::find($id);

            if (empty($class)) {
                return $this->resFailed(701,'数据不存在');
            }

            $class->update($data);

        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess([], "更新成功!");
    }

     /**
     * 删除分类
     * @Author swl
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $id = intval($request->id);
        $class = PointGoodsClass::find($id);
        if ($id <= 0 || empty($class)) {
            return $this->resFailed(414,'数据不存在');
        }

        $hasGood = PointActivityGoods::where('point_class_id', $id)->count();

        if ($hasGood > 0) {
            return $this->resFailed(701, '该分类下存在商品，无法直接删除');
        }

        try {
            PointGoodsClass::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess([], "删除成功!");
    }

    // 获取详情 swl 2020-4-1
    public function detail(Request $request){
        $id = intval($request->id);
        $class = PointGoodsClass::find($id);
        if ($id <= 0 || empty($class)) {
            return $this->resFailed(414,'数据不存在');
        }
        return $this->resSuccess($class);
    }   

}
