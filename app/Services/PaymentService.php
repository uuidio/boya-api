<?php
/**
 * @Filename        PaymentService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ShopEM\Jobs\DistributionReward;
use ShopEM\Models\Payment;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradeCancel;
use ShopEM\Models\TradePaybill;
use ShopEM\Jobs\SplitTrade;
use ShopEM\Jobs\TradePushErp;
use ShopEM\Models\SecKillOrder;
use ShopEM\Models\PointActivityGoods;
use Illuminate\Support\Facades\Cache;

class PaymentService
{
    /**
     * 获取订单交易信息
     *
     * @Author moocde <mo@mocode.cn>
     * @param $payment_id
     * @return array
     */
    public static function tradeInfo($payment_id)
    {
        $trade_info = Trade::whereIn('tid', function ($query) use ($payment_id) {
            $query->select('tid')
                ->from(with(new TradePaybill)->getTable())
                ->where('payment_id', $payment_id);
        })->get();

        $trade_order = TradeOrder::whereIn('tid', function ($query) use ($payment_id) {
            $query->select('tid')
                ->from(with(new TradePaybill)->getTable())
                ->where('payment_id', $payment_id);
        })->get();

        return [
            'trade_info' => $trade_info,
            'trade_order' => $trade_order,
        ];
    }


    /**
     * 订单支付成功处理
     *
     * @Author hfh_wind
     * @param $payment_id
     * @param int $trade_no
     */

    public static function paySuccess($payment_data, $trade_no = 0)
    {
        $payment_id = $payment_data['payment_id'];
        DB::beginTransaction();
        try {
            $payed_time = Carbon::now()->toDateTimeString();
            $pay_app = $payment_data['pay_app'];
            $PayName = Payment::$payAppMap[$pay_app];

            if (!$trade_no) {
                $trade_no = isset($payment_data['trade_no']) ? $payment_data['trade_no'] : 0;
            }
            //改成支付成功后才扣减积分 v2 弃用
            // $tradeWillPaymentPoint = new \ShopEM\Models\TradeWillPaymentPoint;
            // $tradeWillPaymentPoint->payPoint($payment_id);

            Payment::where('payment_id', $payment_id)
                ->update([
                    'status' => Payment::SUCC,
                    'trade_no' => $trade_no,
                    'pay_app' => $pay_app,
                    'memo' => $PayName,
                    'payed_time' => $payed_time
                ]);

            TradePaybill::where('payment_id', $payment_id)
                ->update([
                    'status' => Payment::SUCC,
                    'payed_time' => $payed_time
                ]);
            Trade::whereIn('tid', function ($query) use ($payment_id) {
                $query->select('tid')
                    ->from(with(new TradePaybill)->getTable())
                    ->where('payment_id', $payment_id);
            })->update([
                'pay_time' => $payed_time,
                'status' => Trade::WAIT_SELLER_SEND_GOODS
            ]);
            TradeOrder::whereIn('tid', function ($query) use ($payment_id) {
                $query->select('tid')
                    ->from(with(new TradePaybill)->getTable())
                    ->where('payment_id', $payment_id);
            })->update([
                'pay_time' => $payed_time,
                'status' => Trade::WAIT_SELLER_SEND_GOODS,
            ]);

            //拆单
            SplitTrade::dispatch($payment_id);

            // testLog($res);
            Log::info('pay success');
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            throw new \Exception('状态恢复失败!' . $e->getMessage());
        }


        //变更秒杀订单状态
        $change = SecKillOrder::where(['payment_id' => $payment_id, 'state' => '2'])->count();
        if ($change) {
            SecKillOrder::where(['payment_id' => $payment_id])->update(['state' => '1']);
        }

        //如果是团购,处理
        $GroupService = new \ShopEM\Services\GroupService;
        $GroupService->GroupOrder($payment_data['user_id'], $payment_id);

        //如果支付成功则存入缓存
        $cache_key = 'api_pay_status_id_' . $payment_id;
        $cache_value = ['status' => 'succ', 'payment_id' => $payment_id, 'payed_time' => $payed_time];
        Cache::put($cache_key, $cache_value, Carbon::now()->addMinutes(5));

        //生成提货码
        $trades = Trade::whereIn('tid', function ($query) use ($payment_id) {
            $query->select('tid')
                ->from(with(new TradePaybill)->getTable())
                ->where('payment_id', $payment_id);
        })->get()->toArray();
        foreach ($trades as $trade) {

            if ($trade['pick_type']) {
                $pick_code = rand(100000, 999999);
                Trade::where(['tid' => $trade['tid']])->update(['pick_code' => $pick_code]);
            }
            //2020-4-9 18:51:46 支付成功推送ERP
            TradePushErp::dispatch($trade['tid']);
        }

        //处理推物分润
        DistributionReward::dispatch($payment_id);


        //第三方推单  该项目不需要使用 推单的功能  2019-9-7 10:49:48
        // $trade = Trade::whereIn('tid', function ($query) use ($payment_id) {
        //     $query->select('tid')
        //         ->from(with(new TradePaybill)->getTable())
        //         ->where('payment_id', $payment_id);
        // })->get();
        // if (!count($trade)) {
        //     return true;
        // }
        // 第三限购
        // $limitService = new \ShopEM\Services\LimitBuyService();
        // foreach ($trade as $key => $value) {
        //     $check_limit = $limitService->checkOrderLimitBuy($value->tid);
        //     if ($check_limit['code']) {
        //         $error = 'tid:'.$value->tid.'|'.$check_limit['goods_name'].'('.$value->user_id.'):'.$check_limit['msg'];
        //         Log::info($error);
        //         return false;
        //     }
        //     $order = TradeOrder::where('tid',$value->tid)->get();
        //     foreach ($order as $k => $v) {
        //         $limitService->saveLog($v->sku_id,$v->user_id,$v->quantity);
        //     }
        // }
        // $model = new \ShopEM\Services\GoodsPushService();
        // $res = $model->putFilter($trade);//过滤推送老环游

    }

    /**
     * 用不上,废弃
     *
     * @Author hfh_wind
     * @param $pay_name
     * @return array|string
     */
    public static function getPayName($pay_name)
    {
        $data = [];
        switch ($pay_name) {
            case 'Wxpayjsapi':

                $data = '微信H5支付';

            case 'Wxpaywap':

                $data = '微信外H5支付';
                break;
            case 'WxpayApp':

                $data = '微信app支付';
                break;
            case 'Wxqrpay':

                $data = '微信二维码支付';
                break;
            case 'Alipay':

                $data = '支付宝支付';
                break;
            case 'Malipay':

                $data = '支付宝H5支付';
                break;
            case 'AlipayApp':

                $data = "支付宝app支付";
                break;
            case 'Deposit':

                $data = "余额支付";
                break;
        }

        return $data;
    }


    /**
     * 支付完成扣减库存
     *
     * @Author hfh_wind
     * @param $payment_id
     * @param $status 1-是未付款  0-是付款扣减
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public static function freezeGoodStore($payment_id, $status)
    {
        $freezeStore = new  \ShopEM\Services\GoodsService();

        DB::beginTransaction();
        try {

            if ($status) {
                //未付款扣减库存
                $get_param = ['payment_id' => $payment_id];
            } else {
                $get_param = ['payment_id' => $payment_id, 'status' => 'succ'];
            }

            $TradePaybill = TradePaybill::where($get_param)->first();

            if (empty($TradePaybill)) {
                return false;
//                throw new \Exception("尚未支付完成,异常订单!");
            }
            $trad_model = new Trade();
            $trade_info = $trad_model->where(['trades.tid' => $TradePaybill->tid])->select('trade_orders.*')
                ->leftJoin('trade_orders', 'trades.tid', '=', 'trade_orders.tid')->get();
            $arrParams = [];
            foreach ($trade_info as $key => $value) {
                if ($value['activity_type'] != 'seckill') {
                    $arrParams['goods_id'] = $value['goods_id'];
                    $arrParams['sku_id'] = $value['sku_id'];
                    $arrParams['quantity'] = $value['quantity'];
                    $arrParams['oid'] = $value['oid'];
                    $arrParams['shop_id'] = $value['shop_id'];

                    $res = $freezeStore->freezeItemStore($arrParams);
                    if (!$res) {
                        throw new \Exception('扣减库存失败!');
                    }
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('扣减库存失败!' . $e->getMessage());
        }
        DB::commit();

        return true;
    }


    /**
     * 支付前判断商品是否可购买
     *
     * @Author djw
     * @param $trades
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public static function goodsIsAleAble($trade_orders)
    {
        DB::beginTransaction();
        try {
            foreach ($trade_orders as $trade_order_info) {
                $goodsSaleAble = GoodsService::saleAble($trade_order_info['sku_id']);
                if ($goodsSaleAble['code'] === 0) {
                    throw new \Exception($trade_order_info['goods_name'] . $goodsSaleAble['message']);
                }
            }


        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('扣减库存失败!' . $e->getMessage());
        }
        DB::commit();

        return true;
    }

    //如果是系统自动关闭的订单，支付成功的要自动退款
    public function systemDoRefund($payment_data,$payment_info)
    {
        $payment_id = $payment_data['payment_id'];
        DB::beginTransaction();
        try {
            $payed_time = Carbon::now()->toDateTimeString();
            $pay_app = $payment_data['pay_app'];
            $PayName = Payment::$payAppMap[$pay_app];
            $trade_no = isset($payment_data['trade_no']) ? $payment_data['trade_no'] : 0;

            Payment::where('payment_id', $payment_id)
                ->update([
                    'trade_no' => $trade_no,
                    'pay_app' => $pay_app,
                    'memo' => $PayName,
                    'payed_time' => $payed_time
                ]);

            $tradepaybill = TradePaybill::where('payment_id', $payment_id)->pluck('tid');
            $tids = [];
            foreach ($tradepaybill as $tid) {
                $tids[] = $tid;
            }
            $trade_cancel = TradeCancel::whereIn('tid', $tids)->first();
            if ($trade_cancel) 
            {
                $refund_bn = $trade_cancel->cancel_id;
                TradeCancel::whereIn('tid', $tids)->update([
                    'reason' => '支付超时，系统关闭订单',
                ]);
            }
            if(!isset($refund_bn)) $refund_bn = TradeService::createId('trade_cancel');

            $gm_id=$payment_info['gm_id'];

            //目前只走微信小程序支付退款
            $pay_app_ins = new \ShopEM\Services\Payment\Wxpaymini($gm_id);
            $refund['pay_type'] = 'refund';
            $refund['payment_id'] = $payment_id;
            $refund['refund_bn'] = $refund_bn;
            $refund['total_fee'] = $refund['refund_fee'] = $payment_info->amount;
            $refund['refund_desc'] = '支付超时，系统关闭订单，且退款';
            $pay_app_ins->dorefund($refund);

            DB::commit();
        } catch (\Exception $e) {
            Log::error( $payment_id. '退款失败' .$e->getMessage());
            // throw new \Exception($e->getMessage());
            DB::rollBack();
            return false;
        }
        return true;
    }   

}
