<?php
/**
 * @Filename ShopArticleRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Repositories;

use ShopEM\Models\ShopArticle;

class ShopArticleRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'cat_id' => ['field' => 'cat_id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'is_show' => ['field' => 'is_show', 'operator' => '='],
    ];
    /**
     * 文章列表字段
     *
     * @Author djw
     * @return array
     */
    public function aritcleListField()
    {
        return [
            ['key' => 'title', 'dataIndex' => 'title', 'title' => '标题'],
            ['key' => 'cat_name', 'dataIndex' => 'title', 'title' => '分类'],
            ['key' => 'cat_id', 'dataIndex' => 'title', 'title' => '分类id', 'hide' => true],
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
     * 文章查询
     *
     * @Author djw
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $model = new ShopArticle();
        $model = filterModel($model, $this->filterables, $request);
        $filter = listFieldToSelect($this->aritcleListField());
        foreach ($filter as $key => $value) {
            if ($value == 'cat_name') {
                unset($filter[$key]);
            }
        }
        $filter[] = 'cat_id';
        $lists = $model->select($filter)->orderBy('listorder', 'desc')->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }
}