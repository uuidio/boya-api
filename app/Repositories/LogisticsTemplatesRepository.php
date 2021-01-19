<?php
/**
 * @Filename LogisticsTemplatesRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;
use ShopEM\Models\LogisticsTemplate;

class LogisticsTemplatesRepository
{

    /*
    * 定义搜索过滤字段
    */
    protected $filterables = [
        'id' => ['field' => 'id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'name' => ['field' => 'name', 'operator' => '='],
        'valuation' => ['field' => 'valuation', 'operator' => '='],
        'status' => ['field' => 'status', 'operator' => '='],
    ];



    /**
     * 查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => '模板id'],
//            ['dataIndex' => 'shop_id', 'title' => '店铺id'],
            ['dataIndex' => 'name', 'title' => '模板名称'],
            ['dataIndex' => 'is_free_text', 'title' => '是否包邮'],
            ['dataIndex' => 'valuation_text', 'title' => '运费计算类型'],
            ['dataIndex' => 'status_text', 'title' => '状态'],
        ];
    }

    /**
     * 商家后台表格列表显示字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }



    /**
     * 商家物料模板查询
     *
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function search($request)
    {

        $model = new LogisticsTemplate();
        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }

}