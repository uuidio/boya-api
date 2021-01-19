<?php
/**
 * @Filename GoodsTypeRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\GoodsType;

class GoodsTypeRepository
{



    /*
    * 定义搜索过滤字段
    */
    protected $filterables = [
        'type_name' => ['field' => 'type_name', 'operator' => 'like'],
        'class_id' => ['field' => 'class_id', 'operator' => '='],
        'class_name'   => ['field' => 'class_name', 'operator' => '='],
        'created_at'   => ['field' => 'created_at', 'operator' => '='],
        'gm_id'   => ['field' => 'gm_id', 'operator' => '='],
    ];

    /**
     * 查询字段
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        //根据前端要求修改返回的数据格式
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'type_name', 'title' => '类型名称'],
            ['dataIndex' => 'class_id', 'title' => '所属分类id'],
            ['dataIndex' => 'class_name', 'title' => '所属分类名称'],
            ['dataIndex' => 'sp_sort', 'title' => '排序'],
            ['dataIndex' => 'created_at', 'title' => '发布时间'],
            ['dataIndex' => 'updated_at', 'title' => '更新时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
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
     * @param $data
     * @return mixed
     */
    public function listItems($data)
    {
        $goodsSkuModel = new GoodsType();
        $Model = filterModel($goodsSkuModel, $this->filterables, $data);
        return $Model->orderBy('id', 'desc')->paginate(config('app.per_page'));
    }



}