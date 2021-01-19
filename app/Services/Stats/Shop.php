<?php

/**
 * Shop.php
 * @Author: nlx
 * @Date:   2019-10-08 18:36:34
 */
namespace ShopEM\Services\Stats;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeAftersales;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradeRefunds;


class Shop
{
    /**
     * 得到所有的商家id和新增订单数
     *
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function newTrade(array $params)
    {

//        $rows = Trade::where('created_at', '>=', $params['time_start'])->where('created_at', '<',
//            $params['time_end'])->select(DB::raw('count(*) as new_trade,sum(amount) as new_fee ,any_value(shop_id) as shop_id'))->groupBy('shop_id1')->get();
        $rows = DB::select("select count(*) as new_trade,sum(amount) as new_fee ,any_value(shop_id) as shop_id from `em_trades` where `created_at` >= '" . $params['time_start'] . "' and `created_at` < '" . $params['time_end'] . "' and `shop_id` > 0  group by `shop_id`");

        return $rows;
    }


    /**
     * 得到所有的商家id和待付款订单数
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function readyTrade(array $params)
    {

//        $rows= Trade::where('created_at', '>=', $params['time_start'])->where('created_at', '<',
//            $params['time_end'])->where(['status' => "WAIT_BUYER_PAY"])->select(DB::raw('count(*) as ready_trade ,sum(amount) as ready_fee,any_value(shop_id) as shop_id'))->groupBy('shop_id')->get();
        $rows = DB::select("select count(*) as ready_trade ,sum(amount) as ready_fee,any_value(shop_id) as shop_id from `em_trades` where `created_at` >= '" . $params['time_start'] . "' and `created_at` < '" . $params['time_end'] . "' and `status` = 'WAIT_BUYER_PAY' and `shop_id` > 0  group by `shop_id`");

        return $rows;
    }

    /**
     * 得到所有的商家id和以付款订单数和已付款的金额
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function alreadyTrade(array $params)
    {
        //  $rows= Trade::where('end_time', '>=', $params['time_start'])->where('end_time', '<', $params['time_end'])->where('status','<>',"WAIT_BUYER_PAY")->where('status','<>', "TRADE_CLOSED")->where('status' ,'<>', "TRADE_CLOSED_BY_SYSTEM")->select(DB::raw('count(*) as alreadytrade ,sum(amount) as alreadyfee,any_value(shop_idz) as shop_id'))->groupBy('shop_id')->get();

        $rows = DB::select("select count(*) as alreadytrade ,sum(amount) as alreadyfee,any_value(shop_id) as shop_id from `em_trades` where `pay_time` >= '" . $params['time_start'] . "' and `pay_time` < '" . $params['time_end'] . "' and `status` <> 'WAIT_BUYER_PAY' and `status` <> 'TRADE_CLOSED' and `status` <> 'TRADE_CLOSED_BY_SYSTEM' and `shop_id` > 0  group by `shop_id`");

        return $rows;
    }


    /**
     * 得到所有的商家id和待发货订单数量
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function readySendTrade(array $params)
    {

//        $rows= Trade::where('pay_time', '>=', $params['time_start'])->where('pay_time', '<', $params['time_end'])->where(['status'=>"WAIT_SELLER_SEND_GOODS"])->select(DB::raw('count(*) as ready_send_trade ,sum(amount) as ready_send_fee,any_value(shop_id) as shop_id'))->groupBy('shop_id')->get();

        $rows = DB::select("select count(*) as ready_send_trade ,sum(amount) as ready_send_fee,any_value(shop_id) as shop_id from `em_trades` where `pay_time` >= '" . $params['time_start'] . "' and `pay_time` < '" . $params['time_end'] . "' and (`status` = 'WAIT_SELLER_SEND_GOODS')  and `shop_id` > 0 group by `shop_id`");
        return $rows;
    }

    /**
     * 得到所有的商家id和待收货订单数量
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function alreadySendTrade(array $params)
    {
//        $rows= Trade::where('pay_time', '>=', $params['time_start'])->where('pay_time', '<', $params['time_end'])->where(['status'=>"WAIT_BUYER_CONFIRM_GOODS"])->select(DB::raw('count(*) as already_send_trade ,sum(amount11) as already_send_fee,any_value(shop_id) as shop_id'))->groupBy('shop_id')->get();

        $rows = DB::select("select count(*) as already_send_trade ,sum(amount) as already_send_fee,any_value(shop_id) as shop_id from `em_trades` where `pay_time` >= '" . $params['time_start'] . "' and `pay_time` < '" . $params['time_end'] . "' and (`status` = 'WAIT_BUYER_CONFIRM_GOODS') and `shop_id` > 0  group by `shop_id`");

        return count($rows) > 0 ? $rows : [];
    }


    /**
     * 得到所有的商家id和已完成订单数量
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function completeTrade(array $params)
    {
//        $rows= Trade::where('end_time', '>=', $params['time_start'])->where('end_time', '<', $params['time_end'])->where(['status'=>"TRADE_FINISHED"])->select(DB::raw('count(*) as complete_trade ,sum(amount) as complete_fee,completeTradeany_value(shop_id) as shop_id'))->groupBy('shop_id')->get();
        $rows = DB::select("select count(*) as complete_trade ,sum(amount) as complete_fee,any_value(shop_id) as shop_id from `em_trades` where `end_time` >= '" . $params['time_start'] . "' and `end_time` < '" . $params['time_end'] . "' and (`status` = 'TRADE_FINISHED') and `shop_id` > 0  group by `shop_id`");
        return $rows;
    }


    /**
     * 得到所有的商家id和已取消的订单数量
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function cancleTrade(array $params)
    {
//        $rows= Trade::where('end_time', '>=', $params['time_start'])->where('end_timecancleTrade', '<', $params['time_end'])->orWhere(['status'=>"TRADE_CLOSED"])->orWhere(['status'=>"TRADE_CLOSED_BY_SYSTEM"])->select(DB::raw('count(*) as cancle_trade ,sum(amount) as cancle_fee,any_value(shop_id) as shop_id'))->groupBy('shop_id')->get();

        $rows = DB::select("select count(*) as cancle_trade ,sum(amount) as cancle_fee,any_value(shop_id) as shop_id from `em_trades` where `shop_id` > 0 and `end_time` >= '" . $params['time_start'] . "' and `end_time` < '" . $params['time_end'] . "' or (`status` = 'TRADE_CLOSED') or (`status` = 'TRADE_CLOSED_BY_SYSTEM')  group by `shop_id`");

        return $rows;
    }

    /**
     * 得到所有的商家id和已退货退款的订单数量
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function refundTrade(array $params)
    {
//        $rows= TradeRefunds::where('updated_at', '>=', $params['time_start'])->where('updated_at', '<', $params['time_end'])->where(['status'=>1])->where(['refunds_type'=>0])->select(DB::raw('count(*) as refund_trade ,sum(refund_fee-refundTrade) as refund_fee,any_value(shop_id) as shop_id'))->groupBy('shop_id')->get();

        $rows = DB::select("select count(*) as refund_trade ,sum(refund_fee) as refund_fee,any_value(shop_id) as shop_id from `em_trade_refunds` where `updated_at` >= '" . $params['time_start'] . "' and `updated_at` < '" . $params['time_end'] . "' and (`status` = 1) and (`refunds_type` = 0)  and `shop_id` > 0  group by `shop_id`");

        return $rows;
    }

    /**
     * 得到所有的商家id和已换货的订单数量
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function exchangingTrade(array $params)
    {
//        $rows= TradeAftersales::where('updated_at', '>=', $params['time_start'])->where('updated_at', '<', $params['time_end'])->where(['aftersales_type'=>"EXCHANGING_GOODS"])->where(['progress'=>4])->where(['status'=>2])->select(DB::raw('count(*) as changing_trade ,any_value(shop_id) as shop_id'))->groupBy('shop_id')->get();

        $rows = DB::select("select count(*) as changing_trade ,any_value(shop_id) as shop_id from `em_trade_aftersales` where `updated_at` >= '" . $params['time_start'] . "' and `updated_at` < '" . $params['time_end'] . "' and (`aftersales_type` = 'EXCHANGING_GOODS') and (`progress` = 4) and (`status` = 2)  and `shop_id` > 0  group by `shop_id`");
        return $rows;
    }

    /**
     * 得到所有的商家id和已拒收的订单数量
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function rejectTrade(array $params)
    {
//
//        $rows= TradeRefunds::where('updated_at', '>=', $params['time_start'])->where('updated_at', '<', $params['time_end'])->where(['refunds_type'=>2])->where(['status'=>1])->select(DB::raw('count(*) as reject_trade,sum(refund_fee) as reject_fee ,any_value(shop_id) as shop_id'))->groupBy('shop_id')->get();

        $rows = DB::select("select count(*) as reject_trade,sum(refund_fee) as reject_fee ,any_value(shop_id) as shop_id from `em_trade_refunds` where `updated_at` >= '" . $params['time_start'] . "' and `updated_at` < '" . $params['time_end'] . "' and (`refunds_type` = 2) and (`status` = 1)  and `shop_id` > 0  group by `shop_id`");
        return $rows;
    }


    /**
     * 得到所有的商家id和热门商品
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function hotGoods(array $params)
    {
//        $rows= TradeOrder::where('pay_time-hotGoods', '>=', $params['time_start'])->where('pay_time', '<', $params['time_end'])->where('status','<>',"WAIT_BUYER_PAY")->select(DB::raw('sum(quantity) as itemnum,any_value(shop_id) as shop_id,any_value(goods_serial) as bn,any_value(goods_name) as title,any_value(goods_id) as goods_id,any_value(goods_image) as pic_path,sum(amount) as amountprice'))->groupBy('goods_id')->get();

        $rows = DB::select("select sum(quantity) as itemnum,any_value(shop_id) as shop_id,any_value(goods_serial) as bn,any_value(goods_name) as title,any_value(goods_id) as goods_id,any_value(goods_image) as pic_path,sum(amount) as amountprice from `em_trade_orders` where `pay_time` >= '" . $params['time_start'] . "' and `pay_time` < '" . $params['time_end'] . "' and `status` <> 'WAIT_BUYER_PAY' and `shop_id` > 0 group by `goods_id`");

        return $rows;

    }

    /**
     * 得到所有的商家id和退货商品
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function refundGoods(array $params)
    {
//        $rows= TradeOrder::where('updated_at-refundGoods', '>=', $params['time_start'])->where('updated_at', '<', $params['time_end'])->where(['status'=>"TRADE_FINISHED"])->where(['after_sales_status'=>'SUCCESS'])->select(DB::raw('sum(quantity) as refundnum,any_value(shop_id) as shop_id,any_value(goods_serial) as bn,any_value(goods_name) as title,any_value(goods_id) as goods_id,any_value(goods_image) as pic_path,sum(amount) as amountprice'))->groupBy('goods_id')->get();

        $rows = DB::select("select sum(quantity) as refundnum,any_value(shop_id) as shop_id,any_value(goods_serial) as bn,any_value(goods_name) as title,any_value(goods_id) as goods_id,any_value(goods_image) as pic_path,sum(amount) as amountprice from `em_trade_orders` where `updated_at` >= '" . $params['time_start'] . "' and `updated_at` < '" . $params['time_end'] . "' and (`status` = 'TRADE_FINISHED') and (`after_sales_status` = 'SUCCESS')  and `shop_id` > 0  group by `goods_id`");

        return $rows;
    }

    /**
     * 得到所有的商家id和换货商品
     * @Author hfh_wind
     * @param array $params
     * @return mixed
     */
    public function changingGoods(array $params)
    {
//        $rows= TradeOrder::where('updated_at', '>=', $params['time_start'])->where('updated_at', '<', $params['time_end'])->where(['status'=>"TRADE_FINISHED"])->where(['after_sales_status'=>'SELLER_SEND_GOODS'])->select(DB::raw('sum(quantity) as changingnum,any_value(shop_id) as shop_id,any_value(goods_serial) as bn,any_value(goods_name) as title,any_value(goods_id) as goods_id,any_value(goods_image) as pic_path'))->groupBy('goods_id')->get();

        $rows = DB::select("select sum(quantity) as changingnum,any_value(shop_id) as shop_id,any_value(goods_serial) as bn,any_value(goods_name) as title,any_value(goods_id) as goods_id,any_value(goods_image) as pic_path from `em_trade_orders` where `updated_at` >= '" . $params['time_start'] . "' and `updated_at` < '" . $params['time_end'] . "' and (`status` = 'TRADE_FINISHED') and (`after_sales_status` = 'SELLER_SEND_GOODS')  and `shop_id` > 0  group by `goods_id`");

        return $rows;
    }


    /**
     * 获取统计数据
     * @Author hfh_wind
     * @param $newTrade
     * @param $readyTrade
     * @param $readySendTrade
     * @param $alreadySendTrade
     * @param $completeTrade
     * @param $cancleTrade
     * @param $alreadyTrade
     * @param $refundTrade
     * @param $exchangingTrade
     * @param $rejectTrade
     * @param $params
     * @return mixed
     */
    public function getData(
        $newTrade,
        $readyTrade,
        $readySendTrade,
        $alreadySendTrade,
        $completeTrade,
        $cancleTrade,
        $alreadyTrade,
        $refundTrade,
        $exchangingTrade,
        $rejectTrade,
        $params
    ) {

        $tradeData = array_merge((array)$newTrade, (array)$readyTrade, (array)$readySendTrade, (array)$alreadySendTrade,
            (array)$completeTrade, (array)$cancleTrade, (array)$alreadyTrade, (array)$refundTrade,
            (array)$exchangingTrade, (array)$rejectTrade);
//        var_dump($tradeData);exit;
        $tradeArr = [];
        $arr = [];
        $data = [];
        foreach ($tradeData as $key => $value) {
            $arr[$value->shop_id][] = $value;
        }

        foreach ($arr as $key => $value) {
            foreach ($value as $ke => $val) {
                foreach ($val as $k => $v) {
                    $tradeArr[$key][$k] = $v;
                }
            }
        }

        foreach ($tradeArr as $key => $value) 
        {
            if ($value['shop_id'] == 0) continue;

            $data[$key]['shop_id'] = $value['shop_id'];
            $data[$key]['new_trade'] = isset($value['new_trade']) ? $value['new_trade'] : 0;

            $data[$key]['new_fee'] = isset($value['new_fee']) && $value['new_fee'] < '999999' ? $value['new_fee'] : 0;
            $data[$key]['ready_trade'] = isset($value['ready_trade']) ? $value['ready_trade'] : 0;
            $data[$key]['ready_fee'] = isset($value['ready_fee']) && $value['ready_fee'] < '999999'  ? $value['ready_fee'] : 0;

            $data[$key]['ready_send_trade'] = isset($value['ready_send_trade']) ? $value['ready_send_trade'] : 0;
            $data[$key]['ready_send_fee'] = isset($value['ready_send_fee']) ? $value['ready_send_fee'] : 0;

            $data[$key]['already_send_trade'] = isset($value['already_send_trade']) ? $value['already_send_trade'] : 0;
            $data[$key]['already_send_fee'] = isset($value['already_send_fee']) ? $value['already_send_fee'] : 0;
            $data[$key]['cancle_trade'] = isset($value['cancle_trade']) ? $value['cancle_trade'] : 0;
            $data[$key]['cancle_fee'] = isset($value['cancle_fee']) && $value['cancle_fee'] < '999999' ? $value['cancle_fee'] : 0;

            $data[$key]['complete_trade'] = isset($value['complete_trade']) ? $value['complete_trade'] : 0;
            $data[$key]['complete_fee'] = isset($value['complete_fee']) ? $value['complete_fee'] : 0;

            $data[$key]['alreadytrade'] = isset($value['alreadytrade']) ? $value['alreadytrade'] : 0;
            $data[$key]['alreadyfee'] = isset($value['alreadyfee']) ? $value['alreadyfee'] : 0;

            $data[$key]['refund_trade'] = isset($value['refund_trade']) ? $value['refund_trade'] : 0;
            $data[$key]['refund_fee'] = isset($value['refund_fee']) ? $value['refund_fee'] : 0;

            $data[$key]['reject_trade'] = isset($value['reject_trade']) ? $value['reject_trade'] : 0;
            $data[$key]['reject_fee'] = isset($value['reject_fee']) ? $value['reject_fee'] : 0;

            $data[$key]['total_refund_fee'] = isset($value['refund_fee']) ? $value['refund_fee'] : 0 + isset($value['reject_fee']) ? $value['reject_fee'] : 0;

            $data[$key]['changing_trade'] = isset($value['changing_trade']) ? $value['changing_trade'] : 0;

            $data[$key]['created_at'] = $params['time_insert'];
        }

        return $data;
    }


    /**
     * 获取商品统计数据
     * @Author hfh_wind
     * @param $hotGoods
     * @param $refundGoods
     * @param $changingGoods
     * @param $params
     * @return mixed
     */
    public function getGoodsData($hotGoods, $refundGoods, $changingGoods, $params)
    {
        $goodsData = [];
        if (!empty($hotGoods)) {
            foreach ($hotGoods as $key => $value) {
                $goodsData[$value->goods_id]['goods_id'] = $value->goods_id;
                $goodsData[$value->goods_id]['shop_id'] = $value->shop_id;
                $goodsData[$value->goods_id]['title'] = $value->title;
                $goodsData[$value->goods_id]['pic_path'] = $value->pic_path;
                $goodsData[$value->goods_id]['amountnum'] = $value->itemnum;
                $goodsData[$value->goods_id]['amountprice'] = $value->amountprice;
                $goodsData[$value->goods_id]['created_at'] = $params['time_insert'];
            }
        }
        if (!empty($refundGoods)) {
            foreach ($refundGoods as $k => $v) {
                $goodsData[$v->goods_id]['goods_id'] = $v->goods_id;
                $goodsData[$v->goods_id]['shop_id'] = $v->shop_id;
                $goodsData[$v->goods_id]['title'] = $v->title;
                $goodsData[$v->goods_id]['pic_path'] = $v->pic_path;
                $goodsData[$v->goods_id]['created_at'] = $params['time_insert'];
                $goodsData[$v->goods_id]['refundnum'] = $v->refundnum;
            }
        }

        if (!empty($changingGoods)) {
            foreach ($changingGoods as $goodkey => $change_val) {
                $goodsData[$change_val->goods_id]['goods_id'] = $change_val->goods_id;
                $goodsData[$change_val->goods_id]['shop_id'] = $change_val->shop_id;
                $goodsData[$change_val->goods_id]['title'] = $change_val->title;
                $goodsData[$change_val->goods_id]['pic_path'] = $change_val->pic_path;
                $goodsData[$change_val->goods_id]['created_at'] = $params['time_insert'];
                $goodsData[$change_val->goods_id]['changingnum'] = $change_val->changingnum;
            }
        }
        return $goodsData;
    }
}