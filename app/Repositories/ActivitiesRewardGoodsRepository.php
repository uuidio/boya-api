<?php
/**
 * @Filename ActivitiesRewardGoodsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;


use ShopEM\Models\ActivitiesRewardGoods;

class ActivitiesRewardGoodsRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id'            => ['field' => 'id', 'operator' => '='],
        'goods_name'    => ['field' => 'goods_name', 'operator' => 'like'],
        'is_use'        => ['field' => 'is_use', 'operator' => '='],
        'gm_id'        => ['field' => 'gm_id', 'operator' => '='],
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
            ['dataIndex' => 'id', 'title' => '活动id'],
            ['dataIndex' => 'goods_name', 'title' => '商品名称'],
            ['dataIndex' => 'goods_serial', 'title' => '货号'],
            ['dataIndex' => 'goods_barcode', 'title' => '条形码'],
            ['dataIndex' => 'goods_image', 'title' => '商品图片'],
            ['dataIndex' => 'is_use_text', 'title' => '是否启用'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author hfh_wind
     * @return array
     *
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 订单查询
     *
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $model = new ActivitiesRewardGoods();
        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }

}