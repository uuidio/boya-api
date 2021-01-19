<?php
/**
 * @Filename        HousingRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use ShopEM\Models\Housing;

class HousingRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'housing_name' => ['field' => 'housing_name', 'operator' => 'like'],
        'province_id'  => ['field' => 'province_id', 'operator' => '='],
        'city_id'      => ['field' => 'city_id', 'operator' => '='],
        'area_id'      => ['field' => 'area_id', 'operator' => '='],
    ];

    /**
     * 小区列表字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function housingField()
    {
        return [
            ['key' => 'id', 'title' => 'ID', 'minWidth' => 60],
            ['key' => 'housing_name', 'title' => '小区名称', 'minWidth' => 100],
            ['key' => 'province_id', 'title' => '省份ID', 'minWidth' => 100],
            ['key' => 'city_id', 'title' => '城市ID', 'minWidth' => 100],
            ['key' => 'area_id', 'title' => '区县ID', 'minWidth' => 100],
            ['key' => 'street_id', 'title' => '街道ID', 'minWidth' => 100],
            ['key' => 'province_name', 'title' => '省份', 'minWidth' => 100],
            ['key' => 'city_name', 'title' => '城市', 'minWidth' => 100],
            ['key' => 'area_name', 'title' => '区县', 'minWidth' => 100],
            ['key' => 'street_name', 'title' => '街道', 'minWidth' => 100],
            ['key' => 'address', 'title' => '详细地区', 'minWidth' => 100],
            ['key' => 'zip_code', 'title' => '邮政编码', 'minWidth' => 100],
            ['key' => 'lng', 'title' => '经度', 'minWidth' => 100],
            ['key' => 'lat', 'title' => '纬度', 'minWidth' => 100],
        ];
    }

    /**
     * 列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function housingListField()
    {
        return listFieldToShow($this->housingField());
    }

    /**
     * 小区列表
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function housingItems()
    {
        return Housing::select(listFieldToSelect($this->housingField()))->paginate(config('app.per_page'));
    }

    /**
     * 搜索所有小区
     *
     * @Author moocde <mo@mocode.cn>
     * @param $request
     * @return mixed
     */
    public function seachAll($request)
    {
        $housingModel = new Housing();
        $housingModel = filterModel($housingModel, $this->filterables, $request);

        $lists = $housingModel->orderBy('id', 'desc')->get();

        return $lists;
    }
}