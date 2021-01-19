<?php
/**
 * @Filename        TradeMonthRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\TradeMonthSettleAccount;

class TradeMonthRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'status' => ['field' => 'status', 'operator' => '='],
        'settle_time_start' => ['field' => 'settle_time', 'operator' => '>='],
        'settle_time_end' => ['field' => 'settle_time', 'operator' => '<='],
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
            ['dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['dataIndex' => 'tradecount', 'title' => '订单数量'],
            ['dataIndex' => 'goods_price_amount', 'title' => '商品总金额'],
            ['dataIndex' => 'coupon_shop_fee_amount', 'title' => '店铺优惠券金额'],
            ['dataIndex' => 'promotion_fee_amount', 'title' => '促销金额'],
            ['dataIndex' => 'shop_act_fee_amount', 'title' => '店铺优惠'],
            ['dataIndex' => 'platform_act_fee_amount', 'title' => '平台优惠券金额'],
            ['dataIndex' => 'total_discount_fee_amount', 'title' => '优惠总金额'],
            ['dataIndex' => 'points_fee_amount', 'title' => '积分抵扣金额'],
            ['dataIndex' => 'points_amount', 'title' => '使用积分'],
            ['dataIndex' => 'post_fee_amount', 'title' => '邮费'],
//            ['dataIndex' => 'payed_amount', 'title' => '实付'],
            //['dataIndex' => 'shop_rate', 'title' => '店铺扣点比例'],
            ['dataIndex' => 'shop_rate_fee_amount', 'title' => '店铺扣点金额'],
            ['dataIndex' => 'refund_fee_amount_text', 'title' => '退款金额'],
            ['dataIndex' => 'manage_fee', 'title' => '固定管理费'],
            //['dataIndex' => 'point_rate', 'title' => '积分比例'],
            ['dataIndex' => 'settlement_fee_amount', 'title' => '结算金额'],
            ['dataIndex' => 'payed_amount', 'title' => '实付金额汇总'],
            ['dataIndex' => 'status_text', 'title' => '结算状态'],
            ['dataIndex' => 'settle_time', 'title' => '账单结算时间'],
            ['dataIndex' => 'average_fee_amount', 'title' => '客单价'],
            ['dataIndex' => 'profit_amount', 'title' => '利润额汇总'],

        ];
    }

     /**
     * 查询字段 (针对集团)
     *
     * @Author swl 2020-3-12
     * @return array
     */
    public function groupListFields()
    {
        //根据前端要求修改返回的数据格式
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'gm_name', 'title' => '项目名称'],
            ['dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['dataIndex' => 'tradecount', 'title' => '订单数量'],
            ['dataIndex' => 'goods_price_amount', 'title' => '商品总金额'],
            ['dataIndex' => 'coupon_shop_fee_amount', 'title' => '店铺优惠券金额'],
            ['dataIndex' => 'promotion_fee_amount', 'title' => '促销金额'],
            ['dataIndex' => 'shop_act_fee_amount', 'title' => '店铺优惠'],
            ['dataIndex' => 'platform_act_fee_amount', 'title' => '平台优惠券金额'],
            ['dataIndex' => 'total_discount_fee_amount', 'title' => '优惠总金额'],
            ['dataIndex' => 'points_fee_amount', 'title' => '积分抵扣金额'],
            ['dataIndex' => 'points_amount', 'title' => '使用积分'],
            ['dataIndex' => 'post_fee_amount', 'title' => '邮费'],
//            ['dataIndex' => 'payed_amount', 'title' => '实付'],
            //['dataIndex' => 'shop_rate', 'title' => '店铺扣点比例'],
            ['dataIndex' => 'shop_rate_fee_amount', 'title' => '店铺扣点金额'],
            ['dataIndex' => 'refund_fee_amount', 'title' => '退款金额'],
            ['dataIndex' => 'manage_fee', 'title' => '固定管理费'],
            //['dataIndex' => 'point_rate', 'title' => '积分比例'],
            ['dataIndex' => 'settlement_fee_amount', 'title' => '结算金额'],
            ['dataIndex' => 'payed_amount', 'title' => '实付金额汇总'],
            ['dataIndex' => 'status_text', 'title' => '结算状态'],
            ['dataIndex' => 'settle_time', 'title' => '账单结算时间'],
            ['dataIndex' => 'average_fee_amount', 'title' => '客单价'],
            ['dataIndex' => 'profit_amount', 'title' => '利润额汇总'],
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
    public function search($request,$downData='')
    {
        $sum_result = [];
        $sum_data = ['coupon_shop_fee_amount','promotion_fee_amount','shop_act_fee_amount','platform_act_fee_amount','total_discount_fee_amount','points_fee_amount','points_amount','post_fee_amount','shop_rate_fee_amount','refund_fee_amount','settlement_fee_amount','payed_amount','average_fee_amount','profit_amount'];

        $Model = new TradeMonthSettleAccount();
        $Model = filterModel($Model, $this->filterables, $request);

        if($downData){
            //下载提供数据
            $return=$Model->orderBy('id', 'desc')->get();
        }else{
            $search_model = $Model->get();

            $return=$Model->orderBy('id', 'desc')->paginate($request['per_page']);

            if (isset($request['total_data_status']) && $request['total_data_status'])
            {
                $sum_result =$this->totalData($search_model,$sum_data);
                $return['total_fee_data'] =  [
                    ['value' => $sum_result['coupon_shop_fee_amount'], 'dataIndex' => 'coupon_shop_fee_amount', 'title' => '店铺优惠券金额汇总'],
                    ['value' => $sum_result['promotion_fee_amount'], 'dataIndex' => 'promotion_fee_amount', 'title' => '满减/满折金额汇总'],
                    ['value' => $sum_result['shop_act_fee_amount'], 'dataIndex' => 'shop_act_fee_amount', 'title' => '店铺优惠汇总'],
                    ['value' => $sum_result['platform_act_fee_amount'], 'dataIndex' => 'platform_act_fee_amount', 'title' => '平台优惠券金额汇总'],
                    ['value' => $sum_result['total_discount_fee_amount'], 'dataIndex' => 'total_discount_fee_amount', 'title' => '优惠总金额汇总'],
                    ['value' => $sum_result['points_fee_amount'], 'dataIndex' => 'points_fee_amount', 'title' => '积分抵扣金额汇总'],
                    ['value' => $sum_result['points_amount'], 'dataIndex' => 'points_amount', 'title' => '使用积分汇总'],
                    ['value' => $sum_result['post_fee_amount'], 'dataIndex' => 'post_fee_amount', 'title' => '邮费汇总'],
                    ['value' => $sum_result['shop_rate_fee_amount'], 'dataIndex' => 'shop_rate_fee_amount', 'title' => '店铺扣点金额汇总'],
                    ['value' => $sum_result['refund_fee_amount'], 'dataIndex' => 'refund_fee_amount', 'title' => '退款金额汇总'],
                    ['value' => $sum_result['settlement_fee_amount'], 'dataIndex' => 'settlement_fee_amount', 'title' => '结算金额汇总'],
                    ['value' => $sum_result['payed_amount'], 'dataIndex' => 'payed_amount', 'title' => '实付金额汇总'],
                    ['value' => $sum_result['average_fee_amount'], 'dataIndex' => 'average_fee_amount', 'title' => '客单价汇总'],
                    ['value' => $sum_result['profit_amount'], 'dataIndex' => 'profit_amount', 'title' => '利润额汇总'],
                ];
            }

        }

        return $return;
    }


    /**
     * 统计方法
     *
     * @Author Huiho
     * @param $request
     * @return mixed
     */
    public function totalData($model,$sum_data)
    {
        $result = [];
        foreach ($sum_data as $key =>&$value)
        {
            $result[$value] = $model->sum($value);
            $result[$value] = round($result[$value],2);
        }

        return $result;
    }

}
