<?php
/**
 * @Filename ShopFloorsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\Cache;
use ShopEM\Models\ShopFloor;

class ShopFloorsRepository
{
    /*
   * 定义搜索过滤字段
   */
    protected $filterables = [
        'id'      => ['field' => 'id', 'operator' => '='],
        'name'    => ['field' => 'name', 'operator' => 'like'],
        'is_show' => ['field' => 'is_show', 'operator' => '='],
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
    ];

    /**
     *  查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'name', 'dataIndex' => 'name', 'title' => '楼层名称'],
            ['key' => 'shop_name', 'dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['key' => 'order', 'dataIndex' => 'order', 'title' => '排序'],
            ['key' => 'is_show', 'dataIndex' => 'is_show', 'title' => '是否显示'],
            ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '创建时间'],
        ];
    }

    /**
     *  后台表格列表显示字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }


    /**
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $shopModel = new ShopFloor();
        $shopModel = filterModel($shopModel, $this->filterables, $request);

        $lists = $shopModel->orderBy('id', 'desc')->paginate($request['per_page']);
        return $lists;
    }
}