<?php
/**
 * @Filename        LogisticsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use ShopEM\Models\LogisticsDlycorp;

class LogisticsRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'id'     => ['field' => 'id', 'operator' => '='],
        'full_name' => ['field' => 'full_name', 'operator' => 'like'],
        'corp_name' => ['field' => 'corp_name', 'operator' => 'like'],
        'corp_code' => ['field' => 'corp_code', 'operator' => '='],
    ];



    /**
     * 查询字段
     *
     * @Author hfh
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'corp_code', 'title' => '物流公司代码'],
            ['dataIndex' => 'full_name', 'title' => '物流公司全名'],
            ['dataIndex' => 'corp_name', 'title' => '物流公司简称'],
            ['dataIndex' => 'website', 'title' => '物流公司网址'],
            ['dataIndex' => 'request_url', 'title' => '查询接口网址'],
            ['dataIndex' => 'order_sort', 'title' => '排序'],
            ['dataIndex' => 'is_show_text', 'title' => '是否显示'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],

        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author hfh
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 订单查询
     *
     * @Author moocde <mo@mocode.cn>
     * @param $request
     * @return mixed
     */
    public function search($request)
    {

        $model = new LogisticsDlycorp();
        $model = filterModel($model, $this->filterables, $request);
        $lists = $model->orderBy('is_show', 'desc')->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }
}