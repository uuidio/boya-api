<?php
/**
 * @Filename        TradeSpitService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Activity;
use ShopEM\Models\CouponStockOnline;
use ShopEM\Models\Payment;
use ShopEM\Models\TradeActivityDetail;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\TradeSplit;

class TradeSpitService
{

    /**::
     *  根据支付单拆分订单优惠
     *
     * @Author hfh_wind
     * @param $param  payment_id ,user_id
     * @return bool
     * @throws Exception
     */
    public function setPayment($payment_id)
    {

        $this->payment_id = $payment_id;
        //查支付单表
        $payment = Payment::where(['payment_id' => $this->payment_id, 'status' => 'succ'])->first();
        $this->amount = $payment['amount'];

        if (empty($payment)) {
            return false;
        }

        $paybill = TradePaybill::where(['payment_id' => $this->payment_id])->get();

        $paybill = $paybill->toArray();

        $this->paytype = $payment['pay_app'];

        $this->arrTids = $tids = array_column($paybill, 'tid');  //tids是数组

        $this->user_id = $payment['user_id'];

        $this->platform_coupon_fee = $payment['platform_coupon_fee'];//平台卷

        $this->consume_point_fee = $payment['consume_point_fee'];//用户使用积分

        $this->points_fee = $payment['points_fee'];//积分抵扣金额

        $this->paymentMoney(); //整个支付单应付总金额

        $this->platformCoupon(); //平台券

        $this->coupon(); //商家券

        $this->spiltPoints();  //积分

        //满减,满折 同一个商品只能参加一样
        $this->fulldiscount(); //满折

        $this->fullminus(); //满减

        DB::beginTransaction();
        try {
            foreach ($this->orderList as $key => &$order) {

                $order['payment_id'] = $this->payment_id;
                $order['pay_type'] = $this->paytype;
                $order['payed'] = $order['amount'];
                unset($order['amount']);

                $count = TradeSplit::where(['oid' => $order['oid']])->count();
                if ($count > 0) {
                    TradeSplit::where(['oid' => $order['oid']])->update($order);
                } else {
                    TradeSplit::create($order);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \Exception($e->getMessage());
        }

        return true;
    }

    /**
     *  平台券
     * @Author hfh_wind
     * @return bool
     */
    private function platformCoupon()
    {
        $platform_coupon_fee = $this->platform_coupon_fee;
        $params = array(
            'user_id'    => $this->user_id,
            'payment_id' => $this->payment_id,
        );

        $couponListData = CouponStockOnline::where($params)->first();

        if (empty($couponListData)) {
            return false;
        }
        $couponData = $couponListData->toArray();

        $i = 0;
        $arrn = count($this->orderList);

        $newset = 0;
        foreach ($this->orderList as $key => &$order) {
            $i++;
            //一个商品
            if ($arrn == 1) {
                $rateCoupon = $this->doSplit($platform_coupon_fee, $order['total_fee']);
                //平台券总计除以商品介格
                $order['coupon_platform_info'] = json_encode($couponData);
                $order['coupon_platform_fee'] = $rateCoupon;
            } else {
                //多个商品
                if ($arrn != $i) {

                    $rateCoupon = $this->doSplit($platform_coupon_fee, $order['total_fee']);
                    //平台券总计除以商品介格
                    $order['coupon_platform_info'] = json_encode($couponData);
                    $order['coupon_platform_fee'] = $rateCoupon;
                    $newset += $rateCoupon;
                } else {

                    $order['coupon_platform_info'] = json_encode($couponData);
                    $order['coupon_platform_fee'] = $platform_coupon_fee - $newset;
                }
            }
        }
    }


    /**
     *  拆分店铺优惠券
     * @Author hfh_wind
     */
    public function coupon()
    {
        $i = 0;
        $arrn = count($this->orderList);
        $newset = 0;
        foreach ($this->orderList as $key => &$order) {
            $feeRow = $this->tidMatchCoupon($order['tid']);
            $i++;

            if ($arrn == 1) {
                $rateCoupon = $this->doSplit($feeRow['coupon_fee'], $order['total_fee']);
                $order['coupon_shop_info'] = json_encode($feeRow);
                $order['coupon_shop_fee'] = $rateCoupon;
            } else {

                if ($arrn != $i) {
                    $rateCoupon = $this->doSplit($feeRow['coupon_fee'], $order['total_fee']);
                    $newset += $rateCoupon;
                    $order['coupon_shop_info'] = json_encode($feeRow);
                    $order['coupon_shop_fee'] = $rateCoupon;
                } else {
                    $order['coupon_shop_info'] = json_encode($feeRow);
                    $order['coupon_shop_fee'] = $feeRow['coupon_fee'] - $newset;
                }
            }

        }
    }


    /**
     *  拆分平台积分
     *
     * @Author hfh_wind
     */
    public function spiltPoints()
    {
        $points = $this->consume_point_fee;//用户使用积分

        $points_fee = $this->points_fee;//积分抵扣金额

        $i = 0;
        $arrn = count($this->orderList);
        $points_newset = 0;
        $points_fee_newset = 0;

        foreach ($this->orderList as $key => &$order) {

            $i++;

            if ($arrn == 1) {
                $rateCoupon = $this->doSplit($points, $order['total_fee']);
                $order['points'] = $rateCoupon;

                $rateCoupon = $this->doSplit($points_fee, $order['total_fee']);
                $order['points_fee'] = $rateCoupon;
            } else {

                if ($arrn != $i) {
                    $points_rateCoupon = $this->doSplit($points, $order['total_fee']);
                    $points_newset += $points_rateCoupon;
                    $order['points'] = $points_rateCoupon;

                    $points_fee_rateCoupon = $this->doSplit($points_fee, $order['total_fee']);
                    $points_fee_newset += $points_fee_rateCoupon;
                    $order['points_fee'] = $points_fee_rateCoupon;

                } else {
                    $order['points'] = $points - $points_newset;

                    $order['points_fee'] = $points_fee - $points_fee_newset;
                }
            }

        }
    }


    public function paymentMoney()
    {
//        $this->orderList = TradeOrder::whereIn('tid',$this->arrTids)->select('oid','tid','total_fee','user_id','goods_id','sku_id','quantity1')->get()->toArray();

        $this->orderList = DB::table('trade_orders')->whereIn('tid', $this->arrTids)->select('oid', 'tid', 'total_fee',
            'user_id', 'goods_id', 'sku_id', 'quantity','profit','goods_cost','amount')->get()->map(function ($value) {
            return (array)$value;
        })->toArray();



        $tmp_total_arr = array_column($this->orderList, "total_fee");

        $total = array_sum($tmp_total_arr);

        $this->paymentMoney = $total;

    }


    /**
     *  获取订单对应使用的优惠劵
     * @Author hfh_wind
     */
    public function tidMatchCoupon($tid)
    {
        $params['user_id'] = $this->user_id;
        $params['tid'] = $tid;
        // $params['payment_id']=$this->paymentId;
        $params['status'] = 2; //已用

        $res = CouponStockOnline::where($params)->first();
        if (empty($res)) {
            return false;
        }
        return $res;
    }


    /**
     * 满折促销
     * @Author hfh_wind
     */
    public function fulldiscount()
    {
        //一个订单里面可能会一个或者多个商品达成规则
//        $tmpRes = TradeActivityDetail::whereIn('tid', $this->arrTids)->where('user_id', '=',
//            $this->user_id)->selectRaw('any_value(tid) as tid,any_value(activity_id) as activity_id,any_value(activity_type) as activity_type,any_value(sku_id)  as sku_id')->groupBy('tid')->get()->toArray();

        $tmpRes_before = TradeActivityDetail::whereIn('tid', $this->arrTids)->where(['user_id'=>$this->user_id,'activity_type'=>'discount'])->get();

        if (count($tmpRes_before) >0) {
            $tmpRes_before=$tmpRes_before->toArray();
            $tmpRes = $this->assoc_unique($tmpRes_before, 'tid');
            //去重
            $tmpRes_tids = array_unique(array_column($tmpRes, "tid"));
            $set_order_arr = [];
            foreach ($this->orderList as $k => $order) {
                if (in_array($order['tid'], $tmpRes_tids)) {
                    $set_order_arr[$order['tid']][] = $order['total_fee'];
                }
            }

            $order_sum = [];

            foreach ($set_order_arr as $key => $value) {
                $order_sum[$key]['amount'] = array_sum($set_order_arr[$key]);
                $order_sum[$key]['tid'] = $key;
            }

            //满折优惠总金额
            $discount_fee_total = 0;
            //获取支付单满折的优惠总金额
            foreach ($tmpRes as $promotion_key => $promotion) {

                    $activity_id = $promotion['activity_id'];
                    $data = Activity::where(['id' => $activity_id])->first();
                    //满折规则
                    $tmp_condition_rule = $data['rule'];
                    $promotion['rule'] = $data['rule'];

                    $total_price = $order_sum[$promotion['tid']];
                    if ($total_price) {
                        //优惠
                        $discount_fee = $this->fulldiscount_rule_fee($total_price['amount'], $tmp_condition_rule);
                        $discount_fee_total += $discount_fee;
                    }
            }

            $i = 0;
            $promotion_fee_newset = 0;
            $promotion_fee_total = $discount_fee_total;
            $arr_count = count($tmpRes_before);//这里是取活动中关联的
            //拆分订单
            foreach ($tmpRes_before as $promotion_key => $promotion) {
                foreach ($this->orderList as $key => &$order) {

                    if ($order['sku_id'] == $promotion['sku_id']) {

                        $order['promotion_info'] = json_encode($promotion);
                        $i++;

                        if ($arr_count == 1) {

                            $promotion_fee = $this->doSplit($promotion_fee_total, $order['total_fee']);

                            $order['promotion_fee'] = $promotion_fee;
                        } else {

                            if ($arr_count != $i) {

                                $promotion_fee = $this->doSplit($promotion_fee_total, $order['total_fee']);
                                $promotion_fee_newset += $promotion_fee;
                                $order['promotion_fee'] = $promotion_fee;

                            } else {

                                $order['promotion_fee'] = $promotion_fee_total - $promotion_fee_newset;
                            }
                        }
                    }

                }
            }
        }
    }


    /**
     * 满减促销
     * @Author hfh_wind
     * @return bool
     */
    public function fullminus()
    {
        $tmpRes_before = TradeActivityDetail::whereIn('tid', $this->arrTids)->where(['user_id'=>$this->user_id,'activity_type'=>'fullminus'])->get();

        if (count($tmpRes_before) >0) {
            $tmpRes_before = $tmpRes_before->toArray();
            $tmpRes = $this->assoc_unique($tmpRes_before, 'tid');
            //去重
            $tmpRes_tids = array_unique(array_column($tmpRes, "tid"));
            $set_order_arr = [];
            foreach ($this->orderList as $k => $order) {
                if (in_array($order['tid'], $tmpRes_tids)) {
                    $set_order_arr[$order['tid']][] = $order['total_fee'];
                }
            }

            $order_sum = [];

            foreach ($set_order_arr as $key => $value) {
                $order_sum[$key]['amount'] = array_sum($set_order_arr[$key]);
                $order_sum[$key]['tid'] = $key;
            }

            //满折优惠总金额
            $fullminus_fee_total = 0;
            //获取支付单满折的优惠总金额
            foreach ($tmpRes as $promotion_key => $promotion) {

                $activity_id = $promotion['activity_id'];
                $data = Activity::where(['id' => $activity_id])->first();
                //满折规则
                $tmp_condition_rule = $data['rule'];
                $promotion['rule'] = $data['rule'];

                $total_price = $order_sum[$promotion['tid']];

                if ($total_price) {
                    //优惠
                    $fullminus_fee = $this->fullminus_rule_fee($total_price['amount'], $tmp_condition_rule);
                    $fullminus_fee_total += $fullminus_fee;
                }
            }

            $this->fullminusScheme($tmpRes,$fullminus_fee_total);
        }
    }

    //满减促销策
    private function fullminusScheme($fullminusRes,$discount_fee_total)
    {
        $count_fullminus =count($fullminusRes);
        //条件1
        if ($count_fullminus == 1) {
            //如果只有一条记录大于折扣优惠条件,将优惠N元只写入一条记录，不折分。
            foreach ($this->orderList as $k => &$order) {
                if ($order['sku_id'] == $fullminusRes[0]['sku_id']) {
                    $order['promotion_info'] = json_encode($fullminusRes[0]);
                    $order['promotion_fee'] = $discount_fee_total;
                }
            }
        }
        //////条件1 --- end ----
        //////条件2，如果没有一个记录大于折扣值,或者大于1条以上的记录，要做金额拆分。
        if ($count_fullminus > 1) {

            $newset=0;
            $i = 0;
            foreach ($this->orderList as $k => &$order) {

                foreach ($fullminusRes as $k2 => $v2) {

                    if ($order['sku_id'] == $v2['sku_id']) {
                        $i++;
                        if ($count_fullminus != $i) {
                            $order['promotion_info'] = json_encode($v2);
                            $order['promotion_fee'] = $this->doSplit($discount_fee_total, $order['total_fee']);
                            $newset += $order['promotion_fee'];

                        } else {
//print_r($newset);exit;
                            $order['promotion_fee'] = $discount_fee_total - $newset;

                        }
                    }
                }
            }
        }

        //////条件2 --- end ----
    }


    /**
     * 满减的，可以条件上不封顶
     * @Author hfh_wind
     * @param $order_money 订单总金额
     * @param $discount_max_money 打扣金额
     * @param $discount_money 打扣金额
     * @param $canjoin_repeat 是否递进
     * @return mixed 返回打折金额
     */
    private function discountMoney($order_money, $discount_max_money, $discount_money, $canjoin_repeat)
    {
        if (false == $canjoin_repeat) {
            //$canjoin_repeat=false ,表示只折扣一次不封顶。
            return $discount_money;
        }

        if ($canjoin_repeat) {
            $tmp = floor($order_money / $discount_max_money);  //取倍数

            return $this->number_multiple(array($discount_money, $tmp));
        }
    }


    /**
     * 拆分公式
     * @Author hfh_wind
     * @param $a 优惠金额
     * @param $s 总金额
     * @return string
     */
    private function doSplit($a, $s)
    {
        //公式 a*(s/total)
        $total = $this->paymentMoney;
        $tmp1 = 0;
        if ($s > 0 && !empty($a) && intval($total)>0) {
            $tmp1 = number_format($a * $s / $total, 2, ".", "");
        }
        return $tmp1;
    }


    /**
     * 某一键名的值不能重复，删除重复项
     * @Author hfh_wind
     * @param $arr
     * @param $key
     * @return mixed
     */
    public function assoc_unique($arr, $key)
    {
        $tmp_arr = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true

                unset($arr[$k]);

            } else {
                $tmp_arr[] = $v[$key];
            }
        }
        sort($arr); //sort函数对数组进行排序
        return $arr;
    }


    /**
     * 获取满减
     * @Author hfh_wind
     * @param $total_price 子订单总金额
     * @param $tmp_condition_rule 规则
     * @return int
     */
    public function fulldiscount_rule_fee($total_price, $tmp_condition_rule)
    {

        $ruleLength = count($tmp_condition_rule);
        $discount_price = 0;

        if ($total_price >= $tmp_condition_rule[$ruleLength - 1]['condition']) {
            $rulePercent = max(0, $tmp_condition_rule[$ruleLength - 1]['num']);
            $rulePercent = min($rulePercent, 100);
            $discount_price = $total_price * (1 - $rulePercent / 100);
        } elseif ($total_price < $tmp_condition_rule[0]['condition']) {
            $discount_price = 0;
        } else {
            for ($i = 0; $i < $ruleLength - 1; $i++) {
                if ($total_price >= $tmp_condition_rule[$i]['condition'] && $total_price < $tmp_condition_rule[$i + 1]['condition']) {
                    $rulePercent = max(0, $tmp_condition_rule[$i]['num']);
                    $rulePercent = min($rulePercent, 100);
                    $discount_price = $total_price * (1 - $rulePercent / 100);
                    break;
                }
            }
        }
        if ($discount_price < 0) {
            $discount_price = 0;
        }

        return $discount_price;
    }





    /**
     *  获得满减活动的减免金额
     *
     * @Author djw
     * @param $total_price
     * @param $act
     * @return int
     */
    public function fullminus_rule_fee($total_price, $tmp_condition_rule) {
        $ruleArray = $tmp_condition_rule;
        $ruleLength = count($ruleArray);
        $discount_price = 0;

        if( $total_price >=$ruleArray[$ruleLength-1]['condition'] )
        {
            $discount_price = $ruleArray[$ruleLength-1]['num'];
        }
        elseif( $total_price < $ruleArray[0]['condition'] )
        {
            $discount_price = 0;
        }
        else
        {
            for($i=0; $i<$ruleLength-1; $i++)
            {
                if( $total_price>=$ruleArray[$i]['condition'] && $total_price<$ruleArray[$i+1]['condition'] )
                {
                    $discount_price = $ruleArray[$i]['num'];
                }
            }
        }
        if($discount_price<0)
        {
            $discount_price = 0;
        }
        return $discount_price;
    }

}