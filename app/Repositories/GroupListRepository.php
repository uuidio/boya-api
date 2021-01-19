<?php
/**
 * @Filename GroupListRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\Group;
use ShopEM\Models\GroupsMains;

class GroupListRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'gm_id'          => ['field' => 'gm_id', 'operator' => '='],
        'id'             => ['field' => 'id', 'operator' => '='],
//        'group_name'  => ['field' => 'group_name', 'operator' => '='],
        'goods_name'     => ['field' => 'goods_name', 'operator' => '='],
        'shop_id'        => ['field' => 'shop_id', 'operator' => '='],
        'goods_id'       => ['field' => 'goods_id', 'operator' => '='],
        'sku_id'         => ['field' => 'sku_id', 'operator' => '='],
        'gc_id_3'        => ['field' => 'gc_id_3', 'operator' => '='],
        'is_show'        => ['field' => 'is_show', 'operator' => '='],
        'start_time'     => ['field' => 'start_time', 'operator' => '='],
        'start_time_at'  => ['field' => 'start_time', 'operator' => '<='],
        'end_time'       => ['field' => 'end_time', 'operator' => '='],
        'end_time_at'    => ['field' => 'end_time', 'operator' => '>='],
        'created_at'     => ['field' => 'created_at', 'operator' => '='],
        'group_stock_gt' => ['field' => 'group_stock', 'operator' => '>'],
    ];

    /**
     * 查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => '活动id'],
//            ['dataIndex' => 'group_name', 'title' => '拼团促销名称'],
            ['dataIndex' => 'group_size', 'title' => '拼团总人数'],
            ['dataIndex' => 'group_validhours', 'title' => '拼团有效期'],
//            ['dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['dataIndex' => 'goods_name', 'title' => '商品名称'],
//            ['dataIndex' => 'sku_info', 'title' => 'SKU信息'],
            ['dataIndex' => 'price', 'title' => '商品原价'],
            ['dataIndex' => 'group_price', 'title' => '拼团价格'],
//            ['dataIndex' => 'group_stock', 'title' => '拼团库存'],
            ['dataIndex' => 'goods_image', 'title' => '商品图片'],
            ['dataIndex' => 'start_time', 'title' => '团购时间'],
            ['dataIndex' => 'end_time', 'title' => '团购结束时间'],
            ['dataIndex' => 'group_desc', 'title' => '拼团描述'],
            ['dataIndex' => 'sort', 'title' => '排序'],
            ['dataIndex' => 'is_show', 'title' => '是否显示'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author hfh_wind
     * @return array
     *
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 订单查询
     *
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $model = new GroupsMains();

        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }


}
