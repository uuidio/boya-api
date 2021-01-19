<?php
/**
 * @Filename GoodsClassController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\GoodsClassRequest;
use ShopEM\Models\GoodsClass;
use ShopEM\Models\GoodsType;
use ShopEM\Repositories\GoodsClassRepository;
use ShopEM\Traits\ListsTree;

class GoodsClassController extends BaseController
{
    use ListsTree;


    /**
     * 商品分类列表
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request,GoodsClassRepository $goodsClassRepository)
    {
        $data = [];
        if ($request->has('class_level')) {
            $data['class_level'] = $request->class_level;
            if (!$data['class_level']) {
                unset($data['class_level']);
            }
        }
         $data['gm_id'] = $this->platform->gm_id??1;
        $lists = $goodsClassRepository->listItems($data);
        if (!isset($data['class_level'])) {
            $lists = $this->toFormatTree($lists->toArray(), 'gc_name');
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $goodsClassRepository->listShowFields(),
        ]);
    }

    /**
     * 商品分类树
     *
     * @Author moocde <mo@mocode.cn>
     * @param GoodsClassRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function allClassTree(GoodsClassRepository $goodsClassRepository)
    {
        $data = [];
         $data['gm_id'] = $this->platform->gm_id??1;
        $goodsClass = $goodsClassRepository->listItems($data);
        if (empty($goodsClass)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess($this->goodsClassToTree($goodsClass->toArray()));
    }

    /**
     * 分类详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);

        $detail = [];

        if ($id) {
            $detail['GoodsClass'] = GoodsClass::find($id);
            // 关联的类型
            $detail['goods_type_related_id'] = $detail['GoodsClass']->type_id;
        }

        $detail['GoodsType'] = GoodsType::where('gm_id',$this->GMID)->orderBy('type_sort', 'desc')->get();

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 创建分类
     *
     * @Author moocde <mo@mocode.cn>
     * @param GoodsClassRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(GoodsClassRequest $request)
    {
        $data = $request->only('gc_name', 'parent_id', 'type_name', 'type_id', 'associated', 'commis_rate', 'listorder',
            'is_show', 'class_icon', 'seo_title',
            'seo_keywords', 'seo_description');

        $associated = '';
        if (isset($data['associated'])) {
            $associated = $data['associated'];
            unset($data['associated']);
        }

        if ($data['parent_id'] == 0) {
            $data['class_level'] = 1;
        } else {
            $parent = GoodsClass::find($data['parent_id']);
            $data['class_level'] = $parent->class_level + 1;
        }
        $msg_text="创建分类".$data['gc_name'];
        try {
            $data['gm_id'] = $this->platform->gm_id??1;
            GoodsClass::create($data);

            $show_type = isset($data['show_type']) ? $data['show_type'] : '';
            // 更新分类信息
            if ($associated == '1' || $show_type == '1') {
                $gc_id_list = $this->getChildClass($id);

                $update = [];
                // 更新该分类下子分类的所有类型
                if ($associated == '1') {
                    $update['type_id'] = $data['type_id'];
                    $update['type_name'] = $data['type_name'];
                }
                // 商品展示方式
                if ($show_type == '1') {
                    $update['is_show'] = $data['is_show'];
                }

                GoodsClass::whereIn('id', $gc_id_list)->update($update);
            }
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        // 清空分类缓存
        Cache::forget('all_goods_class_tree');

        return $this->resSuccess();
    }


    /**
     * 更新分类数据
     * @Author hfh_wind
     * @param GoodsClassRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function goodsClassUpdate(GoodsClassRequest $request)
    {

        $data = $request->only('id', 'gc_name', 'type_name', 'type_id', 'associated', 'show_type', 'parent_id',
            'commis_rate', 'listorder', 'is_show', 'class_icon', 'seo_title', 'seo_keywords', 'seo_description');

        $id = intval($data['id']);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $associated = '';
        if (isset($data['associated'])) {
            $associated = $data['associated'];
            unset($data['associated']);
        }

        $goodsClass = GoodsClass::find($id);
        if (empty($goodsClass)) {
            return $this->resFailed(701, "分类信息不存在!");
        }

        $msg_text="修改分类-".$goodsClass['id']."-".$goodsClass['gc_name'];

        try {
            if (isset($data['parent_id'])) {
                if ($data['parent_id'] == 0) {
                    $data['class_level'] = 1;
                } else {
                    $parent = GoodsClass::find($data['parent_id']);
                    $data['class_level'] = $parent->class_level + 1;
                }

                if ($data['parent_id'] == $data['id']) {
                    return $this->resFailed(701, '不能选择自己的作为父类');
                }
            }


            $goodsClass->update($data);

            $show_type = isset($data['show_type']) ? $data['show_type'] : '';
            // 更新分类信息
            if ($associated == '1' || $show_type == '1') {
                $gc_id_list = $this->getChildClass($id);

                $update = [];
                // 更新该分类下子分类的所有类型
                if ($associated == '1') {
                    $update['type_id'] = $data['type_id'];
                    $update['type_name'] = $data['type_name'];
                    /*          $update=[];
                              foreach ($gc_id_list as $gc_idk => $gc_idv) {
                                  $update[$gc_idk]['id'] = $gc_idv;
                                  $update[$gc_idk]['type_id'] = $data['type_id'];
                                  $update[$gc_idk]['type_name'] = $data['type_name'];
                              }
                    */
                }
                // 商品展示方式
                if ($show_type == '1') {
                    $update['is_show'] = $data['is_show'];
                }

                GoodsClass::whereIn('id', $gc_id_list)->update($update);
        //$goodsClass = new GoodsClass();
        //$goodsClass->updateBatch($update);
            }
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }

        //日志
        $this->adminlog($msg_text, 1);

        // 清空分类缓存
        Cache::forget('all_goods_class_tree');
        return $this->resSuccess();
    }


    /**
     * 删除分类
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $hasChild = GoodsClass::where('parent_id', $id)->count();

        if ($hasChild > 0) {
            return $this->resFailed(701, '该分类下存在子类，无法直接删除');
        }

        $hasGoods = \ShopEM\Models\Goods::where('gc_id',$id)->count();

        if ($hasGoods > 0) {
            return $this->resFailed(701, '有商品使用该分类，无法直接删除');
        }
        $msg_text="删除分类id为".$id;
        try {
            GoodsClass::destroy($id);
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }

        //日志
        $this->adminlog($msg_text, 1);

        // 清空分类缓存
        Cache::forget('all_goods_class_tree');
        return $this->resSuccess();
    }


    /**
     * 取指定分类ID下的所有子类
     * @param int /array $parent_id 父ID 可以单一可以为数组
     * @return array $rs_row 返回数组形式的查询结果
     */
    public function getChildClass($parent_id)
    {
        $gm_id = $this->platform->gm_id??1;
        $all_class = GoodsClass::where('gm_id',$gm_id)->orderBy('id', 'asc')->get()->toArray();
        if (is_array($all_class)) {
            if (!is_array($parent_id)) {
                $parent_id = array($parent_id);
            }

            $result = array();
            foreach ($all_class as $k => $v) {
                $gc_id = $v['id'];//返回的结果包括父类
                $gc_parent_id = $v['parent_id'];
//                print_r($v['id']);echo"\\\\";
                if (in_array($gc_id, $parent_id) || in_array($gc_parent_id, $parent_id)) {
                    $parent_id[] = $v['id'];
                    $result[] = $v['id'];
//                    print_r($v['parent_id']);echo"---";
                }
            }
//            dd($parent_id);
            $return = $result;
        } else {
            $return = false;
        }
        return $return;
    }


    /*
   * 获取所有下级
   * @param $id String 待查找的id
   * @return String | NULL 失败返回null
   */
    public function getSub($id, $level = 1)
    {
        $isComma = strstr($id, ',');
        $ids = '';
        if ($isComma) {

            $res = GoodsClass::whereIn(['parent_id' => $id])->select('id')->get();
        } else {

            $res = GoodsClass::where(['parent_id' => $id])->select('id')->get();
        }

        if ($res) {

            $id = '';

            foreach ($res as $k => $v) {
                if ($v['id'] > 0) {
                    if ($k == 0) {
                        $id = $v['id'];
                    } else {
                        $id .= ',' . $v['id'];
                    }
                }
            }

            if ($isComma) {
                $ids .= ";" . $id;
            } else {
                $ids .= $id;
            }
            $ids .= '-lv' . $level . $this->getSub($id, $level + 1);
        }
        return $ids;
    }


}