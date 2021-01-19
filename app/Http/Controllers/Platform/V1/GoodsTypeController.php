<?php
/**
 * @Filename GoodsTypeController.php
 * 商品类型
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author        hfh
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\GoodsTypeRequest;
use ShopEM\Http\Requests\Platform\GoodsSpecValuesRequest;
use ShopEM\Models\GoodsAttribute;
use ShopEM\Models\GoodsAttributeValue;
use ShopEM\Models\GoodsSpec;
use ShopEM\Models\GoodsType;
use ShopEM\Models\Brand;
use ShopEM\Models\GoodsTypeBrand;
use ShopEM\Models\GoodsTypeSpec;
use ShopEM\Repositories\GoodsTypeRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GoodsTypeController extends BaseController
{



    /**
     * 类型列表
     *
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request,GoodsTypeRepository $GoodsTypeRepository)
    {
        $data = $request->all();
        $data['gm_id'] = $this->GMID;
        $lists = $GoodsTypeRepository->listItems($data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $GoodsTypeRepository->listShowFields(),
        ]);
    }

    /**
     * 类型详情
     *
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        //所有品牌
        $gm_id = $this->GMID;
        // all_brands_gmid_
        // 升级成集团统一管理
        $all_brands = Cache::remember('all_brands_group', cacheExpires(), function () {
            return Brand::where('gm_id',$gm_id)->orderBy('brand_initial')->get();
        });
        $detail['all_brands'] = $all_brands;

        //所有规格(暂时废弃,无需关联规格)
//        $spec_list = GoodsSpec::orderBy('sp_sort')->get();
//        $detail['spec_list'] = $spec_list;

        if ($id) {  //编辑类型,展示页面

            $detail['GoodsType'] = GoodsType::find($id);

            // 类型与品牌关联
            $brand_related = GoodsTypeBrand::where(['type_id' => $id])->get();
            $detail['brand_related'] = $brand_related;

            // 规格关联列表(暂时废弃,无需关联规格)
            //$spec_related = GoodsTypeSpec::where(['type_id' => $id])->get();

            //$detail['spec_related'] = $spec_related;

            // 属性
            $attr_list = GoodsAttribute::where(['type_id' => $id])->orderBy('attr_sort')->get();

            $detail['attr_list'] = $attr_list;

        }

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 添加类型
     * @Author hfh_wind
     * @param BrandRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(GoodsTypeRequest $request)
    {
        $data = $request->only('type_name', 'spec_id', 'brand_id', 'attribute_value', 'type_sort', 'class_id',
            'class_name');


        if (isset($data['spec_id'])) {
            $data['spec_id'] = is_array($data['spec_id']) ? $data['spec_id'] : json_decode($data['spec_id'], true);
        }
        if (isset($data['brand_id'])) {
            $data['brand_id'] = is_array($data['brand_id']) ? $data['brand_id'] : json_decode($data['brand_id'], true);
        }

        if (isset($data['attribute_value'])) {
            $data['attribute_value'] = is_array($data['attribute_value']) ? $data['attribute_value'] : json_decode($data['attribute_value'],
                true);
        }
        $msg_text="添加类型".$data['type_name'];

        DB::beginTransaction();
        try {

            $goodsTypeData['type_name'] = $data['type_name'];
            $goodsTypeData['type_sort'] = isset($v['type_sort']) ? $v['type_sort'] : 0;
            $goodsTypeData['class_id'] = isset($data['class_id']) ? $data['class_id'] : 0;
            $goodsTypeData['class_name'] = isset($data['class_name']) ? $data['class_name'] : '';
            $goodsTypeData['gm_id'] = $this->GMID;
            $res = GoodsType::create($goodsTypeData);
            $type_id = $res->id;

            //添加类型与品牌对应
            if (!empty($data['brand_id'])) {
                $brand_array = $data['brand_id'];
                $return = $this->typeBrandAdd($brand_array, $type_id);
                if (!$return) {
                    return $this->resFailed(701, "品牌关联更新失败!");
                }
            }
            //添加类型与规格对应(暂时废弃,无需关联规格)
            /*  if (!empty($data['spec_id'])) {
                  $spec_array = $data['spec_id'];
                  $return = $this->typeSpecAdd($spec_array, $type_id);
                  if (!$return) {
                      return $this->resFailed(701, "规格关联更新失败!");
                  }
              }*/

            // 转码  防止GBK下用中文逗号截取不正确
            $comma = '，';

            //添加类型属性
            if (!empty($data['attribute_value'])) {
                $attribute_array = $data['attribute_value'];

                foreach ($attribute_array as $v) {

                    if ($v['attr_value'] != '') {
                        $v['attr_value'] = str_replace($comma, ',', $v['attr_value']);
                        //添加属性
                        $attr_array = [];
                        $attr_array['attr_name'] = $v['attr_name'];
                        $attr_array['attr_value'] = $v['attr_value'];
                        $attr_array['type_id'] = $type_id;
                        $attr_array['attr_sort'] = isset($v['attr_sort']) ? $v['attr_sort'] : 0;
                        $attr_array['attr_show'] = isset($v['attr_show']) ? $v['attr_show'] : 0;
                        $attr_array['gm_id'] = $this->GMID;

                        $attr_id = GoodsAttribute::create($attr_array);
                        if (!$attr_id) {
                            return $this->resFailed(701, "属性添加失败!");
                        }
                        //添加属性值
                        $attr_value = explode(',', $v['attr_value']);

                        if (!empty($attr_value)) {
                            $attr_array = array();
                            foreach ($attr_value as $val) {
                                $tpl_array = array();
                                $tpl_array['attr_value_name'] = $val;
                                $tpl_array['attr_id'] = $attr_id->id;
                                $tpl_array['type_id'] = $type_id;
                                $tpl_array['attr_value_sort'] = 0;
                                $tpl_array['updated_at'] = $tpl_array['created_at'] = Carbon::now()->toDateTimeString();
                                $tpl_array['gm_id'] = $this->GMID;
                                $attr_array[] = $tpl_array;
                            }

                            $return = GoodsAttributeValue::insert($attr_array);
                            if (!$return) {
                                return $this->resFailed(701, "属性值添加失败!");
                            }
                        }
                    }
                }
            }


        } catch (\Exception $e) {
            DB::rollback();
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        DB::commit();
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess();
    }

    /**
     * 更新类型
     * @Author hfh_wind
     * @param GoodsSpecRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(GoodsTypeRequest $request)
    {

        $data = $request->only('id', 'type_name', 'spec_id', 'brand_id', 'attribute_value', 'attribute_del',
            'type_sort', 'class_id', 'class_name');

        if (!isset($data['id']) || intval($data['id']) <= 0) {
            return $this->resFailed(414, "请传入id!");
        }

        if (isset($data['spec_id'])) {
            $data['spec_id'] = is_array($data['spec_id']) ? $data['spec_id'] : json_decode($data['spec_id'], true);
        }
        if (isset($data['brand_id'])) {
            $data['brand_id'] = is_array($data['brand_id']) ? $data['brand_id'] : json_decode($data['brand_id'], true);
        }

        if (isset($data['attribute_value'])) {
            $data['attribute_value'] = is_array($data['attribute_value']) ? $data['attribute_value'] : json_decode($data['attribute_value'],
                true);
        }

        if (isset($data['attribute_del'])) {
            $data['attribute_del'] = is_array($data['attribute_del']) ? $data['attribute_del'] : json_decode($data['attribute_del'],
                true);
        }
        $id = $data['id'];
        $goodsType = GoodsType::find($id);
        if (empty($goodsType)) {
            return $this->resFailed(701);
        }

        $msg_text="更新类型-".$id."-".$goodsType['type_name'];

        DB::beginTransaction();
        try {


            $goodsTypeData['type_name'] = $data['type_name'];
            $goodsTypeData['type_sort'] = $data['type_sort'];
            $goodsTypeData['class_id'] = isset($data['class_id']) ? $data['class_id'] : 0;
            $goodsTypeData['class_name'] = isset($data['class_name']) ? $data['class_name'] : "";

            $goodsType->where(['id' => $id])->update($goodsTypeData);

            //更新属性关联表信息

            //品牌关联
            if (!empty($data['brand_id'])) {
                GoodsTypeBrand::where(['type_id' => $id])->delete();
                $brand_array = $data['brand_id'];
                $return = $this->typeBrandAdd($brand_array, $id);
                if (!$return) {
                    return $this->resFailed(701, "品牌关联更新失败!");
                }
            }

            //规格关联(暂时废弃,无需关联规格)
            /*  if (!empty($data['spec_id'])) {
                  GoodsTypeSpec::where(['type_id' => $id])->delete();
                  $spec_array = $data['spec_id'];
                  $return = $this->typeSpecAdd($spec_array, $id);
                  if (!$return) {
                      return $this->resFailed(701, "规格关联更新失败!");
                  }
              }*/

            // 属性
            // 转码  防止GBK下用中文逗号截取不正确
            $comma = '，';

            if (!empty($data['attribute_value'])) {
                $attribute_array = $data['attribute_value'];
                foreach ($attribute_array as $v) {

                    // 要删除的属性id
                    $del_array = array();
                    if (!empty($data['attribute_del'])) {
                        $del_array = $data['attribute_del'];
                    }

                    $v['attr_value'] = str_replace($comma, ',',
                        $v['attr_value']);                      //把属性值中的中文逗号替换成英文逗号

                    if (isset($v['id']) && !in_array($v['id'], $del_array)) {
                        $goodsAttribute_info = GoodsAttribute::find($v['id']);
                        if ($goodsAttribute_info) {
                            //属性修改
                            $attr_array = [];
                            $attr_array['attr_name'] = $v['attr_name'];
//                            $attr_array['attr_value'] = $v['attr_value'];
                            $attr_array['attr_sort'] = $v['attr_sort'];
                            $attr_array['attr_show'] = intval($v['attr_show']);
                            $attr_return = GoodsAttribute::where(['id' => $v['id']])->update($attr_array);

                            if (!$attr_return) {
                                return $this->resFailed(701, "属性更新失败!");
                            }
                            //更新属性值另外方法操作,这里不做处理

                        }

                    } else {
                        //属性修改
                        $attr_array = [];
                        $attr_array['attr_name'] = $v['attr_name'];
//                            $attr_array['attr_value'] = $v['attr_value'];
                        $attr_array['attr_sort'] = $v['attr_sort'];
                        $attr_array['type_id'] = $id;
                        $attr_array['attr_show'] = intval($v['attr_show']);
                        $attr_array['gm_id'] = $this->GMID;
                        $attr_return = GoodsAttribute::create($attr_array);
                        if (!$attr_return) {
                            return $this->resFailed(701, "属性添加失败!");
                        }
                    }
                }
                // 删除属性
                if (!empty($data['attribute_del'])) {
                    $del_id = '"' . implode('","', $data['attribute_del']) . '"';
                    GoodsAttributeValue::whereIn('attr_id', $del_id)->delete();  //删除属性值
                    GoodsAttribute::whereIn('id', $del_id)->delete();   //删除属性
                }

            }


        } catch (\Exception $e) {
            DB::rollback();
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        DB::commit();
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess();
    }

    /**
     * 删除类型
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $goodsType = GoodsType::find($id);
        if (empty($goodsType)) {
            return $this->resFailed(701);
        }
        $msg_text="删除类型-".$goodsType['id']."-".$goodsType['type_name'];
        DB::beginTransaction();
        try {
            //删除类型
            GoodsType::destroy($id);
            //删除属性值表
            GoodsAttributeValue::where(['type_id' => $id])->delete();
            //删除属性
            GoodsAttribute::where(['type_id' => $id])->delete();
            //删除对应品牌
            GoodsTypeBrand::where(['type_id' => $id])->delete();
            //删除对应规格
//            GoodsTypeSpec::where(['type_id' => $id])->delete();

        } catch (\Exception $e) {
            DB::rollback();
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        DB::commit();
        //日志
        $this->adminlog($msg_text, 0);
        return $this->resSuccess();
    }


    /**
     * 添加对应品牌关系信息
     *
     * @Author hfh_wind
     * @param $param
     * @param $id
     * @return bool
     */
    public function typeBrandAdd($param, $id)
    {
        if (!is_array($param)) {
            return true;
        }
        $brand_data = [];
        $time = Carbon::now()->toDateTimeString();
        foreach ($param as $brand_v) {
            $brand_data[] = ['type_id' => $id, 'brand_id' => $brand_v, 'updated_at' => $time, 'created_at' => $time];
        }
        return GoodsTypeBrand::insert($brand_data);
    }


    /**
     * 添加对应规格关系信息
     * @param string $table 表名
     * @param array $param 一维数组
     * @param string $id
     * @return bool
     */
    public function typeSpecAdd($param, $id)
    {
        if (!is_array($param)) {
            return true;
        }
        $spec_data = [];
        $time = Carbon::now()->toDateTimeString();

        foreach ($param as $spec_v) {
            $spec_data[] = ['type_id' => $id, 'sp_id' => $spec_v, 'updated_at' => $time, 'created_at' => $time];
        }
        return GoodsTypeSpec::insert($spec_data);
    }


    /**
     * 编辑属性值
     * @Author hfh_wind
     */
    public function attrShow($id)
    {

        if ($id <= 0) {
            return $this->resFailed(414, "请传入属性id!");
        }

        $attr_info = GoodsAttribute::where(array('id' => $id))->first();
        $detail['attr_info'] = $attr_info;
        // 属性
        $attr_list = GoodsAttributeValue::where(['attr_id' => $id])->get();

        $detail['attr_list'] = $attr_list;

        return $this->resSuccess($detail);
    }


    /**
     *  编辑属性值
     * @Author hfh_wind
     * @param GoodsSpecValuesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function attrEdit(GoodsSpecValuesRequest $request)
    {

        $postDate = $request->only('attr_name', 'attr_id', 'type_id', 'attr_sort', 'attr_value', 'attr_show',
            'attr_value_del');

        if (isset($postDate['attr_value'])) {
            $postDate['attr_value'] = is_array($postDate['attr_value']) ? $postDate['attr_value'] : json_decode($postDate['attr_value'],
                true);
        }

        if (isset($postDate['attr_value_del'])) {
            $postDate['attr_value_del'] = is_array($postDate['attr_value_del']) ? $postDate['attr_value_del'] : json_decode($postDate['attr_value_del'],
                true);
        }

        //更新属性值表
        $attr_value = $postDate['attr_value'];
        // 要删除的规格值id
        $del_array = array();
        if (!empty($postDate['attr_value_del'])) {
            $del_array = $postDate['attr_value_del'];
        }

        DB::beginTransaction();
        try {
            if (!empty($attr_value) && is_array($attr_value)) {
                foreach ($attr_value as $key => $val) {
                    if ($val['attr_value_name'] == '') {
                        continue;
                    }
                    if (isset($val['id']) && !in_array(intval($val['id']), $del_array)) {       // 属性已修改

                        $update = array();
                        $update['attr_value_name'] = $val['attr_value_name'];
                        $update['attr_value_sort'] = intval($val['attr_value_sort']);
                        if (isset($val['set_search']) && $val['set_search'] == 'true') {
                            $update['set_search'] = 1;
                        } else {
                            $update['set_search'] = 0;
                        }
                        GoodsAttributeValue::where(['id' => $val['id']])->update($update);
//                    $model_attribute->editAttributeValue($update, $where);
                    } else {
                        $insert = array();
                        $insert['attr_value_name'] = $val['attr_value_name'];
                        $insert['attr_id'] = intval($postDate['attr_id']);
                        $insert['type_id'] = intval($postDate['type_id']);
                        $insert['attr_value_sort'] = intval($val['attr_value_sort']);
                        if (isset($val['set_search']) && $val['set_search'] == 'true') {
                            $insert['set_search'] = 1;
                        } else {
                            $insert['set_search'] = 0;
                        }
                        $insert['gm_id'] = $this->GMID;
                        GoodsAttributeValue::create($insert);
//                        $model_attribute->addAttributeValue($insert);
                    }
                }
                // 删除属性值
                if ($del_array) {
                    GoodsAttributeValue::whereIn('id', $del_array)->delete();
                }
            }

            $attr_value_list = GoodsAttributeValue::where(['attr_id' => $postDate['attr_id']])->get()->toArray();

            $attr_value_name = array_column($attr_value_list, 'attr_value_name');
            $attr_value_name = implode(',', $attr_value_name);
//            dd($attr_value_name);
            /**
             * 更新属性
             */
            $data = array();
            $data['attr_name'] = $postDate['attr_name'];
            $data['attr_value'] = $attr_value_name;
            $data['attr_show'] = isset($postDate['attr_show']) ? intval($postDate['attr_show']) : 0;
            $data['attr_sort'] = isset($postDate['attr_sort']) ? intval($postDate['attr_sort']) : 0;
            GoodsAttribute::where(['id' => intval($postDate['attr_id'])])->update($data);

        } catch (\Exception $e) {
            DB::rollback();
            return $this->resFailed(701, $e->getMessage());
        }
        DB::commit();
        return $this->resSuccess();
    }


}