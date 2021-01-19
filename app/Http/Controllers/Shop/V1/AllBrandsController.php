<?php
/**
 * @Filename        AllBrandsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\ShopFloor;
use ShopEM\Repositories\ShopRelCatsRepository;
use ShopEM\Repositories\ShopRepository;

class AllBrandsController extends BaseController
{
    /**
     *  全部品牌关联店铺
     *
     * @Author hfh_wind
     * @param GoodsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function AllBrandShops(Request $request, ShopRepository $repository)
    {
        $data = $request->all();
//        $user_id = $request->user_id;

        $data['per_page'] = config('app.per_page');
        $data['shop_state'] = 1;
        if (!isset($data['gm_id'])) {
            $data['gm_id'] = $this->GMID;
        }
        $lists = $repository->search($data);
//        if (!empty($user_id)) {
//            foreach ($lists as $key => $value) {
//            dd();
//
//            }
//        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     *  获取商场楼层信息
     *
     * @Author hfh_wind
     * @return int
     */
    public function getShopFloors(Request $request)
    {
        $data = $request->all();
        if (!isset($data['gm_id'])) {
            $data['gm_id'] = $this->GMID;
        }
        $info = ShopFloor::where('gm_id',$data['gm_id'])->where(['is_show' => '1'])->get();

        if (count($info) < 0) {
            $info = [];
        }
        return $this->resSuccess($info);
    }


    /**
     *  商场店铺分类树
     *
     * @Author hfh_wind
     * @param ShopRelCatsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function allShopRelCatsTree(Request $request,ShopRelCatsRepository $repository)
    {
        $data = $request->all();
        if (!isset($data['gm_id'])) {
            $data['gm_id'] = $this->GMID;
        }
        $Class = $repository->listItems($data['gm_id']);
        if (empty($Class)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($this->ToTree($Class->toArray()));
    }


    /**
     * 生成树形店铺分类
     *
     * @Author moocde <mo@mocode.cn>
     * @param $list
     * @return array
     */
    protected function ToTree($list) {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $tmp = [];
                $tmp['value'] = strval($data['id']);
                $tmp['label'] = $data['cat_name'];
                $tmp['parent_id'] = $data['parent_id'];
                //$tmp['class_icon'] = $data['class_icon'];
                $tmp['class_level'] = $data['level'];
                $tmp['count_shop'] = $data['count_shop'];
                $refer[$data['id']] = $tmp;
            }
            foreach ($refer as $key => $data) {
                // 判断是否存在parent
                $parentId = $data['parent_id'];
                if ($parentId == 0) {
                    $tree[] = &$refer[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
                        $parent['children'][] = &$refer[$key];
//                        $level = 'lv'.$data['class_level']; //children改成子级的层级 djw
//                        $parent[$level][] = &$refer[$key];
                    }
                }
            }
        }

        return $tree;
    }

}