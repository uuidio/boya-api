<?php
/**
 * @Filename        TradeDayDetailRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;


use ShopEM\Models\TradeSplit;

class TradeDayDetailGoodsRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'tid' => ['field' => 'tid', 'operator' => '='],
        'oid' => ['field' => 'oid', 'operator' => '='],
        'goods_id' => ['field' => 'goods_id', 'operator' => '='],
        'sku_id' => ['field' => 'sku_id', 'operator' => '='],
        'user_id' => ['field' => 'user_id', 'operator' => '='],
        'payment_id' => ['field' => 'payment_id', 'operator' => '='],
        'created_at' => ['field' => 'created_at', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author hfh
     * @return array
     */
    public function listFields()
    {
        //根据前端要求修改返回的数据格式
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'user_mobile', 'title' => '会员手机号','width'=>150],
            ['dataIndex' => 'goods_name', 'title' => '商品名称','width'=>150],
            ['dataIndex' => 'goods_id', 'title' => '商品id','width'=>150],
            ['dataIndex' => 'sku_info', 'title' => '商品sku','width'=>150],
            ['dataIndex' => 'sku_id', 'title' => '商品sku_id','width'=>150],
            ['dataIndex' => 'quantity', 'title' => '购买数量','width'=>150],
            ['dataIndex' => 'payment_id', 'title' => '支付单号','width'=>150],
            ['dataIndex' => 'tid', 'title' => '订单号','width'=>150],
            ['dataIndex' => 'oid', 'title' => '子订单单号','width'=>150],
            ['dataIndex' => 'coupon_shop_fee', 'title' => '店铺优惠券金额','width'=>150],
            ['dataIndex' => 'coupon_platform_fee', 'title' => '平台优惠券明细','width'=>150],
            ['dataIndex' => 'promotion_fee', 'title' => '促销金额','width'=>150],
            //['dataIndex' => 'points', 'title' => '积分','width'=>150],
            ['dataIndex' => 'points_fee', 'title' => '积分抵扣金额','width'=>150],
            ['dataIndex' => 'total_fee', 'title' => '商品总金额','width'=>150],
            ['dataIndex' => 'payed', 'title' => '拆分实付','width'=>150],
            ['dataIndex' => 'goods_cost', 'title' => '成本价','width'=>150],
            ['dataIndex' => 'profit', 'title' => '利润额','width'=>150],
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
     * @param int $shop_id
     * @return mixed
     */
    public function search($request)
    {
        $Model = new TradeSplit();
        $Model = filterModel($Model, $this->filterables, $request);

        return $Model->orderBy('id', 'desc')->paginate($request['per_page']);
    }

}