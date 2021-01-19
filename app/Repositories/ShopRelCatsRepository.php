<?php
/**
 * @Filename ShopRelCatsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author         hfh
 */

namespace ShopEM\Repositories;

use ShopEM\Models\ShopRelCat;
//use Illuminate\Support\Facades\Cache;


class ShopRelCatsRepository
{
    /**
     * 查询数据库字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        //根据前端要求修改返回的数据格式
        return [
            ['key' => 'id','dataIndex' => 'id', 'title' => '分类ID'],
            //['key' => 'shop_id', 'dataIndex' => 'shop_id','title' => '店铺ID'],
            ['key' => 'parent_id', 'dataIndex' => 'parent_id','title' => '分类父级ID'],
            ['key' => 'level', 'dataIndex' => 'level','title' => '分类级别'],
            //['key' => 'class_icon', 'dataIndex' => 'class_icon','title' => '分类图标'],
            ['key' => 'cat_name', 'dataIndex' => 'cat_name','title' => '分类名称'],
            ['key' => 'order','dataIndex' => 'order', 'title' => '排序'],
            //['key' => 'is_leaf', 'dataIndex' => 'is_leaf','title' => '是否叶子节点'],
//            ['key' => 'count_shop', 'dataIndex' => 'count_shop','title' => '分类下店铺数量'],
            ['key' => 'is_show', 'dataIndex' => 'is_show','title' => '是否显示'],
            ['key' => 'created_at', 'dataIndex' => 'created_at','title' => '创建时间'],
            ['key' => 'updated_at', 'dataIndex' => 'updated_at','title' => '更新时间'],
        ];
    }


    /**
     * 商城店铺分类显示字段
     * @Author hfh_wind
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 获取列表数据
     * @Author hfh_wind
     * @return mixed
     */
    public function listItems($gm_id=0)
    {
        $model = ShopRelCat::select(listFieldToSelect($this->listShowFields()));
        if ($gm_id > 0) 
        {
            $model->where('gm_id',$gm_id);
        }
        //return Cache::remember('all_shop_cats_tree', cacheExpires(), function() {
            return $model->get();
        //});
    }




}