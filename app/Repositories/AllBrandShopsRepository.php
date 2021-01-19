<?php
/**
 * @Filename AllBrandShopsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author         hfh
 */

namespace ShopEM\Repositories;

use ShopEM\Models\ShopRelBrand;

class AllBrandShopsRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'id' => ['field' => 'shop_rel_brands.id', 'operator' => '='],
        'brand_id' => ['field' => 'shop_rel_brands.brand_id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_rel_brands.shop_id', 'operator' => '='],
        'shop_name' => ['field' => 'shops.shop_name', 'operator' => 'like'],
        'shop_type' => ['field' => 'shops.shop_type', 'operator' => '='],
        'status' => ['field' => 'shops.status', 'operator' => '='],
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
     * @Author hfh
     * @return mixed
     */
    public function listItems()
    {
        return Shop::select()->paginate(config('app.per_page'));
    }


    /**
     * 搜索
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $allBrandShops = new  ShopRelBrand();
        $allBrandShops->leftJoin('shops','shops.id', '=', 'shop_rel_brands.shop_id')->where('shops.status','=','successful')->select('shop_rel_brands.*','shops.shop_name');

        $lists = filterModel($allBrandShops, $this->filterables, $request)->orderBy('shop_rel_brands.shop_id', 'desc')->get();


        return $lists;
    }
}