<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-07-11 11:14:04
 * @version 	V1.0
 */


namespace ShopEM\Repositories;

use Carbon\Carbon;
use ShopEM\Models\ShopShip;

class ShopShipRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'name' => ['field' => 'name', 'operator' => '='],
        'status' => ['field' => 'status', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'name', 'title' => '名称'],
            ['dataIndex' => 'content', 'title' => '内容'],
            ['dataIndex' => 'is_default', 'title' => '是否默认'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],
            ['dataIndex' => 'updated_at', 'title' => '更新时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 获取列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @param int $page_count
     * @return mixed
     */
    public function listItems($request, $page_count = 0)
    {
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;
        $model = new ShopShip();
        $model = filterModel($model, $this->filterables, $request);
        $lists = $model->orderBy('id', 'desc')->paginate($page_count);

        return $lists;
    }
}