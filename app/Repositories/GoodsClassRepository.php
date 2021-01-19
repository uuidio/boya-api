<?php
/**
 * @Filename GoodsClassRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\Cache;
use ShopEM\Models\GoodsClass;

class GoodsClassRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'class_level' => ['field' => 'class_level', 'operator' => '='],
        'parent_id' => ['field' => 'parent_id', 'operator' => '='],
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'is_show' => ['field' => 'is_show', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields($is_show='')
    {
        return [
            ['key' => 'gc_name','dataIndex' => 'gc_name', 'title' => '分类名称'],
            ['key' => 'id','dataIndex' => 'id', 'title' => 'ID'],
            // ['key' => 'gm_name','dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group'])],
            ['key' => 'parent_id','dataIndex' => 'parent_id', 'title' => '父ID'],
            ['key' => 'type_id','dataIndex' => 'parent_id', 'title' => '类型ID'],
            ['key' => 'type_name','dataIndex' => 'parent_id', 'title' => '类型名称'],
            ['key' => 'class_icon','dataIndex' => 'class_icon', 'title' => '分类图标'],
            ['key' => 'class_level','dataIndex' => 'class_level', 'title' => '分类层级'],//添加上分类层级
            ['key' => 'is_show_text','dataIndex' => 'is_show_text', 'title' => '是否显示'],
            ['key' => 'listorder','dataIndex' => 'listorder', 'title' => '排序'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields($is_show='')
    {
        return listFieldToShow($this->listFields($is_show));
    }

    /**
     * 获取列表数据
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function listItems($request = [])
    {
        /*if ($request) {
            $goodsClassModel = new GoodsClass();
            $goodsClassModel = filterModel($goodsClassModel, $this->filterables, $request);
            $lists = $goodsClassModel->select(listFieldToSelect($this->listShowFields()))->get();
            return $lists;
        }
        return Cache::remember('all_goods_class_tree', cacheExpires(), function() {
            return GoodsClass::select(listFieldToSelect($this->listShowFields()))->get();
        });*/

        $goodsClassModel = new GoodsClass();
        if ($request) {
            $goodsClassModel = filterModel($goodsClassModel, $this->filterables, $request);
        }
        $lists = $goodsClassModel->orderBy('listorder','asc')->get();
        return $lists;
    }
}