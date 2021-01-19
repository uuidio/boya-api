<?php
/**
 * @Filename SecKillGoodRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use ShopEM\Models\SecKillGood;

class SecKillGoodRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'gm_id'         => ['field' => 'gm_id', 'operator' => '='],
        'id'         => ['field' => 'id', 'operator' => '='],
        'goods_name'  => ['field' => 'goods_name', 'operator' => '='],
        'shop_id'    => ['field' => 'shop_id', 'operator' => '='],
        'goods_id'   => ['field' => 'goods_id', 'operator' => '='],
        'seckill_ap_id'   => ['field' => 'seckill_ap_id', 'operator' => '='],
        'sku_id'     => ['field' => 'sku_id', 'operator' => '='],
        'verify_status' => ['field' => 'verify_status', 'operator' => '='],
        'show_act_status' => ['field' => 'verify_status', 'operator' => '<>'],
        'start_time' => ['field' => 'start_time', 'operator' => '='],
        'end_time'   => ['field' => 'end_time', 'operator' => '='],
        'created_at' => ['field' => 'created_at', 'operator' => '='],
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
            ['dataIndex' => 'title', 'title' => '秒杀商品名称'],
            ['dataIndex' => 'goods_name', 'title' => '商品名称'],
            ['dataIndex' => 'shop_id', 'title' => '店铺ID'],
            ['dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['dataIndex' => 'verify_status_text', 'title' => '审核状态'],
            ['dataIndex' => 'goods_image', 'title' => '商品图片'],
            ['dataIndex' => 'goods_price', 'title' => '店铺价格'],
            ['dataIndex' => 'seckill_price', 'title' => '秒杀价格'],
            ['dataIndex' => 'seckills_stock', 'title' => '秒杀库存'],
            ['dataIndex' => 'start_time', 'title' => '秒杀开始时间'],
            ['dataIndex' => 'end_time', 'title' => '秒杀结束时间'],
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
        $model = new SecKillGood();
        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }

}