<?php
/**
 * @Filename        GoodsClassController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\GoodsClass;
use ShopEM\Repositories\GoodsClassRepository;
use ShopEM\Traits\ListsTree;

class GoodsClassController extends BaseController
{
    use ListsTree;

    /**
     * 商品分类
     *
     * @Author moocde <mo@mocode.cn>
     * @param GoodsClassRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, GoodsClassRepository $repository)
    {
        $request = $request->all();
        // if (!isset($request['gm_id'])) 
        // {
        //     $request['gm_id'] = $this->GMID;
        // }
        $request['is_show'] = 1; //这个字段反着来的（前期bug）
        $goodsClass = $repository->listItems($request);
        if (empty($goodsClass)) {
            return $this->resFailed(700);
        }

        if (isset($request['class_level'])) {
            $retrun = $this->resSuccess($goodsClass->toArray());
        } else {
            $retrun = $this->resSuccess($this->goodsClassToTree($goodsClass->toArray()));
        }

        return $retrun;
    }

    /**
     * 同辈分类
     * @Author djw
     * @param Request $request
     * @param GoodsClassRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function siblingsLists(Request $request, GoodsClassRepository $repository)
    {
        $data = [];
        if ($request->has('gc_id')) {
            $class = GoodsClass::find($request->gc_id);
            if ($class) {
                $data['parent_id'] = $class->parent_id;
            }
        }
        // if (!$request->has('gm_id')) 
        // {
        //     $data['gm_id'] = $this->GMID;
        // }
        return $this->resSuccess($repository->listItems($data));
    }
}