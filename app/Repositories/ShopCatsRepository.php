<?php
/**
 * @Filename ShopCatsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use ShopEM\Models\ShopCats;
//use Illuminate\Support\Facades\Cache;


class ShopCatsRepository
{
    /**
     * 查询数据库字段
     *
     * @Author moocde <mo@mocode.cn>
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
            ['key' => 'order_sort','dataIndex' => 'order_sort', 'title' => '排序'],
            //['key' => 'is_leaf', 'dataIndex' => 'is_leaf','title' => '是否叶子节点'],
            ['key' => 'disabled', 'dataIndex' => 'disabled','title' => '是否屏蔽'],
            ['key' => 'created_at', 'dataIndex' => 'created_at','title' => '创建时间'],
            ['key' => 'updated_at', 'dataIndex' => 'updated_at','title' => '更新时间'],
        ];
    }



    /**
     * 店铺分类显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $shop_id
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
    public function listItems($shopId)
    {
        //return Cache::remember('all_shop_cats_tree', cacheExpires(), function() {
            return ShopCats::select(listFieldToSelect($this->listShowFields()))->where('shop_id',$shopId)->get();
        //});
    }




}