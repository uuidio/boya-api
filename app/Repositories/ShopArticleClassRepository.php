<?php
/**
 * @Filename ShopArticleClassRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\Cache;
use ShopEM\Models\ShopArticleClass;

class ShopArticleClassRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'parent_id' => ['field' => 'parent_id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
    ];

    /**
     * 文章列表字段
     *
     * @Author djw
     * @return array
     */
    public function listFields()
    {
        return [
            ['key' => 'id','dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'name', 'dataIndex' => 'name', 'title' => '分类名称'],
            ['key' => 'parent_id', 'dataIndex' => 'parent_id', 'title' => '父ID'],
            ['key' => 'listorder', 'dataIndex' => 'listorder', 'title' => '列表顺序'],
            ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '发布时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author djw
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 获取列表数据
     *
     * @Author djw
     * @return mixed
     */
    public function listItems($request)
    {
            $model = new ShopArticleClass();
            $model = filterModel($model, $this->filterables, $request);
            return $model->select(listFieldToSelect($this->listShowFields()))->get();
    }
}