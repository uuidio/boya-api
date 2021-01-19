<?php
/**
 * @Filename TradeAfterRefundService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ShopEM\Jobs\RefundTradePush;
use ShopEM\Jobs\TradePushErp;
use ShopEM\Models\Shop;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeAftersaleLog;
use ShopEM\Models\TradeCancel;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\TradeRefunds;
use ShopEM\Models\TradeAftersales;
use ShopEM\Models\TradeRefundLog;
use ShopEM\Models\Payment;
use ShopEM\Services\TradeService;

class TradeAfterRefundService
{

    /**
     * 判断是否可以进行创建退款申请单
     */
    public static function __check($params)
    {
        if ($params['refunds_type'] == 'cancel') {
            $data = TradeRefunds::where(['tid' => $params['tid']])->select('id', 'status')->first();
            if ($data && $data['status'] != '4') {
                throw new \Exception("不能重复申请取消");
            }
        } else {
            if (empty($params['aftersales_bn'])) {
                throw new \Exception("该订单已申请过退款");
            }
            $data = TradeRefunds::where(['aftersales_bn' => $params['aftersales_bn']])->exists();
            if ($data) {
                throw new \Exception("该售后单已申请过退款");
            }
        }

        return true;
    }


    /**
     * 消费者提交售后申请,商家审核,转售后流程或生成相关退款单
     *
     * @Author hfh_wind
     * @param  $data
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function apply($params)
    {

        TradeAfterRefundService::__check($params);

        $refund_bn = isset($params['refund_bn']) ? $params['refund_bn'] : '';
        $return_freight = isset($params['return_freight']) ? $params['return_freight'] : '';

        //直接取消订单生成退款单
        if ($params['refunds_type'] == 'cancel') {

            $data = self::cancelRefundApply($params['tid'], $params['status'], $params['reason'], $params['shop_id'], $refund_bn, $return_freight);

        } else {

            DB::beginTransaction();
            try {
                //售后退款，如果是商家同意则表示提交到平台进行退款
                if ($params['status'] == '3') {
                    $updateStatusParams['aftersales_bn'] = $params['aftersales_bn'];
                    $updateStatusParams['shop_id'] = $params['shop_id'];
                    $updateStatusParams['status'] = '8';
                    $this->AftersalesStatuspdateupdate($updateStatusParams);
                }

                $refundBn=isset($params['refund_bn'])?$params['refund_bn']:'';
                $data = $this->afsRefundApply($params, $params['tid'], $params['oid'], $refundBn);
                DB::commit();
            } catch (Exception $e) {
                testLog($e->getMessage());
                DB::rollBack();
                throw new \Exception('退款单创建失败' . $e);
            }

        }

        return $data;
    }


    /**
     *
     * 创建退款申请单，商家在需要进行退款处理的时候需要向平台发起退款申请，又平台进行退款处理
     * @Author hfh_wind
     * @param array $data 申请退款数据
     * @param int $tid 订单编号
     * @param int $oid 子订单编号
     * @param $refundBn
     * @return mixed
     */

    public function afsRefundApply($data, $tid, $oid, $refundBn)
    {

        $tradeData=TradeOrder::where(['oid'=>$oid])->first();

        $tradePayment=TradePaybill::where(['tid'=>$tid])->first();

//        $tradeHongbao = 0;
        $paymentTotal = $tradePayment['amount'];

        /*if ($data['total_price'] > $paymentTotal) {
            throw new \LogicException('商品退款金额不能大于付款金额');
        }*/

        if ($data['total_price'] > $tradeData['amount']) {
            throw new \LogicException('商品退款金额不能大于付款金额');
        }

        if ($data['total_price'] < 0.01 && $tradeData['amount']>0) {
            throw new \LogicException('商品退款金额不能小于0.01');
        }


//        $total_price = ecmath::number_minus([$data['total_price'], $orderData['points_fee']]);

        $total_price=$data['total_price'];

        $getRefundBn = new \ShopEM\Services\TradeService();

        $insertData['refund_bn'] = $refundBn ? $refundBn : $getRefundBn->createId('refund_bn');
        $insertData['aftersales_bn'] = $data['aftersales_bn'];
        $insertData['refunds_reason'] = $data['reason'];
//        $insertData['total_price'] = $tradeData['total_fee'];
        $insertData['total_price'] = $tradeData['amount'];
        $insertData['refund_fee'] = $data['total_price'];
        $insertData['order_price'] = $tradeData['amount'];
//        $insertData['hongbao_fee'] = $tradeHongbao;
//        $insertData['user_hongbao_id'] = $tradeData['user_hongbao_id'];
        $insertData['status'] = '3';//商家同意退款，则表示商家审核通过
        $insertData['tid'] = $data['tid'];
        $insertData['refunds_type'] = '0';
        $insertData['oid'] = $oid;
        $insertData['shop_id'] = $data['shop_id'];
        $insertData['user_id'] = $tradeData['user_id'];


        $tradesData = Trade::where(['tid' => $tid])->first();
        $consume_point_fee = 0;
        if ($tradesData->consume_point_fee) {
            if ($tradesData->points_fee && $tradesData->points_fee > 0) {
                $consume_point_fee = TradeService::avgDiscountPoint($tradesData->consume_point_fee, $tradesData->points_fee,
                    $tradeData['avg_points_fee']); //抵扣的积分
            } else {
                $consume_point_fee = $tradesData->consume_point_fee;
            }
        }
        $insertData['consume_point_fee'] = $consume_point_fee;
        $insertData['points_fee'] = $tradeData['avg_points_fee'];
        $insertData['coupon_fee'] = $tradeData['avg_discount_fee'];

        $refundsId = TradeRefunds::create($insertData);

//        if ($total_price == $tradeHongbao) {
//            app::get('sysaftersales')->rpcCall('aftersales.refunds.restore', array('refunds_id' => $refundsId, 'return_fee' => $data['total_price']));
//            $insertData['is_restore'] = true;
//        }

        return $insertData;
    }


    /**
     * 消费者提交退货物流信息
     *
     * @Author hfh_wind
     * @param $params
     * @return bool
     */
    public function AftersalesStatuspdateupdate($params)
    {
        $filter['aftersales_bn'] = $params['aftersales_bn'];
        $filter['shop_id'] = $params['shop_id'];

        $info=TradeAftersales::where($filter)->first();
        if (empty($info)) {
            throw new \LogicException('售后单号不存在');
        }

        //平台退款不能直接接口进行更新
        if (in_array($params['status'], ['6', '7', '0']) || ($params['status'] == '1' && $info['progress'] != '0')) {
            return true;
            //throw new \LogicException(app::get('sysaftersales')->_('更新的状态不存在'));
        }

        $progress = $params['status'];

        //如果是换货售后 OMS完成 那么在多用户商城上则为等待平台处理退款状态
        if ($progress == '4' && $info['aftersales_type'] != 'EXCHANGING_GOODS') {
            $progress = '5';
        }

        if (in_array($progress, ['1', '2', '5', '8'])) {
            $status = '1';
        } elseif ($progress == '4') {
            $status = '2';
            if (empty($params['memo'])) {
                throw new \LogicException('卖家重新发货信息必填');
            }

            $updateData['sendconfirm_data'] = serialize(['return_trade_info' => $params['memo']]);
        } else {
            $status = '3';
        }

        try {
            $updateData['status'] = $status;
            $updateData['progress'] = $progress;
            $result = TradeAftersales::where($filter)->update($updateData);

            //新增售后记录
            $set_data = array(
                'oid'                => $info->oid,
                'tid'                => $info->tid,
                'aftersales_type'    => $info->aftersales_type,
                'progress'           => $progress,
                'status'             => $status,
            );
            $this->setAftersaleTrace($set_data);
        } catch (\Exception $e) {
            throw new \LogicException('更新的状态失败');
        }

        switch ($progress) {
            case '1':
                $params['aftersales_status'] = "WAIT_BUYER_RETURN_GOODS";
                break;
            case '2':
                $params['aftersales_status'] = "WAIT_SELLER_CONFIRM_GOODS";
                break;
            case '3':
                $params['aftersales_status'] = "SELLER_REFUSE_BUYER";
                break;
            case '4':
                $params['aftersales_status'] = "SELLER_SEND_GOODS";
                break;
            case '5':
                $params['aftersales_status'] = "REFUNDING";
                break;
        }

        if (isset($params['aftersales_status'])) {

            $params['oid'] = $info['oid'];
            $params['tid'] = $info['tid'];
            $params['user_id'] = $info['user_id'];
//            app::get('sysaftersales')->rpcCall('order.aftersales.status.update', $params);
        }


        return true;
    }


    /**
     * @Author hfh_wind
     * @param $tid 取消的订单ID
     * @param $status 取消的状态 用户申请取消订单 未审核，商家取消订单，平台取消订单
     * @param $refundsReason 取消订单原因
     * @param $shopId 取消订单的店铺ID
     * @param $refundBn 退款申请单编号
     * @param null $returnFreight 取消订单，是否退还运费
     * @return mixed
     * @throws \Exception
     */

    public function cancelRefundApply($tid, $status, $refundsReason, $shopId, $refundBn, $returnFreight = null)
    {
        $params = ['tid', 'status', 'amount', 'post_fee', 'user_id', 'shop_id', 'points_fee', 'discount_fee', 'consume_point_fee', 'total_fee'];
        $tradeData = Trade::where(['tid' => $tid])->select($params)->first()->toArray();

        if ($tradeData && $shopId && $tradeData['shop_id'] != $shopId) {
            throw new \Exception("参数错误");
        }

        if ($tradeData['status'] == 'WAIT_BUYER_PAY') {
            throw new \Exception("未付款订单不需要退款");
        } elseif (in_array($tradeData['status'], ['TRADE_CLOSED', 'TRADE_CLOSED_BY_SYSTEM'])) {
            throw new \Exception("订单已取消，不需要重复取消");
        }
        //如果不是已付款未发货，则表示订单已发货或者已完成 都不能进行取消订单操作
        //商家强制关单和平台强制关单,则发货的可以取消
        elseif (!in_array($status, ['5', '6']) && $tradeData['status'] != 'WAIT_SELLER_SEND_GOODS') {
            throw new \Exception("已发货订单不能直接退款");
        }
        $getRefundBn = new \ShopEM\Services\TradeService();
        //优惠分摊
        /*$paymentInfo = Payment::where(['payment_id' => $tradeData['payment_id']])->first();
        $total = $paymentInfo->amount + $paymentInfo->points_fee;
        $points_fee = $getRefundBn->avgDiscountFee($total, $paymentInfo->points_fee, $tradeData['amount']); //积分抵扣金额*/

        $insertData['refund_bn'] = $refundBn ? $refundBn : $getRefundBn->createId('refund_bn');
        $insertData['user_id'] = $tradeData['user_id'];
        $insertData['shop_id'] = $shopId;
        $insertData['tid'] = $tid;
        $insertData['refunds_type'] = '1';
        $insertData['status'] = $status;
        $insertData['refunds_reason'] = $refundsReason;
        $insertData['refund_fee'] = $tradeData['amount'];
        $insertData['total_price'] = $tradeData['amount'];
//        $insertData['total_price'] = $tradeData['total_fee'];
        $insertData['order_price'] = $tradeData['amount'];
        $insertData['points_fee'] = $tradeData['points_fee'];
        $insertData['coupon_fee'] = $tradeData['discount_fee'];
        $insertData['consume_point_fee'] = $tradeData['consume_point_fee'];

        $insertData['return_freight'] = '2';

        if ($tradeData['status'] == "WAIT_BUYER_CONFIRM_GOODS") {
            if ($returnFreight == "false") {
                $insertData['refund_fee'] = $tradeData['amount'] - $tradeData['post_fee'];
                $insertData['total_price'] = $insertData['total_price'] - $tradeData['post_fee'];
                $insertData['return_freight'] = '3';
            }
            $insertData['refunds_type'] = '2';
        }

        $refundsId = TradeRefunds::create($insertData);

        if ($refundsId) {
            $insertData['is_restore'] = true;
        }
        //如果是商家取消订单或者平台取消订单，并且红包全额支付，则直接退款成功，退还红包

        return $insertData;
    }


    /**
     * 平台对退款申请进行退款处理
     *
     * @Author hfh_wind
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function refundApplyRestore($params)
    {
        $filter = ['id' => $params['id']];
        $refunds = TradeRefunds::where($filter)->first()->toArray();
        // 如果已经是完成状态，直接返回true，不做其他操作
        if ($refunds['status'] == '1') {
            return true;
        }

        DB::beginTransaction();
        try {

            //更新退款申请单
            $params['status'] = "1";
            //新增退款时间
            $params['refund_at'] = Carbon::now()->toDateTimeString();
            $result = TradeRefunds::where($filter)->update($params);
            if (!$result) {
                throw new \LogicException('退款申请单更新失败');
            }

            //如果为售后，则更新售后单状态
            if ($refunds['refunds_type'] == '0')//退款类型，售后退款
            {
                $refundFee = $params['refund_fee'];
                $this->__afsRefundSucc($refunds, $refundFee);
            } else//取消订单退款
            {
                //取消退款成功后，更新取消成功后操作
                // $trade = new \ShopEM\Services\TradeService();
                // $trade->__cancelSuccDo($refunds['tid'],$refunds['shop_id']);

                //更新取消订单的状态
                $this->succdo($refunds);

                // 如果是拒收订单，需要生成结算明细  生成报表以后有需求相关再说
            }
        } catch (logicexception $e) {
            DB::rollback();
            throw new \logicexception($e->getMessage());
        }
        DB::commit();

        return true;
    }


    /**
     * 取消订单,退款成功后的操作
     *
     * @Author hfh_wind
     * @param $params
     * @return bool
     */

    public function succdo($params)
    {
        //取消订单

        DB::beginTransaction();
        try {
            $this->cancelSuccDo($params['tid'], $params['shop_id'], '');

            DB::commit();
        } catch (LogicException $e) {
            DB::rollback();

            throw new \logicexception($e->getMessage());
        }

        return true;
    }


    /**
     * 取消订单，退款成功后的操作
     *
     * @Author hfh_wind
     * @param $tid
     * @param $shopId
     * @param $refundHongbaoFee
     * @return bool
     * @throws \Exception
     *
     */
    public function cancelSuccDo($tid, $shopId, $refundHongbaoFee = '')
    {
        $data = TradeCancel::where(['tid' => $tid])->select('shop_id', 'user_id', 'cancel_from', 'cancel_id')->where('refunds_status', 'WAIT_REFUND')->orderBy('id', 'desc')->first();

        if (empty($data)) {
            throw new \Exception("此取消订单不存在");
        }
        if ($data->shop_id != $shopId) {
            throw new \Exception("参数错误");//当前用户和取消记录中存储的用户ID不一致
        }

        //更新取消订单记录状态 退款状态未等待退款
        $updateData['refunds_status'] = 'SUCCESS';
        $updateData['process'] = '3';
        TradeCancel::where(['cancel_id' => $data->cancel_id])->update($updateData);

        // app::get('systrade')->model('trade_cancel')->update(['refunds_status' => 'SUCCESS', 'process' => '3'], ['cancel_id' => $data->cancel_id]);
        $trade = new \ShopEM\Services\TradeService();
        //取消订单成功后处理事件,回库存解冻等等
        $trade->__cancelSuccDo($tid, $shopId);

//        $logText = '取消订单成功，退款已处理！';
//        $this->__addLog($data['cancel_id'], $logText, $shopId, 'shopadmin');

        return true;
    }


    /**
     * 售后退款
     *
     * @Author hfh_wind
     * @param $refunds
     * @param $refundFee
     * @return bool
     */

    private function __afsRefundSucc($refunds, $refundFee)
    {

        $aftersales = TradeAftersales::where(['aftersales_bn' => $refunds['aftersales_bn']])->select('aftersales_type','progress','status','tid','oid','user_id','shop_id','gm_id')->first();
        if ($aftersales['tid'] != $refunds['tid'] || $aftersales['oid'] != $refunds['oid'] || $aftersales['user_id'] != $refunds['user_id'] || $aftersales['shop_id'] != $refunds['shop_id']) {
            throw new \LogicException('数据有误，请重新处理');
        }

        if (in_array($aftersales['progress'], ['3', '4', '6', '7']) || in_array($aftersales['status'], ['2', '3'])) {
            throw new \LogicException('当前处理异常，无法处理');
        }

        $afterparams['progress'] = '7';
        $afterparams['status'] = '2';
        $afterFilter['aftersales_bn'] = $refunds['aftersales_bn'];
        $result = TradeAftersales::where($afterFilter)->update($afterparams);
        if (!$result) {
            throw new \LogicException('售后单状态更新失败');
        }

        //新增售后记录
        $set_data = array(
            'oid'                => $aftersales['oid'],
            'tid'                => $aftersales['tid'],
            'aftersales_type'    => $aftersales['aftersales_type'],
            'progress'           => $afterparams['progress'],
            'status'           => $afterparams['status'],
        );
        $this->setAftersaleTrace($set_data);

        try {
            //更新字订单售后状态
            $orderparams['oid'] = $refunds['oid'];
            $orderparams['tid'] = $refunds['tid'];
            $orderparams['user_id'] = $refunds['user_id'];
            $orderparams['aftersales_status'] = 'SUCCESS';
            $orderparams['refund_fee'] = $refundFee;
            $orderparams['total_fee'] = $refunds['total_price'];
            $orderparams['gm_id'] = $refunds['gm_id'];
            $this->AfterSalesStatusUpdate($orderparams);

//            $this->__rollbackHongbao($refunds);
        } catch (Exception $e) {
            throw new \LogicException($e->getMessage());
        }

        return true;
    }


    /**
     * 订单售后状态处理(未完成订单退款成功时更新订单状态为关闭)
     * @Author hfh_wind
     * @param $params
     * @return bool
     * @throws \Exception
     */
    public function AfterSalesStatusUpdate($params)
    {

        $ifAll = false;
        $orderData = array();
        $tradesData = array();

        //数据监测
        try
        {
            $data = $this->check($params,$orderData,$tradesData,$ifAll);
        }
        catch(\LogicException $e)
        {
            throw new \LogicException($e->getMessage());
        }

        DB::beginTransaction();

        try
        {
            //当平台退款完成时需要改变订单状态
            if($ifAll && $tradesData && ($tradesData['status'] != "TRADE_FINISHED" || $tradesData['pick_type'] == 1))
            {
                $updataTradeData['status'] = "TRADE_CLOSED";
                $updataTradeData['tid'] = $params['tid'];
                $updataTradeData['cancel_reason'] = "退款成功，交易自动关闭";
                $result = Trade::where(['tid'=>$params['tid']])->update($updataTradeData);
                if(!$result)
                {
                    throw new \LogicException('退款失败，关闭订单异常');
                }
            }
            else
            {
                //售后退款后需要改变订单的最后修改时间
                Trade::where(['tid'=>$params['tid']])->update(['end_time'=>date("Y-m-d H:i:s",time())]);
            }

            //子订单售后状态改变
            //售后不改变订单状态
            $result = TradeOrder::where(['oid'=>$params['oid']])->update($data);
            if(!$result)
            {
                throw new \LogicException('退款失败，子订单状态更新失败');
            }

            if($orderData)
            {
                if($orderData['status'] == "TRADE_FINISHED")
                {
                    $orderData['refund_fee'] = $params['total_fee'];
                    //处理积分回扣和经验值回扣(订单完成)
//                    $this->__PointAndExp($orderData);
                    //只有在订单完成后的售后退款才需要进行，结算退款处理
//                    $this->__settlement($tradesData, $orderData);
                }
                $orderData['refund_fee'] = $params['total_fee'];

                // 恢复、解冻库存
                $arrParam = array(
                    'goods_id' => $orderData['goods_id'],
                    'sku_id' => $orderData['sku_id'],
                    'quantity' => $orderData['quantity'],
//                        'sub_stock' => $orderData['sub_stock'],
                    'tradePay' => 1,//是否支付
                    'oid' => $orderData['oid'],
                    'shop_id' => $orderData['shop_id'],
                );
                $goodStore = new \ShopEM\Services\GoodsService();
                $isRecover = $goodStore->storeRecover($arrParam);
                if(!$isRecover)
                {
                    throw new \LogicException('退款失败，恢复库存失败');
                }
                $tradeService = new TradeService();

                //推送erp
                TradePushErp::dispatch($params['oid'],'refund');

                //记录crm积分推送，目前使用的是crm的比例 10:1
                $pointLog = array(
                    'gm_id'     => $params['gm_id'],
                    'amount'    => $orderData['refund_fee'],
                    'user_id'   => $params['user_id'],
                    'remark'    => "订单号： " . $params['tid'],
                    // 'behavior'  => '订单退款扣减赠送积分',
                    'behavior'  => '扣减订单赠送积分',
                    'type'      => 'consume'
                );
                $log_id = $tradeService->crmPushPoint($pointLog);


                $store_code = Shop::where('id',$orderData['shop_id'])->value('store_code');
                RefundTradePush::dispatch([
                    'storeCode'           => $store_code,                     //门店编码
                    'originTransDate'     => $orderData['pay_time'],          //原交易日期
                    'receiptno'           => $params['oid'],                  //新退货单号
                    'org_receiptno'       => $params['tid'],                  //原小票号
                    'returnamount'        => $orderData['refund_fee'],        //退货金额
                    'forcereturnoption'   => 0,                               //系统参数(forcereturnoption=1),则允许在界面上选择强制退货，否则提示积分不足不允许退货
                    'receiptDate'         => $orderData['consign_time'] ?? date('Y-m-d',time()),             //退货日期
                    'log_id'              => $log_id,
                ]);
                //返回使用积分购买的订单
                $tradeService->__yitianPoint($orderData);

            }
            DB::commit();
        }
        catch(\Exception $e)
        {
            DB::rollback();
            throw new \LogicException($e->getMessage());
        }


        return true;
    }


    /**
     * 检测数据
     * @Author hfh_wind
     * @param $params
     * @param $orderData
     * @param $tradesData
     * @param $ifAll
     * @return bool
     */
    private function check($params,&$orderData,&$tradesData,&$ifAll)
    {

        if($params['aftersales_status'] == "SUCCESS")
        {
            //红包全额付款不需要退还金额，不需要判断
            //if(!$params['refund_fee']) throw new \LogicException(app::get('systrade')->_('数据有误'));
            $ifAll = true;

            $orders = TradeOrder::where(['tid'=>$params['tid']])->get();

            foreach($orders as $key=>$order)
            {
                if($order['user_id'] != $params['user_id'])
                {
                    throw new \LogicException('数据有误，请重新处理');
                    return false;
                }

                if( $order['oid'] != $params['oid'] && $order['after_sales_status'] != 'SUCCESS')
                {
                    $ifAll = false;
                }

                if($order['oid'] == $params['oid'])
                {
                    $orderData = $order;
                }

                if($order['oid'] == $params['oid'] && $order['status'] != "TRADE_FINISHED")
                {
                    $data['status'] = "TRADE_CLOSED_AFTER_PAY";
                    $data['refund_fee'] = $params['refund_fee'];
                }
            }
        }

        $tradesData = Trade::where(['tid'=>$params['tid']])->first();
        $data['after_sales_status'] = $params['aftersales_status'];
        return $data;
    }





    /**
     *更新退款申请单状态
     *
     * @Author hfh_wind
     * @param $params
     * @return mixed
     */

    public function refundApplyShopReply($params)
    {
        if ($params['refunds_id']) {
            $filter['refunds_id'] = $params['refunds_id'];
        } elseif ($params['aftersales_bn']) {
            $filter['aftersales_bn'] = $params['aftersales_bn'];
        } elseif ($params['tid']) {
            $filter['tid'] = $params['tid'];
            $filter['refunds_type'] = '1';
        } else {
            throw new \LogicException('参数错误');
        }


        $status = $params['status'];

        $data = TradeRefunds::where($filter)->first()->toArray();
        if ($data['status'] != '0') {
            throw new \LogicException('该退款申请已审核，不需要重新审核');
        }

        $refundsfilter['shop_id'] = $params['shop_id'];
        $refundsfilter['refunds_id'] = $data['refunds_id'];
        $result = TradeRefunds::where($refundsfilter)->update(['status' => $status]);

        return $data;
    }


    /**
     * 生成退款单记录(记录平台是否已经退款)
     *
     * @Author hfh_wind
     * @param $params 退款单所需参数
     * @return array|bool
     */
    public function createTradeRefundLog($params)
    {
        if ($params['refund_type'] == 'offline') {
            $data['status'] = 'succ';
//            $data['refund_type'] = 'offline';
            $data['pay_app'] = 'offline';
            $data['finish_time'] = Carbon::now()->toDateTimeString();

            $data['refund_people'] = $params['refund_people'];
            $data['refund_bank'] = $params['refund_bank'];
            $data['refund_account'] = $params['refund_account'];

            $data['beneficiary'] = $params['beneficiary'];
            $data['receive_bank'] = $params['receive_bank'];
            $data['receive_account'] = $params['receive_account'];
        } elseif ($params['refund_type'] == 'online') {
            $data['status'] = 'ready';
            $data['type'] = 'online';
            $paymentInfo = Payment::where(['payment_id' => $params['payment_id']])->select('pay_app')->first();
            $data['pay_app'] = $paymentInfo->pay_app;
        }

        if ($params['money'] == 0) {
            $data['status'] = 'succ';
            $data['memo'] = '退款金额为0元，直接退款状态为succ';
        }

        $data['money'] = $params['money'];
        $data['cur_money'] = $params['money'];
        $data['op_id'] = !empty($params['op_id']) ? $params['op_id'] : 0;
        $data['type'] = $params['refund_type'];
        $data['refunds_type'] = $params['refunds_type'];
        $data['aftersales_bn'] = !empty($params['aftersales_bn']) ? $params['aftersales_bn'] : '';

        $data['oid'] = !empty($params['oid']) ? $params['oid'] : '';
        $data['tid'] = $params['tid'];

        $data['return_fee'] = $params['return_fee'];//由于现在代码逻辑暂时存储，方便第三方退款后更新其他api
        $data['refunds_id'] = $params['refunds_id'];//由于现在代码逻辑暂时存储，方便第三方退款后更新其他api
//dd($data);
        $result = TradeRefundLog::create($data);
        if (!$result) {
            throw new \LogicException("创建退款单失败");
        }
        return $result;
    }

    /**
     * 第三方支付方式退款(原路退回专用)
     *
     * @Author hfh_wind
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function refundPay($params)
    {

        $paymentData = Payment::where(['payment_id'=>$params['payment_id']])->select('payment_id','trade_no','status','amount','pay_app','user_id')->first();

        if(!$paymentData || $paymentData->status != 'succ')
        {
            throw new \LogicException('请检查订单对应的支付单号是否存在且已支付成功，否则不能退款');
        }

        if(!$paymentData->trade_no && $paymentData->pay_app  !='deposit')
        {
            throw new \LogicException('支付失败，没有第三方支付交易号，请选择线下方式退款');
        }
        $refundTrade = TradeRefunds::find($params['refund_id']->refunds_id);
        $payData = [
            'user_id'   => $paymentData->user_id,
            'trade_no' => $paymentData->trade_no,
            'refund_fee' => $params['money'],
            'refund_bn' => $refundTrade->refund_bn,
            'total_fee' => $paymentData->amount,
            'payment_id' => $paymentData->payment_id,
            'pay_app' => $paymentData->pay_app,
            'pay_type' => 'refund', //此参数一定不能少，判断是否是退款操作
//            'pay_type' => 'refund',
        ];

        $pay= new  \ShopEM\Services\PayToolService;
        $result = $pay->dopay($payData,$paymentData->pay_app);
        if(!$result)
        {
            throw new \LogicException('支付失败,请求支付网关出错');
        }

        switch ($result['status'])
        {
            case 'succ':
            case 'progress':
                $isUpdatedPay =TradeRefundLog::where(['id'=> $params['refund_id']->id ])->update(['status'=>$result['status']]);
                break;
            case 'failed':
                $isUpdatedPay = TradeRefundLog::where(['id'=> $params['refund_id']->id ])->update(['status'=>'failed']);
                break;
        }
        return $result;
    }



    /**
     * 生成退款记录refund_id
     *
     * @Author hfh_wind
     * @return string
     */
    private function genId()
    {
        $now = time();
        $startTime = 1325347200;//2012-01-01 做为初始年
        //当前时间相距初始年的天数，4为可使用20年
        $day = floor(($now - $startTime) / 86400);
        //当天从0秒开始到当前时间的秒数 总数为86400
        $second = $now - strtotime(date('Y-m-d'));

        $base = $day . str_pad($second, 5, '0', STR_PAD_LEFT);//9位
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);//3位
        return $base . $random;
    }

    /**
     * 记录售后状态
     * @Author huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    private function setAftersaleTrace($set_data)
    {
        try
        {
            TradeAftersaleLog::create($set_data);
            return true;
        }
        catch (\Exception $e)
        {
            throw new \LogicException($e->getMessage());
        }

    }

}
