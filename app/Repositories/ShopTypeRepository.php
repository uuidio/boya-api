<?php
/**
 * @Filename ShopTypeRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author         hfh
 */

namespace ShopEM\Repositories;

use ShopEM\Models\ShopType;

class ShopTypeRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'id' => ['field' => 'id', 'operator' => 'id'],
        'name' => ['field' => 'name', 'operator' => 'like'],
        'shop_type' => ['field' => 'shop_type', 'operator' => '='],
        'status' => ['field' => 'status', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author hfh
     * @return array
     */
    public function listFields()
    {
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'name', 'dataIndex' => 'name', 'title' => '类型名称'],
            ['key' => 'shop_type', 'dataIndex' => 'shop_type', 'title' => '类型标识'],
            ['key' => 'brief', 'dataIndex' => 'brief', 'title' => '类型描述'],
            ['key' => 'suffix', 'dataIndex' => 'suffix', 'title' => '店铺名称后缀'],
            ['key' => 'type_status', 'dataIndex' => 'type_status', 'title' => '状态'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author hfh
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
    public function listItems()
    {
        return Shop::select()->paginate(config('app.per_page'));
    }

    /**
     * 店铺信息
     *
     * @Author moocde <mo@mocode.cn>
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        return Shop::find($id);
    }

    /**
     * 搜索店铺
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return mixed
     */
    public function search($request)
    {
        $Model = new ShopType();
        $Model = filterModel($Model, $this->filterables, $request);

        $lists = $Model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }
}