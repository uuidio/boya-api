<?php

/**
 * @Author: nlx
 * @Date:   2020-04-20 16:47:38
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-05-19 14:19:12
 */
namespace ShopEM\Repositories;

use Carbon\Carbon;
use ShopEM\Models\CrmMasterStore;

class CrmStoreRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'storeID' => ['field' => 'storeID', 'operator' => '='],
        'storeCode'   => ['field' => 'storeCode', 'operator' => '='],
        'storeName' => ['field' => 'storeName', 'operator' => 'like'],
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
            ['dataIndex' => 'id', 'title' => 'id'],
            ['dataIndex' => 'storeCode', 'title' => '店铺编码'],
            ['dataIndex' => 'storeName', 'title' => '店铺名称'],
            ['dataIndex' => 'storeID', 'title' => '店铺id'],
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
        $model = new CrmMasterStore();
        $model = filterModel($model, $this->filterables, $request);
        $lists = $model->orderBy('id', 'desc')->paginate($page_count);

        return $lists;
    }	
}