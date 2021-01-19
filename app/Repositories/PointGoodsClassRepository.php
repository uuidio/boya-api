<?php
/**
 * @Filename PointGoodsClassRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author         swl 
 */

namespace ShopEM\Repositories;
use ShopEM\Models\PointGoodsClass;


class PointGoodsClassRepository
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
            // ['key' => 'parent_id', 'dataIndex' => 'parent_id','title' => '分类父级ID'],
            //['key' => 'class_icon', 'dataIndex' => 'class_icon','title' => '分类图标'],
            ['key' => 'cat_name', 'dataIndex' => 'cat_name','title' => '分类名称'],
            ['key' => 'order','dataIndex' => 'order', 'title' => '排序'],
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
    public function listItems($data)
    {   
        $model = PointGoodsClass::select(listFieldToSelect($this->listShowFields()));
         if (!empty($data['gm_id'])) 
        {
            $model->where('gm_id',$data['gm_id']);
        }
        return $model->orderBy('order','asc')->paginate($data['per_page']);
    }

    /**
     * [allListItems 获取全部]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function allListItems($data)
    {   
        $model = PointGoodsClass::select(listFieldToSelect($this->listShowFields()));
         if (!empty($data['gm_id'])) 
        {
            $model->where('gm_id',$data['gm_id']);
        }
        return $model->orderBy('order','asc')->get();
    }
    
}