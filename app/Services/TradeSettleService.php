<?php
/**
 * @Filename        TradeSettleService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Config;
use ShopEM\Models\Shop;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeDaySettleAccount;
use ShopEM\Models\TradeDaySettleAccountDetail;
use ShopEM\Models\TradeMonthSettleAccount;
use ShopEM\Models\TradeRefundLog;
use ShopEM\Models\TradeSplit;


class TradeSettleService
{

    /**
     *  订单明细结算
     *
     * @Author hfh_wind
     * @param $param  time_start ,time_end,time_insert
     * @return bool
     * @throws Exception
     */
    public function tradeDayDetail($param)
    {
        //订单信息
        $tradeInfo = $this->getTrades($param);
        $point_rate = Config::where(['group' => 'point'])->first();
        $point_rate_value = '';
        if ($point_rate) {
            $point_rate = json_decode($point_rate['value'], true);
            $point_rate_value = $point_rate['point_deduction_rate']['value'] . "%";
        }

        foreach ($tradeInfo as $key => $value) {
            $save = [];
            $split = $this->getTradeSplit($value->tid);
            if ($split) {
                $save['tid'] = $split->tid;
                $save['shop_id'] = $value->shop_id;
                $save['pay_time'] = $value->pay_time;
                $save['pay_type'] = $split->pay_type;
                $save['shop_act_fee'] = $split->promotion_fee + $split->coupon_shop_fee; //店铺优惠
                $save['coupon_shop_fee'] =  -$split->coupon_shop_fee; //店铺优惠券金额
                $save['promotion_fee'] = -$split->promotion_fee ; //店铺促销金额
                $save['shop_act_fee'] = $save['shop_act_fee'] > 0 ? -$save['shop_act_fee'] : $save['shop_act_fee'];
                //$save['platform_act_fee'] = '0'; //平台优惠
                $save['platform_act_fee'] = -$split->coupon_platform_fee; //平台优惠卷优惠（由于现在没平台活动所以就不划分优惠）
                $save['total_discount_fee'] = $split->coupon_platform_fee + $split->coupon_shop_fee + $split->promotion_fee; //总折扣金额
                $save['total_discount_fee'] = $save['total_discount_fee'] > 0 ? -$save['total_discount_fee'] : $save['total_discount_fee'];

                $save['points'] = -$split->points; //积分
                $save['goods_price_amount'] = $split->total_fee; //商品总金额
                $save['post_fee'] = $value->post_fee; //邮费
                $save['points_fee'] = -$split->points_fee; //积分抵扣金额

//                $save['payed'] = $save['goods_price_amount'] + $save['shop_act_fee'] + $save['platform_act_fee'] - $save['post_fee']; //实付
                $save['payed'] = $split->payed;

                $shopinfo = Shop::where(['id' => $save['shop_id']])->select('shopRate', 'is_own_shop')->first();
                $save['point_rate'] = $point_rate_value; //积分比例

                $save['settle_time'] = $param['time_insert']; //结算时间
                $save['shop_rate'] = $shopinfo['shopRate'] . "%"; //店铺扣点比例


//                //如果是自营就不算邮费
//                if ($shopinfo['is_own_shop'])
//                {
//                    $amount = $save['goods_price_amount']- $save['platform_act_fee'] - $save['shop_act_fee'] - $save['points_fee'];
//                    $save['shop_rate_fee'] = -($shopinfo['shopRate']*$amount/100); //店铺扣点金额
//                } else {
//                    //非自营
//                    $amount = $save['goods_price_amount'] - $save['platform_act_fee'] - $save['shop_act_fee'] - $save['points_fee'] + $save['post_fee'];
//                    $save['shop_rate_fee'] = -($shopinfo['shopRate']*$amount/100); //店铺扣点金额
//                }
                $amount = $save['payed'] + $save['post_fee'];
                $save['shop_rate_fee'] = -($shopinfo['shopRate']*$amount/100); //店铺扣点金额

                //$amount_fee=$amount-$save['shop_rate_fee'];
                $amount_fee = $amount + $save['shop_rate_fee'];

                $save['settlement_fee'] = $amount_fee; //结算金额

                $check_detail = TradeDaySettleAccountDetail::where('settle_time', '>=',
                    $param['time_start'])->where('settle_time', '<=', $param['time_end'])->where('tid', '=',
                    $split->tid)->where('settle_type', '=', '1')->count();

                $save['goods_cost_amount'] = $split->goods_cost_amount ?? 0; //新增成本价汇总
                $profit_amount = $split->profit_amount ?? 0; //新增利润额汇总
                $save['profit_amount'] = $profit_amount + $save['shop_rate_fee'] ; //新增利润额汇总

                if ($check_detail)
                {
                    TradeDaySettleAccountDetail::where(['tid' => $split->tid, 'settle_type' => '1'])->update($save);
                } else {
                    TradeDaySettleAccountDetail::create($save);
                }
            }
        }

        //当天退款订单处理
        $refundTradeInfo = $this->getRefundTrades($param);

        if (!empty($refundTradeInfo)) {

            foreach ($refundTradeInfo as $key_refund => $value_refund) {
                //同步结算单
//                $syn_data = TradeDaySettleAccountDetail::where('settle_time', '>=',
//                $param['time_start'])->where('settle_time', '<=',
//                $param['time_end'])->where(['tid' => $value_refund['tid'], 'settle_type' => '1'])->first();
                $syn_data = TradeDaySettleAccountDetail::where(['tid' => $value_refund['tid'], 'settle_type' => '1'])->first();

                if($syn_data)
                {
                    $save_refunds['pay_time'] = $syn_data['pay_time'];
                    $save_refunds['pay_type'] =  $syn_data['pay_type'];
                    $save_refunds['goods_price_amount'] = -$syn_data['goods_price_amount'];
                    $save_refunds['shop_act_fee'] = abs($syn_data['shop_act_fee']);
                    $save_refunds['points_fee'] = abs($syn_data['points_fee']);
                    $save_refunds['points'] = abs($syn_data['points']);
                    $save_refunds['post_fee'] = '0'; //不退运费
                    $save_refunds['payed'] = -$syn_data['payed'];
                    $save_refunds['shop_rate'] = $syn_data['shop_rate'];
                    $save_refunds['point_rate'] = $syn_data['point_rate'];
                    $save_refunds['shop_rate_fee'] = abs($syn_data['shop_rate_fee']);
                    $save_refunds['refund_type'] = $syn_data['refund_type'] ?? 1;
                    $save_refunds['goods_cost_amount'] = -$syn_data['goods_cost_amount'];
                    $save_refunds['profit_amount'] = -$syn_data['profit_amount'];
                    $save_refunds['coupon_shop_fee'] =  abs($syn_data['coupon_shop_fee']); //店铺优惠券金额
                    $save_refunds['promotion_fee'] = abs($syn_data['promotion_fee']); //店铺促销金额
                    //新增优惠卷
                    //$save['platform_act_fee'] = '0'; //平台优惠
                    $save_refunds['platform_act_fee'] = abs($syn_data['platform_act_fee']); //平台优惠卷优惠（由于现在没平台活动所以就不划分优惠）
                    $save_refunds['total_discount_fee'] = abs($syn_data['total_discount_fee']); //总折扣金额
                }

                $save_refunds['tid'] = $value_refund['tid'];
                $save_refunds['refund_fee'] = $value_refund['return_fee'];
                // $save_refunds['refund_points_fee']=$refunds['points_fee'];
                //退积分
                $get_shop_id = Trade::where(['tid' => $value_refund['tid']])->select('shop_id')->first();
//                $save_refunds['point_amount'] = $value_refund['refund_point'];
                //售后类型
                $save_refunds['shop_id'] = $get_shop_id['shop_id'];
                $save_refunds['settle_type'] = '2';
                $save_refunds['settle_time'] = $param['time_insert'];
                $save_refunds['settlement_fee'] = -$value_refund['return_fee'];
                $check_refund = TradeDaySettleAccountDetail::where('settle_time', '>=',
                    $param['time_start'])->where('settle_time', '<=',
                    $param['time_end'])->where(['tid' => $value_refund['tid'], 'settle_type' => '2'])->first();
                try {
                    if ($check_refund) {
                        TradeDaySettleAccountDetail::where([
                            'tid'         => $value_refund['tid'],
                            'settle_type' => '2'
                        ])->update($save_refunds);
                    } else {
                        TradeDaySettleAccountDetail::create($save_refunds);
                    }
                } catch (\Exception $e) {

                    throw new \Exception($e->getMessage());
                }

            }
        }
    }


    /**
     *  订单日结
     *
     * @Author hfh_wind
     * @param $param
     * @return string
     * @throws \Exception
     */
    public function tradeDay($param)
    {
        $tradeDayInfo = $this->getTradesDetail($param);
        if (empty($tradeDayInfo)) {
            return 'none';
        }

//        $conf = Config::where(['group' => 'manage'])->first();
//        if (!empty($conf)) {
//            $conf = json_decode($conf['value'], true);
//            $conf_value = $conf['manage_fee']['value'];
//        }

        foreach ($tradeDayInfo as $key => $value)
        {
            $param['shop_id'] = $value['shop_id'];
            $repeat_data = self::getTradesCount($param);
            $save['shop_id'] = $value['shop_id'];
            $save['tradecount'] = $value['count'] - $repeat_data;
            $save['goods_price_amount'] = $value['goods_price_amount'];
            $save['shop_act_fee_amount'] = $value['shop_act_fee'];
            $save['platform_act_fee_amount'] = $value['platform_act_fee'];
            $save['points_fee_amount'] = $value['points_fee'];
            $save['points_amount'] = $value['points'];
            $save['post_fee_amount'] = $value['post_fee'];
            $save['payed_amount'] = $value['payed_amount'];
            $save['shop_rate'] = $value['shop_rate'];
            $save['shop_rate_fee_amount'] = $value['shop_rate_fee'];
            $save['refund_fee_amount'] = $value['refund_fee'];
            //管理费
            $shopinfo = Shop::where(['id' => $value['shop_id']])->select('manage_fee')->first();
            $save['manage_fee'] = $shopinfo['manage_fee'] ? $shopinfo['manage_fee'] : 0;
            $save['point_rate'] = $value['point_rate'];
            $save['settlement_fee_amount'] = $value['settlement_fee'];

            $save['settle_time'] = $param['time_insert'];

            //新增客单价
            $save['average_fee_amount'] = bcdiv($value['goods_price_amount'] ,  $value['count'],2) ?? 0;
            //新增利润额汇总
            $save['profit_amount'] = $value['profit_amount'] ?? 0;

            $save['coupon_shop_fee_amount'] =  $value['coupon_shop_fee'];//店铺优惠券金额
            $save['promotion_fee_amount'] = $value['promotion_fee'];//店铺促销金额
            //新增优惠卷
            $save['total_discount_fee_amount'] = $value['total_discount_fee']; //总折扣金额

            $save['payed_amount'] = $value['payed_amount']; //实付汇总

            $check_detail = TradeDaySettleAccount::where('settle_time', '>=',
                $param['time_start'])->where('settle_time', '<=', $param['time_end'])->where('shop_id', '=',
                $save['shop_id'])->count();

            try {
                if ($check_detail) {
                    TradeDaySettleAccount::where('settle_time', '>=',
                        $param['time_start'])->where('settle_time', '<=', $param['time_end'])->where(['shop_id' => $save['shop_id']])->update($save);

                } else {
                    TradeDaySettleAccount::create($save);
                }

            } catch (\Exception $e) {

                throw new \Exception($e->getMessage());
            }

        }

    }


    /**
     * 订单月结
     * @Author hfh_wind
     * @param $param
     * @return string
     * @throws \Exception
     */
    public function tradeMonth($param)
    {
        if (empty($param)) {
            //计划任务生成,获取上月日期
            $time_info = $this->getLastmonth();
            $param = array(
                'time_start'  => $time_info['firstday'],
                'time_end'    => $time_info['lastday'],
                'time_insert' => date('Y-m-d H:i:s', time()),
            );

            $tradesMonthInfo = $this->getTradesMonth($param);
        } else {
            //手动指定日期生成
            $tradesMonthInfo = $this->getTradesMonth($param);
        }

        if (empty($tradesMonthInfo)) {
            return 'none';
        }


        foreach ($tradesMonthInfo as $key => $value)
        {
            $param['shop_id'] = $value['shop_id'];
            //$repeat_data = self::getTradesCount($param);
            $save['shop_id'] = $value['shop_id'];
            //$save['tradecount'] = $value['tradecount'] - $repeat_data;
            $save['tradecount'] = $value['tradecount'];
            $save['goods_price_amount'] = $value['goods_price_amount'];
            $save['shop_act_fee_amount'] = $value['shop_act_fee_amount'];
            $save['platform_act_fee_amount'] = $value['platform_act_fee_amount'];
            $save['points_fee_amount'] = $value['points_fee_amount'];
            $save['points_amount'] = $value['points_amount'];
            $save['post_fee_amount'] = $value['post_fee_amount'];
            $save['payed_amount'] = $value['payed_amount'];
            $save['shop_rate'] = $value['shop_rate'];
            $save['shop_rate_fee_amount'] = $value['shop_rate_fee_amount'];
            $save['refund_fee_amount'] = $value['refund_fee_amount'];
            $save['manage_fee'] = $value['manage_fee'];
            $save['point_rate'] = $value['point_rate'];
            $save['settlement_fee_amount'] = round($value['settlement_fee_amount'],2);

            $save['settle_time'] = $param['time_start'];//月结第一天作为结算时间

            //新增客单价
            $save['average_fee_amount'] = bcdiv($value['goods_price_amount'] ,  $value['tradecount'],2);
            //新增利润额汇总
            $save['profit_amount'] = $value['profit_amount'];

            $save['coupon_shop_fee_amount'] =  $value['coupon_shop_fee_amount'];//店铺优惠券金额
            $save['promotion_fee_amount'] = $value['promotion_fee_amount'];//店铺促销金额
            //新增优惠卷
            $save['total_discount_fee_amount'] = $value['total_discount_fee_amount']; //总折扣金额

            $save['payed_amount'] = $value['payed_amount']; //实付汇总

            $check_detail = TradeMonthSettleAccount::where('settle_time', '=',
                $param['time_start'])->where('shop_id', '=',
                $save['shop_id'])->count();

            try {
                if ($check_detail)
                {
                    TradeMonthSettleAccount::where('settle_time', '>=',
                        $param['time_start'])->where('settle_time', '<=', $param['time_end'])->where(['shop_id' => $save['shop_id']])->update($save);
                } else {
                    TradeMonthSettleAccount::create($save);
                }

            } catch (\Exception $e) {

                throw new \Exception($e->getMessage());
            }

        }

    }


    /**
     * 得到所有的已经支付的订单
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function getTrades($params)
    {
//        $rows = Trade::where('pay_time', '>=', $params['time_start'])->where('pay_time', '<=',
//            $params['time_end'])->select('post_fee', 'tid', 'shop_id', 'pay_time')->get();
        $rows = Trade::where('confirm_at', '>=', $params['time_start'])->where('confirm_at', '<=',
            $params['time_end'])->whereIn('status' , ['TRADE_FINISHED'])->select('post_fee', 'tid', 'shop_id', 'pay_time')->get();
        $return = [];
        if (count($rows) > 0) {
            $return = $rows;
        }
        return $return;
    }


    /**
     * 得到当天平台处理的退款订单信息
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function getRefundTrades($params)
    {
        $rows = TradeRefundLog::where('created_at', '>=', $params['time_start'])->where('created_at', '<=',
            $params['time_end'])->where('status', '=', 'succ')->whereIn('refunds_type', ['0','2'])->select('return_fee', 'tid', 'refunds_id')->get();
        $return = [];
        if (count($rows) > 0) {
            $return = $rows->toArray();
        }
        return $return;
    }


    /**
     * 订单对应的拆分数据
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function getTradeSplit($tid)
    {
        $rows = DB::select("select any_value(tid) as tid,any_value(pay_type) as pay_type,sum(coupon_shop_fee) as coupon_shop_fee,sum(total_fee) as total_fee,sum(promotion_fee) as promotion_fee,sum(coupon_platform_fee) as coupon_platform_fee,sum(points_fee) as points_fee,sum(points) as points ,sum(profit) as profit_amount ,sum(goods_cost) as goods_cost_amount,sum(payed) as payed from `em_trade_splits` where `tid`=$tid   group by `tid`");
        $return = [];
        if (count($rows) > 0) {
            $return = $rows[0];
        }
        return $return;
    }


    /**
     * 得到所有的已经支付的订单
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function getTradesDetail($params)
    {
        $rows = TradeDaySettleAccountDetail::where('settle_time', '>=', $params['time_start'])->where('settle_time',
            '<=',
            $params['time_end'])->select(DB::raw('count(*) as count,any_value(tid) as tid,any_value(shop_id) as shop_id,sum(goods_price_amount) as goods_price_amount,sum(shop_act_fee) as shop_act_fee,sum(platform_act_fee) as platform_act_fee,sum(points_fee) as points_fee,sum(points) as points,sum(post_fee) as post_fee,sum(payed) as payed_amount,any_value(shop_rate) as shop_rate,any_value(point_rate) as point_rate,sum(shop_rate_fee) as shop_rate_fee,sum(refund_fee) as refund_fee,sum(settlement_fee) as settlement_fee,sum(profit_amount) as profit_amount,sum(coupon_shop_fee) as coupon_shop_fee,sum(promotion_fee) as promotion_fee,sum(total_discount_fee) as total_discount_fee'))->groupBy('shop_id')->get();

        $return = [];
        if (count($rows) > 0) {
            $return = $rows->toArray();
        }
        return $return;
    }


    /**
     * 得到所有的已经支付的订单
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function getTradesMonth($params)
    {
        $rows = TradeDaySettleAccount::where('settle_time', '>=', $params['time_start'])->where('settle_time',
            '<=',
            $params['time_end'])->select(DB::raw('any_value(shop_id) as shop_id,sum(tradecount) as tradecount,sum(goods_price_amount) as goods_price_amount,sum(shop_act_fee_amount) as shop_act_fee_amount,sum(platform_act_fee_amount) as platform_act_fee_amount,sum(points_fee_amount) as points_fee_amount,sum(points_amount) as points_amount,sum(post_fee_amount) as post_fee_amount,sum(payed_amount) as payed_amount,any_value(shop_rate) as shop_rate,any_value(point_rate) as point_rate,sum(shop_rate_fee_amount) as shop_rate_fee_amount,sum(refund_fee_amount) as refund_fee_amount,sum(settlement_fee_amount) as settlement_fee_amount,any_value(manage_fee) as manage_fee,sum(profit_amount) as profit_amount,sum(coupon_shop_fee_amount) as coupon_shop_fee_amount,sum(promotion_fee_amount) as promotion_fee_amount,sum(total_discount_fee_amount) as total_discount_fee_amount'))->groupBy('shop_id')->get();

        $return = [];
        if (count($rows) > 0) {
            $return = $rows->toArray();
        }
        return $return;
    }

    /**
     *  获取月份开始和结束时间
     *
     * @Author hfh_wind
     * @param string $y
     * @param string $m
     * @return array
     */
    public function monthGetFristAndLast($y = "", $m = "")
    {
        if ($y == "") {
            $y = date("Y");
        }
        if ($m == "") {
            $m = date("m");
        }
        $m = sprintf("%02d", intval($m));
        $y = str_pad(intval($y), 4, "0", STR_PAD_RIGHT);

        $m > 12 || $m < 1 ? $m = 1 : $m = $m;
        $firstday = strtotime($y . $m . "01000000");
        $firstdaystr = date("Y-m-01 00:00:00", $firstday);
        $lastday = date('Y-m-d 23:59:59', strtotime("$firstdaystr +1 month -1 day"));
        return array("firstday" => $firstdaystr, "lastday" => $lastday);
    }

    /**
     * 获取上个月日期
     * @Author hfh_wind
     * @return array
     */
    public function getLastmonth()
    {
        $thismonth = date('m');
        $thisyear = date('Y');
        if ($thismonth == 1) {
            $lastmonth = 12;
            $lastyear = $thisyear - 1;
        } else {
            $lastmonth = $thismonth - 1;
            $lastyear = $thisyear;
        }
        $lastStartDay = $lastyear . '-' . $lastmonth . '-1';

        $lastEndDay = $lastyear . '-' . $lastmonth . '-' . date('t', strtotime($lastStartDay));

        $b_time = strtotime($lastStartDay);//上个月的月初时间戳

        $e_time = strtotime($lastEndDay);//上个月的月末时间戳

        $firstday =  date("Y-m-01 00:00:00", $b_time);;//上个月的月初时间
        $lastday = date('Y-m-d 23:59:59',$e_time);//上个月的月末时间

        return ["firstday" => $firstday, "lastday" => $lastday];
    }


    /**
     * 获取当天重复的订单记录数
     * @Author Huiho
     * @param array $params
     * @return mixed
     */
    private function getTradesCount($params)
    {
        $count = TradeDaySettleAccountDetail::where('settle_time', '>=', $params['time_start'])->where('settle_time',
            '<=',
            $params['time_end'])->where(['shop_id' => $params['shop_id'], 'settle_type' => '2'])->count();

        return $count;
        
    }


    /**
     *  提供开发人员修复数据
     */
    public function _repairData()
    {
//        $tradeDaySettleAccount =  TradeDaySettleAccountDetail::where('id' , '>' ,  '0' )->select('tid' , 'shop_rate_fee' , 'settlement_fee')->get()->toArray();
//        foreach ($tradeDaySettleAccount as $key => $value)
//        {
//            $update_data['settlement_fee'] = 2*$value['shop_rate_fee'] + $value['settlement_fee'];
//            TradeDaySettleAccountDetail::where('tid' , $value['tid'])->update($update_data);
//        }
        echo 'success';
    }


}