<?php
/**
 * @Filename        GoodsHotkeywordsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Repositories;

use Illuminate\Http\Request;
use ShopEM\Models\GoodsHotkeywords;

class GoodsHotkeywordsRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'keyword' => ['field' => 'keyword', 'operator' => 'like'],
        'no_disabled' => ['field' => 'disabled', 'operator' => '!='],
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author djw
     * @return array
     */
    public function listFields()
    {
        //根据前端要求修改返回的数据格式
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'keyword', 'dataIndex' => 'keyword', 'title' => '关键字'],
            ['key' => 'listorder', 'dataIndex' => 'listorder', 'title' => '排序'],
            ['key' => 'disabled', 'dataIndex' => 'disabled', 'title' => '是否有效'],
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
     * 获取关键字列表
     *
     * @Author djw
     * @param Request $request
     * @param int $page_count
     * @return mixed
     */
    public function listItems($request, $page_count = 0)
    {
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;
        $goodsHotkeywordsModel = new GoodsHotkeywords();
        $goodsHotkeywordsModel = filterModel($goodsHotkeywordsModel, $this->filterables, $request);
        $lists = $goodsHotkeywordsModel->orderBy('listorder', 'desc')->orderBy('id', 'desc')->paginate($page_count);
        return $lists;
    }

}