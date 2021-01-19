<?php
/**
 * @Filename        TradePolymorphicService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          Huiho
 */

namespace ShopEM\Services;

use Illuminate\Support\Facades\DB;

class TradePolymorphicService
{

    /**
     *  订单日结
     *
     * @Author Huiho
     * @param $param
     * @return string
     * @throws \Exception
     */
    public function tradeDay($param)
    {
        $sum_result = [];
        $search_result = false;

        $sum_data = ['sales_amount','payed_amount','average_fee_amount','refund_payed_amount','total_discount_fee','platform_act_fee_amount','coupon_shop_fee_amount','promotion_fee_amount','points_fee_amount','consume_point_fee_amount','post_fee_amount'];

        $tradeDayInfo = $this->getSaleTrades($param);

        if (empty($tradeDayInfo))
        {
            return [];
        }

        $result = [];
        //导出
        if(!empty($param['down']))
        {
            unset($tradeDayInfo['search_rows']);
            foreach ($tradeDayInfo as $key => &$value)
            {
                $output_data['data_at'] = $param['filter_time'];
                $shop_name = $this->getShopName($value->shop_id);
                $output_data['shop_name'] = $shop_name->shop_name ?? '--';
                //$output_data['sales_amount'] = $value->sales_amount;
                $output_data['sales_amount'] = $value->sales_amount == 0 ? 1 : $value->sales_amount;
                $output_data['payed_amount'] = $value->payed_amount;
                $output_data['average_fee_amount'] = bcdiv($output_data['payed_amount'] ,  $output_data['sales_amount'],2);
                $output_data['platform_act_fee_amount'] = $value->platform_act_fee_amount;
                $output_data['coupon_shop_fee_amount'] = $value->coupon_shop_fee_amount;
                $output_data['promotion_fee_amount'] = $value->promotion_fee_amount;
                $output_data['total_discount_fee'] = $value->promotion_fee_amount + $value->coupon_shop_fee_amount + $value->platform_act_fee_amount;
                $output_data['consume_point_fee_amount'] = $value->consume_point_fee_amount;

                //获取退款信息
                $search['time_start'] = $param['time_start'];
                $search['time_end'] = $param['time_end'];
                $search['shop_id'] = $value->shop_id;
                $refund_data = $this->getRefundTrades($search);
                foreach ($refund_data as $refund_key => &$refund_value)
                {
                    $output_data['refund_amount'] = $refund_value->refund_amount ?? '0.00';
                    $output_data['refund_payed_amount'] = $refund_value->refund_payed_amount ?? '0.00';
                }
                //追加积分抵扣金额（可优化）
                $points_fee_amount = $this->getPointsFeeAmount($search);
                $output_data['points_fee_amount'] = $points_fee_amount->points_fee_amount ?? '0.00';
                $result[] = $output_data;
            }
            $tradeDayInfo = $result;
        }

        else
        {
            //用于统计金额
            foreach ($tradeDayInfo['search_rows'] as $key => &$value)
            {
                $search_output_data['data_at'] = $param['filter_time'];
                $search_output_data['sales_amount'] = $value->sales_amount == 0 ? 1 : $value->sales_amount;
                $search_output_data['payed_amount'] = $value->payed_amount;
                $search_output_data['average_fee_amount'] = bcdiv($search_output_data['payed_amount'] ,  $search_output_data['sales_amount'],2);
                $search_output_data['consume_point_fee_amount'] = $value->consume_point_fee_amount ?? '0.00';

                //获取退款信息
                $search['time_start'] = $param['time_start'];
                $search['time_end'] = $param['time_end'];
                $search['shop_id'] = $value->shop_id;
                $refund_data = $this->getRefundTrades($search);
                foreach ($refund_data as $refund_key => &$refund_value)
                {
                    $search_output_data['refund_amount'] = $refund_value->refund_amount ?? '0.00';
                    $search_output_data['refund_payed_amount'] = $refund_value->refund_payed_amount ?? '0.00';
                }
                //追加积分抵扣金额（可优化）
                $points_fee_amount = $this->getPointsFeeAmount($search);
                $search_output_data['points_fee_amount'] = $points_fee_amount->points_fee_amount ?? '0.00';
                $search_result[] = $search_output_data;
            }

            if($search_result)
            {
                if (isset($param['total_data_status']) && $param['total_data_status'])
                {
                    $sum_result = $this->totalData($search_result , $sum_data);
                    $tradeDayInfo['total_fee_data'] =  [
                        ['value' => $sum_result['payed_amount'], 'dataIndex' => 'payed_amount', 'title' => '销售订单实付金额汇总'],
                        ['value' => $sum_result['average_fee_amount'], 'dataIndex' => 'average_fee_amount', 'title' => '客单价汇总'],
                        ['value' => $sum_result['refund_payed_amount'], 'dataIndex' => 'refund_payed_amount', 'title' => '退货订单实付金额汇总'],
                        ['value' => $sum_result['total_discount_fee'], 'dataIndex' => 'total_discount_fee', 'title' => '优惠总额汇总'],
                        ['value' => $sum_result['platform_act_fee_amount'], 'dataIndex' => 'platform_act_fee_amount', 'title' => '平台优惠券金额汇总'],
                        ['value' => $sum_result['coupon_shop_fee_amount'], 'dataIndex' => 'coupon_shop_fee_amount', 'title' => '商家优惠券金额汇总'],
                        ['value' => $sum_result['promotion_fee_amount'], 'dataIndex' => 'promotion_fee_amount', 'title' => '店铺满减满折金额汇总'],
                        ['value' => $sum_result['points_fee_amount'], 'dataIndex' => 'points_fee_amount', 'title' => '积分抵扣金额汇总'],
                        ['value' => $sum_result['consume_point_fee_amount'], 'dataIndex' => 'consume_point_fee_amount', 'title' => '消耗积分汇总'],
                        ['value' => $sum_result['post_fee_amount'], 'dataIndex' => 'post_fee_amount', 'title' => '邮费汇总'],

                    ];
                }
            }

            //用于分页展示数据
            foreach ($tradeDayInfo['data'] as $key => &$value)
            {
                $output_data['data_at'] = $param['filter_time'];
                $shop_name = $this->getShopName($value->shop_id);
                $output_data['shop_name'] = $shop_name->shop_name ?? '--';
                $output_data['sales_amount'] = $value->sales_amount;
                $output_data['payed_amount'] = $value->payed_amount;
                $output_data['average_fee_amount'] = bcdiv($output_data['payed_amount'] ,  $output_data['sales_amount'],2);
                $output_data['platform_act_fee_amount'] = $value->platform_act_fee_amount;
                $output_data['coupon_shop_fee_amount'] = $value->coupon_shop_fee_amount;
                $output_data['promotion_fee_amount'] = $value->promotion_fee_amount;
                $output_data['total_discount_fee'] = $value->promotion_fee_amount + $value->coupon_shop_fee_amount + $value->platform_act_fee_amount;
                $output_data['consume_point_fee_amount'] = $value->consume_point_fee_amount;

                //获取退款信息
                $search['time_start'] = $param['time_start'];
                $search['time_end'] = $param['time_end'];
                $search['shop_id'] = $value->shop_id;
                $refund_data = $this->getRefundTrades($search);
                foreach ($refund_data as $refund_key => &$refund_value)
                {
                    $output_data['refund_amount'] = $refund_value->refund_amount ?? '0.00';
                    $output_data['refund_payed_amount'] = $refund_value->refund_payed_amount ?? '0.00';
                }
                //追加积分抵扣金额（可优化）
                $points_fee_amount = $this->getPointsFeeAmount($search);
                $output_data['points_fee_amount'] = $points_fee_amount->points_fee_amount ?? '0.00';
                $result[] = $output_data;
            }
            $tradeDayInfo['data'] = $result;




        }

        return $tradeDayInfo;

    }

    public function getSaleTrades($param)
    {

        $search_rows = [];
        //已完成的的订单
        $rows = DB::table('trades as a')
            ->leftJoin('trade_splits as b', 'a.tid', '=', 'b.tid')
            ->where('a.pay_time', '>=', $param['time_start'])
            ->where('a.pay_time', '<=', $param['time_end'])
            //->where('a.status', '=', 'TRADE_FINISHED')
           // ->where('a.gm_id', '=', $param['gm_id'])
            ->select(DB::raw('count(*) as sales_amount,any_value(shop_id) as shop_id,sum(amount) as payed_amount,sum(coupon_shop_fee) as coupon_shop_fee_amount,sum(coupon_platform_fee) as platform_act_fee_amount,sum(promotion_fee) as promotion_fee_amount,sum(consume_point_fee) as consume_point_fee_amount,sum(post_fee) as post_fee_amount'))
            ->orderBy('a.created_at', 'desc')
            ->groupBy('shop_id');

        if (isset($param['shop_id'])&& !empty($param['shop_id'])) {
            $rows = $rows->where('shop_id', '=', $param['shop_id']);
        }
        if (isset($param['gm_id'])&& !empty($param['gm_id'])) {
            $rows = $rows->where('a.gm_id', '=', $param['gm_id']);
        }

        if(!empty($param['down']))
        {
            //下载提供数据
            $rows =  $rows->get();
        }
        else
        {
            $search_rows =  $rows->get();
            $rows = $rows->paginate($param['per_page']);

        }

        if (count($rows) > 0)
        {
            $return = $rows->toArray();
            $return['search_rows'] = $search_rows;
        }
        else
        {
            return [];
        }

//        if (isset($param['total_data_status']) && $param['total_data_status'])
//        {
//            $sum_result =$this->totalData($search_rows,$sum_data);
//            if($sum_result['sales_amount'] == 0)
//            {
//                $sum_result['average_fee_amount'] = 0;
//            }
//            else
//            {
//            $sum_result['average_fee_amount'] = round($sum_result['payed_amount']/$sum_result['sales_amount'],2);
//            }
//            $return['total_fee_data'] =  [
//                ['value' => $sum_result['payed_amount'], 'dataIndex' => 'payed_amount', 'title' => '销售订单实付金额汇总'],
//                ['value' => $sum_result['average_fee_amount'], 'dataIndex' => 'average_fee_amount', 'title' => '客单价汇总'],
//                //['value' => $sum_result['refund_payed_amount'], 'dataIndex' => 'refund_payed_amount', 'title' => '退货订单实付金额汇总'],
//                ['value' => $sum_result['total_discount_fee'], 'dataIndex' => 'total_discount_fee', 'title' => '优惠总额汇总'],
//                ['value' => $sum_result['platform_act_fee_amount'], 'dataIndex' => 'platform_act_fee_amount', 'title' => '平台优惠券金额汇总'],
//                ['value' => $sum_result['coupon_shop_fee_amount'], 'dataIndex' => 'coupon_shop_fee_amount', 'title' => '商家优惠券金额汇总'],
//                ['value' => $sum_result['promotion_fee_amount'], 'dataIndex' => 'promotion_fee_amount', 'title' => '店铺满减满折金额汇总'],
//                //['value' => $sum_result['points_fee_amount'], 'dataIndex' => 'points_fee_amount', 'title' => '积分抵扣金额汇总'],
//                ['value' => $sum_result['consume_point_fee_amount'], 'dataIndex' => 'consume_point_fee_amount', 'title' => '消耗积分汇总'],
//                ['value' => $sum_result['post_fee_amount'], 'dataIndex' => 'post_fee_amount', 'title' => '邮费汇总'],
//
//            ];
//        }
        return $return;

    }

    public function getRefundTrades($param)
    {
        $rows = DB::table('trade_refunds')
            ->where('updated_at', '>=', $param['time_start'])
            ->where('updated_at', '<=', $param['time_end'])
            ->where('shop_id', '=', $param['shop_id'])
            ->where('status', '=', '1')
            ->whereIn('refunds_type', ['0','2'])
            ->select(DB::raw('count(*) as refund_amount , sum(refund_fee) as refund_payed_amount'))
            ->get();
        $result = [];
        if (count($rows) > 0) {
            $result = $rows->toArray();
        }

        return $result;
    }

    public function getShopName($shopId)
    {
        //追加店铺名
        $rows = DB::table('shops')->where('id' , $shopId)->select('shop_name')->get();
        $result = [];
        if (count($rows) > 0) {
            $result = $rows->toArray();
            $result = $result[0];
        }

        return $result;
    }

    public function getPointsFeeAmount($param)
    {
        //追加当天积分抵扣金额
        $rows = DB::table('trades as a')
                                ->where('a.created_at', '>=', $param['time_start'])
                                    ->where('a.created_at', '<=', $param['time_end'])
                                    ->where('a.shop_id', '=', $param['shop_id'])
                                        ->where('status', '=', 'TRADE_FINISHED')
                                            ->select(DB::raw('sum(points_fee) as points_fee_amount'))
                                                 ->get();
        $result = [];
        if (count($rows) > 0) {
            $result = $rows->toArray();
            $result = $result[0];
        }

        return $result;

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
        foreach ($sum_data as $key =>&$value)
        {
            //$result[$value] = $model->sum($value);
            $result[$value] = array_sum(array_column($model,$value));
            $result[$value] = round($result[$value],2);
        }

        return $result;
    }



}