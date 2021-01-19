<?php
/**
 * @Filename RegionsClassRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author         hfh
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\Cache;
use ShopEM\Models\ShopRegion;

class RegionsClassRepository
{
    /**
     * 查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        return [
            ['key' => 'region_name','dataIndex' => 'region_name', 'title' => '地区名称'],
            ['key' => 'id','dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'parent_id','dataIndex' => 'parent_id', 'title' => '父ID'],
            ['key' => 'level','dataIndex' => 'level', 'title' => '分类层级'],//添加上分类层级
            ['key' => 'disabled','dataIndex' => 'disabled', 'title' => '是否显示'],
            ['key' => 'order_sort','dataIndex' => 'order_sort', 'title' => '排序'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 获取列表数据
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function listItems()
    {
        return Cache::remember('all_regions_tree', cacheExpires(), function() {
            return ShopRegion::select(listFieldToSelect($this->listShowFields()))->get();
        });
    }
}