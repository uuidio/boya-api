<?php
/**
 * @Filename ArticleClassRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\Cache;
use ShopEM\Models\ArticleClass;

class ArticleClassRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'parent_id' => ['field' => 'parent_id', 'operator' => '='],
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
            ['key' => 'cat_node', 'dataIndex' => 'cat_node', 'title' => '分类节点'],
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
    public function listItems()
    {
        return Cache::remember('all_article_class_tree', cacheExpires(), function() {
            return ArticleClass::select(listFieldToSelect($this->listShowFields()))->get();
        });
    }
}