<?php
/**
 * @Filename        CustomActivityConfigRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use ShopEM\Models\CustomActivityConfig;

class CustomActivityConfigRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'title' => ['field' => 'class_level', 'operator' => 'like'],
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
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
            ['key' => 'id','dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'title','dataIndex' => 'title', 'title' => '自定义活动标题'],
            ['key' => 'updated_at','dataIndex' => 'updated_at', 'title' => '更新时间'],
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
     * 获取列表数据
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function listItems($request = [], $page_count = 0)
    {
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;
        $model = new CustomActivityConfig();
        $model = filterModel($model, $this->filterables, $request);
        
        $lists = $model->orderBy('id', 'desc')->paginate($page_count);

        return $lists;
    }
}