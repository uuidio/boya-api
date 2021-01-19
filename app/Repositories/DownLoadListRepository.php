<?php
/**
 * @Filename DownLoadListRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author         hfh
 */

namespace ShopEM\Repositories;


use ShopEM\Models\DownloadLog;

class DownLoadListRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'id'         => ['field' => 'id', 'operator' => '='],
        'type'      => ['field' => 'type', 'operator' => '='],
        'status'      => ['field' => 'status', 'operator' => '='],
        'shop_id'      => ['field' => 'shop_id', 'operator' => '='],
        'gm_id'      => ['field' => 'gm_id', 'operator' => '='],
    ];

    /**
     * 查询字段
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
//            ['dataIndex' => 'desc', 'title' => '备注'],
//            ['dataIndex' => 'url', 'title' => '下载地址'],
            ['dataIndex' => 'status_text', 'title' => '状态'],
            ['dataIndex' => 'type_text', 'title' => '类型'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     * @Author hfh_wind
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 获取列表
     * @Author hfh_wind
     * @param $request
     * @param int $page_count
     * @return mixed
     */
    public function search($request)
    {
        $page_count = isset($request['per_page']) ? $request['per_page'] : config('app.per_page');
        $couponModel = new DownloadLog();

        $couponModel = filterModel($couponModel, $this->filterables, $request);

        $lists = $couponModel->orderBy('id', 'desc')->paginate($page_count);

        return $lists;
    }
}