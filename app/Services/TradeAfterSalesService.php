<?php
/**
 * @Filename TradeAfterSalesService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeAftersaleLog;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradeAftersales;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\TradeRefunds;
use ShopEM\Models\TradeRelAftersale;

class TradeAfterSalesService
{

    /**
     * 获取子订单是否可进行退换货
     *
     * @Author hfh_wind
     * @param $params 子订单信息
     * @return mixed
     */
    public function isAftersalesEnabled($order)
    {
        $pagedata = [];
        //strtotime($order['end_time'])
        //七天无理由
        //refund_active  退货是否开启  changing_active  换货是否开启
        $aftersalesSetting = [
            'refund_days'     => '7',
            'changing_days'   => '7',
            'refund_active'   => true,
            'changing_active' => true
        ];
        if (!$aftersalesSetting['refund_active']) {
            $pagedata['refund_enabled'] = true;
        } elseif ($aftersalesSetting['refund_days'] != 0) {
            $refund_endtime = strtotime("+" . $aftersalesSetting['refund_days'] . " days",
                strtotime($order['end_time']));

            if ($refund_endtime > time()) {
                $pagedata['refund_enabled'] = true;
            }
        }

        if (!$aftersalesSetting['changing_active']) {
            $pagedata['changing_enabled'] = true;
        } elseif ($aftersalesSetting['changing_days'] != 0) {
            $changing_endtime = strtotime("+" . $aftersalesSetting['changing_days'] . " days",
                strtotime($order['end_time']));
            if ($changing_endtime > time()) {
                $pagedata['changing_enabled'] = true;
            }
        }

        if ($order['complaint_status'] == 'FINISHED' && $order['after_sales_status'] == "SELLER_REFUSE_BUYER") {
            $pagedata['refund_enabled'] = true;
            $pagedata['changing_enabled'] = true;
        }

        return $pagedata;
    }


    /**
     * 申请售后服务具体实现接口
     *
     * @Author hfh_wind
     * @param $params
     * @return bool
     * @throws \Exception
     */
    public function aftersalesCreate($params)
    {

        DB::beginTransaction();
        try {
            $result = $this->create($params);
            if (!$result) {
                DB::rollback();
                return false;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        return true;
    }


    /**
     * 消费者提交售后申请
     *
     * @param array $data 创建售后申请提交的参数
     */
    public function create($data)
    {
        $getTrade = new \ShopEM\Services\TradeService();
        $tradeInfo = $getTrade->__getTradeInfo($data['tid']);

        $tradeorder = TradeOrder::where(['oid' => $data['oid']])->first()->toArray();

        if ($data['apply_refund_price'] > $tradeorder['amount']) {
            throw new \LogicException('申请退款金额不能大于实付金额');
        }

        $apply_type = $data['apply_type'] ?? false;

        $this->__checkApply($tradeInfo, $data);

        if (!empty($tradeorder['gift_data']) && $data['aftersales_type'] == "REFUND_GOODS") {
            $saveData['gift_data'] = $tradeorder['gift_data'];
        }

        $saveData['aftersales_bn'] = $getTrade->createId('aftersales_bn');

        $saveData['user_id'] = $data['user_id'];
        $saveData['shop_id'] = $tradeInfo['shop_id'];
        $saveData['aftersales_type'] = $data['aftersales_type'];
        $saveData['reason'] = $data['reason'];
        $saveData['tid'] = $data['tid'];
        $saveData['oid'] = $data['oid'];
        $saveData['goods_id'] = $tradeorder['goods_id'];
        $saveData['sku_id'] = $tradeorder['sku_id'];
        $saveData['evidence_pic'] = !empty($data['evidence_pic']) ? implode(',', $data['evidence_pic']) : '';
        $saveData['description'] = $data['description'];
        $saveData['title'] = $tradeorder['goods_name'];
        $saveData['num'] = $tradeorder['quantity'];
        $saveData['goods_barcode'] = $tradeorder['goods_barcode'] ?? '';
        $saveData['apply_refund_price'] = $data['apply_refund_price'];

        DB::beginTransaction();
        try {
            if($apply_type == 'apply_again')
            {
                $after_data = TradeAftersales::where('oid',$data['oid'])->first();
                $rel_data['aftersales_bn'] = $saveData['aftersales_bn'];
                $rel_data['aftersales_type'] = $saveData['aftersales_type'];
                //原数据
                $rel_data['or_bn'] = $after_data->aftersales_bn;
                $rel_data['or_aftersales_type'] = $after_data->aftersales_type;
                $rel_data['or_reason'] = $after_data->reason;
                $rel_data['sku_id'] = $after_data->sku_id;
                $rel_data['or_description'] = $after_data->description;
                $rel_data['or_num'] = $after_data->num;
                $rel_data['or_status'] = $after_data->status;
                $rel_data['title'] = $after_data->title;
                $rel_data['or_sendback_data'] = $after_data->sendback_data ?? '';
                $rel_data['or_sendconfirm_data'] = $after_data->sendconfirm_data ?? '';
                $rel_data['or_shop_explanation'] = $after_data->shop_explanation ?? '';
                $rel_data['or_gift_data'] = $after_data->gift_data ?? '';
                $rel_data['tid'] = $saveData['tid'];
                $rel_data['oid'] = $saveData['oid'];
                $rel_data['aftersales_number'] = TradeRelAftersale::where('or_bn', $after_data->aftersales_bn)->count()+1;
                $result = TradeRelAftersale::create($rel_data);
                if (!$result) {
                    throw new \LogicException('售后单创建失败');
                }
                //重置记录
                $update_data['aftersales_type'] = $saveData['aftersales_type'];
                $update_data['apply_refund_price'] = $saveData['apply_refund_price'];
                $update_data['description'] = $saveData['description'];
                $update_data['title'] = $saveData['title'];
                $update_data['num'] = $saveData['num'];
                $update_data['goods_barcode'] = $saveData['goods_barcode'];
                $update_data['reason'] = $saveData['reason'];
                $update_data['progress'] = '0';
                $update_data['status'] = '0';
                $update_data['after_state'] = '0';
                TradeAftersales::where('aftersales_bn',$after_data->aftersales_bn)->update($update_data);
            }
            else
            {
                $result = TradeAftersales::create($saveData);
                if (!$result) {
                    throw new \LogicException('售后单创建失败');
                }
            }

            $set_data = array(
                'oid'                => $saveData['oid'],
                'tid'                => $saveData['tid'],
                'aftersales_type'    => $saveData['aftersales_type'],
                'progress'           => '0',
                'status'           => '0',
                'mes'           => $saveData['reason'],
            );
            $this->setAftersaleTrace($set_data);

            $params = array(
                'oid'                => $data['oid'],
                'tid'                => $data['tid'],
                'user_id'            => $saveData['user_id'],
                'after_sales_status' => 'WAIT_SELLER_AGREE',
                'tradesData'         => $tradeInfo,
            );

            try {
                //子订单售后状态更新
                $getTrade->afterSaleOrderStatusUpdate($params);

                $orderFilter = array('oid' => $data['oid'], 'user_id' => $data['user_id'], 'tid' => $data['tid']);
                // 更改子订单投诉状态
                TradeOrder::where($orderFilter)->update(['complaint_status' => 'NOT_COMPLAINTS']);
            } catch (\LogicException $e) {
                throw new \LogicException($e->getMessage());
            }
            DB::commit();
        } catch (\LogicException $e) {
            DB::rollback();
            throw new \LogicException($e->getMessage());
        }

        return true;
    }


    /**
     * 检查售后申请的订单是否合法
     *
     * @Author hfh_wind
     * @param array $tradeInfo 申请售后的订单数据
     * @param array $data 申请的参数
     * @return bool
     */
    private function __checkApply($tradeInfo, $data)
    {

        $aftersalesInfo = TradeAftersales::where(['oid' => $data['oid']])->select('aftersales_bn','status')->first();

        // 判断是否通过平台申诉
        $flag = false;

        $tradeFilter = array('oid' => $data['oid'], 'user_id' => $data['user_id'], 'tid' => $data['tid']);
        $rs = TradeOrder::where($tradeFilter)->select('complaint_status','refund_fee','gm_id', 'shop_id')->first();

        $store_code = \ShopEM\Models\Shop::where('id',$rs->shop_id)->value('store_code');
        //检查积分是否足够
        if ($data['aftersales_type'] == 'REFUND_GOODS') {
            $info  = [
                'storeCode'           => $store_code,
                'originTransDate'     => $tradeInfo['pay_time'],
                'receiptno'           => $data['oid'],
                'org_receiptno'       => $tradeInfo['tid'],
                'returnamount'        => $rs->refund_fee,
                'forcereturnoption'   => 0,
                'receiptDate'         => $tradeInfo['consign_time'] ?? date('Y-m-d',time()),
            ];
            $yitianGroupServices = new YitianGroupServices($rs->gm_id);
            $res = $yitianGroupServices->returnedPurchaseCheck($info,$data['user_id']);
            if (isset($res['pointEnough']) && $res['pointEnough'] == 0) {
                throw new \LogicException('积分不足,无法申请');
            }
        }


        if ($rs && $rs->complaint_status == 'FINISHED') {
            $flag = true;
        }

//        if ($aftersalesInfo && !$flag) {
//            throw new \LogicException('已申请过售后，不需要再进行申请');
//        }
        if($aftersalesInfo && !$flag)
        {
            if (in_array($aftersalesInfo->status,[0,1]))
            {
                throw new \LogicException('该订单正在售后中,请勿重复提交!');
            }
        }

        if (empty($data['reason'])) {
            throw new \LogicException('售后理由必选');
        }

        if (!$tradeInfo) {
            throw new \LogicException('申请的订单不存在');
        }

        if ($tradeInfo['user_id'] != $data['user_id']) {
            throw new \LogicException('申请的订单编号无权访问');
        }

        $repository = new \ShopEM\Repositories\ConfigRepository;
        $config = $repository->configItem('shop', 'trade', $rs->gm_id);
        //根据订单状态判断是否申请售后的类型是否可以可以进行申请
        switch ($data['aftersales_type']) {
            case 'REFUND_GOODS'://退货退款
                $end_day = 0;
                //判断是否开启了退货时间限定
                if (isset($config['open_aftersalse_refund']) && $config['open_aftersalse_refund']['value']) {
                    if (isset($config['aftersalse_refund_day']) && $config['aftersalse_refund_day']['value']) {
                        $day = '-'.$config['aftersalse_refund_day']['value'].' day';
                        $end_day = date('Y-m-d H:i:s', strtotime($day));

                        if ($tradeInfo['end_time'] && $tradeInfo['end_time'] < $end_day) {
                            throw new \LogicException('超过售后退货退款服务时间');
                        }
                    }
                }
                break;
            case 'EXCHANGING_GOODS'://换货
                $end_day = 0;
                //判断是否开启了换货时间限定
                if (isset($config['open_aftersalse_changing']) && $config['open_aftersalse_changing']['value']) {
                    if (isset($config['aftersalse_changing_day']) && $config['aftersalse_changing_day']['value']) {
                        $day = '-'.$config['aftersalse_changing_day']['value'].' day';
                        $end_day = date('Y-m-d H:i:s', strtotime($day));
                        if ($tradeInfo['end_time'] && $tradeInfo['end_time'] < $end_day) {
                            throw new \LogicException('超过售后退换货服务时间');
                        }
                    }
                }
                if ($tradeInfo['status'] != 'WAIT_BUYER_CONFIRM_GOODS' && $tradeInfo['status'] != "TRADE_FINISHED") {
                    throw new \LogicException('该商品不能申请退换货');
                }
                break;
            default://默认为只退款
                if ($tradeInfo['status'] == 'WAIT_BUYER_PAY') {
                    throw new \LogicException('该商品不能申请退款');
                }
                break;
        }

        return true;
    }


    /**
     * 获取单条申请售后服务信息
     *
     * @Author hfh_wind
     * @param $params
     * @return mixed
     */
    public function AfterSalesGetData($params)
    {
        $filter['aftersales_bn'] = $params['aftersales_bn'];
        if (!empty($params['shop_id'])) {
            $filter['shop_id'] = $params['shop_id'];
        }
        if (!empty($params['user_id'])) {
            $filter['user_id'] = $params['user_id'];
        }

        $aftersalesInfo = $this->getAftersalesInfo($filter);

        return $aftersalesInfo;
    }


    /**
     * 获取单条售后数据
     *
     * @Author hfh_wind
     * @param int $aftersalesBn 售后编号
     *
     * @return array 根据售后编号返回需要的数据，如果编号不存在则返回空数组
     */
    public function getAftersalesInfo($filter)
    {
        if (!$filter['aftersales_bn']) {
            throw new \LogicException('售后编号不能为空');
        }

        $aftersalesInfo = TradeAftersales::where($filter)->first();
        if (!$aftersalesInfo) {
            throw new \LogicException('查询的售后单无效');
            return false;
        }
        $aftersalesInfo = $aftersalesInfo->toArray();

        if (in_array($aftersalesInfo['status'], [1, 2]) && in_array($aftersalesInfo['progress'], [8, 7])) {
            $params = array(
                'aftersales_bn' => $aftersalesInfo['aftersales_bn'],
                'oid'           => $aftersalesInfo['oid'],
                'refunds_type'  => "0",
            );

            $refunds = TradeRefunds::where($params)->first();

            $refunds['refund_money'] = isset($refunds['refund_fee']) ? $refunds['refund_fee'] : 0;
            $aftersalesInfo['refunds'] = $refunds;
        }

        $aftersalesInfo['trade'] = TradeOrder::where(['oid' => $aftersalesInfo['oid']])->first();
        if ($aftersalesInfo['trade']) {
            $aftersalesInfo['trade'] = $aftersalesInfo['trade']->toArray();
        }

        $aftersalesInfo['sendback_data'] = $aftersalesInfo['sendback_data'] ? unserialize($aftersalesInfo['sendback_data']) : null;

        $aftersalesInfo['sendconfirm_data'] = $aftersalesInfo['sendconfirm_data'] ? unserialize($aftersalesInfo['sendconfirm_data']) : null;

        $send_data = TradeRelAftersale::where('oid',$aftersalesInfo['oid'])->select('or_sendconfirm_data', 'or_sendback_data')->get()->toArray();

        if($send_data)
        {
            foreach ($send_data as $key => $value){
                if($value['or_sendconfirm_data'])
                {
                    $aftersalesInfo['or_sendconfirm_data'][$key] = $value['or_sendconfirm_data'] ? unserialize($value['or_sendconfirm_data']) : null;
                }
                else
                {
                    $aftersalesInfo['or_sendconfirm_data'] = null;
                }
                if($value['or_sendback_data'])
                {
                    $aftersalesInfo['or_sendback_data'][$key] = $value['or_sendback_data'] ? unserialize($value['or_sendback_data']) : null;
                }
                else
                {
                    $aftersalesInfo['or_sendback_data'] = null;
                }
            }
        }

        return $aftersalesInfo;
    }


    /**
     * 商家审核售后服务
     *
     * @Author hfh_wind
     * @param $params
     * @return string
     */

    public function afterSalesVerification($params)
    {
        if (isset($params['shop_explanation']) && empty($params['shop_explanation'])) {
            throw new \LogicException('处理说明必填');
        }

        $filter['aftersales_bn'] = $params['aftersales_bn'];
        $filter['shop_id'] = $params['shop_id'];

        $refundsData['aftersales_bn'] = $params['aftersales_bn'];

        $refunds_reason = isset($params['refunds_reason']) ? $params['refunds_reason'] : '';

        if ($params['check_result'] == 'true') {
//            $refundsData['total_price'] = $params['total_price'];
            $refundsData['refunds_reason'] = isset($params['refunds_reason']) ? $params['refunds_reason'] : '';
            $refundsData['total_price'] = $params['total_price'];
            $refundsData['refund_point'] = $params['refund_point'] ?? 0;
        }

        $shop_explanation = isset($params['shop_explanation']) ? $params['shop_explanation'] : '';

        try {
            $result = $this->doValidation($filter, $params['check_result'], $shop_explanation, $refundsData,
                $params['shop_id'], $refunds_reason);

        } catch (\Exception $e) {
            throw new \LogicException($e->getMessage());
        }

        return 'true';
    }


    /**
     * 商家审核消费者提交的售后申请
     *
     * @param string $filter 售后编号
     * @param bool $result 审核结果
     * @param string $explanation 审核处理说明
     * @param array $refundsData 如果是审核退款申请，为提交的退款数据
     */
    public function doValidation(
        $filter,
        $result,
        $explanation = null,
        $refundsData = null,
        $shopId,
        $refundsReason = null,
        $refundsShutSend = false
    ) {
        $info = TradeAftersales::where($filter)->first()->toArray();

//        kernel::single('sysaftersales_verify')->checkPermission($info, 'seller',$shopId);

        if (!in_array($info['progress'], ['0', '2']) && !$refundsShutSend) {
            throw new \LogicException('审核的售后编号已处理');
        }

        $updateData['shop_explanation'] = $explanation;

        //拒绝售后申请
        if ($result == 'false') {
            $updateData['progress'] = '3';
            $updateData['status'] = '3';
            $updateData['after_state'] = 2;
            $op_status = 0;
        } else {
            //同意售后申请
            $updateData['status'] = 1;//处理状态为正在处理
            $updateData['progress'] = '1';

            if ($info['aftersales_type'] == 'ONLY_REFUND' || ($info['progress'] == '2' && $info['aftersales_type'] == 'REFUND_GOODS')) {
                $refundsData['user_id'] = $info['user_id'];
                $refundsData['shop_id'] = $info['shop_id'];
                $refundsData['oid'] = $info['oid'];
                $refundsData['tid'] = $info['tid'];
                $refundsData['reason'] = $info['reason'];
                $refundsData['refunds_type'] = 'aftersalse';
                $refundsData['status'] = '3';

                //kernel::single('sysaftersales_refunds')->afsRefundApply($refundsData, $info['tid'], $info['oid']);

                $after = new \ShopEM\Services\TradeAfterRefundService();
                $refundData = $after->apply($refundsData);

                //is_restore 表示已经退款完成
                //在红包全额支付的情况下，不需要平台退款，创建好退款申请单后直接进行了退款
                if (isset($refundData['is_restore'])) {
                    $updateData['status'] = 2;//处理状态为已处理
                    $updateData['progress'] = '7'; //处理进度为，已退款
                } else {
                    //生成退款申请单到平台
                    $updateData['progress'] = '8'; //处理进度为，等待平台处理
                }
            }
        }

        if ($refundsReason) {
            $updateData['refunds_reason'] = $refundsReason;
        }

        $result = TradeAftersales::where($filter)->update($updateData);
        if (!empty($refundData['is_restore'])) {
            return $info;
        }

        switch ($updateData['progress']) {
            case '8':
                $params['after_sales_status'] = "REFUNDING";
                break;
            case '1':
                $params['after_sales_status'] = "WAIT_BUYER_RETURN_GOODS";
                break;
            case '3':
                $params['after_sales_status'] = "SELLER_REFUSE_BUYER";
                break;
        }

        $params['oid'] = $info['oid'];
        $params['tid'] = $info['tid'];
        $params['user_id'] = $info['user_id'];

        $getTrade = new \ShopEM\Services\TradeService();
        $tradeInfo = $getTrade->__getTradeInfo($params['tid']);

        $params['tradesData'] = $tradeInfo;
        //子订单售后状态更新
        $getTrade->afterSaleOrderStatusUpdate($params);

        //记录操作日志
        $set_data = array(
            'oid'                => $info['oid'],
            'tid'                => $info['tid'],
            'aftersales_type'    => $info['aftersales_type'],
            'progress'           => $updateData['progress'],
            'status'           => $updateData['status'],
            'mes'           => $explanation,
        );
        $this->setAftersaleTrace($set_data);

        return $info;
    }


    /**
     * 消费者回寄退货物流信息
     *
     * @Author hfh_wind
     * @param $params
     * @return bool
     */
    public function sendBack($params)
    {

        $filter['aftersales_bn'] = $params['aftersales_bn'];

        if ($params['user_id']) {
            $filter['user_id'] = $params['user_id'];
        }

        $data['corp_code'] = $params['corp_code'];
        $data['logi_name'] = !empty($params['logi_name']) ? $params['logi_name'] : '';
        $data['logi_no'] = $params['logi_no'];
        $data['receiver_address'] = !empty($params['receiver_address']) ? $params['receiver_address'] : '';
        $data['mobile'] = !empty($params['mobile']) ? $params['mobile'] : '';

        $this->sendGoods($filter, 'buyer_back', $data, $params['user_id']);

        return true;
    }


    /**
     * 商家回寄退货物流信息
     *
     * @Author hfh_wind
     * @param $params
     * @return bool
     */
    public function sendConfirm($params)
    {

        $filter['aftersales_bn'] = $params['aftersales_bn'];

        if ($params['shop_id']) {
            $filter['shop_id'] = $params['shop_id'];
        }

        $data['corp_code'] = $params['corp_code'];
        $data['logi_name'] = !empty($params['logi_name']) ? $params['logi_name'] : '';
        $data['logi_no'] = $params['logi_no'];

        $this->sendGoods($filter, 'seller_confirm', $data, $params['shop_id']);

        return true;
    }


    /**
     * 售后过程中的退换货物流保存
     *
     * @Author hfh_wind
     * @param int $filter 售后编号
     * @param string $type buyer_back 消费者回寄，seller_confirm 商家重新发货
     * @param array $data 物流信息
     * @return mixed
     */
    public function sendGoods($filter, $type, $data, $loginId)
    {
        $info = TradeAftersales::where($filter)->select('aftersales_bn', 'aftersales_type', 'tid', 'oid', 'user_id',
            'status', 'shop_id', 'progress')->first()->toArray();

        if ($type == 'buyer_back') {

            if ($info['progress'] !== '1' && $info['aftersales_type'] == 'ONLY_REFUND') {
                throw new \LogicException('不需要回寄货品');
            }

            if ($data['corp_code'] == "other" && !$data['logi_name']) {
                throw new \LogicException('请填写物流公司');
            }

            if ($info['aftersales_type'] == 'RETURN_GOODS') {
                if (!$data['mobile']) {
                    throw new \LogicException('请填写手机号');
                }
                if (!$data['receiver_address']) {
                    throw new \LogicException('请填写收货地址');
                }
            }

            $updateData['sendback_data'] = serialize($data);
            $updateData['progress'] = '2';
            $params['after_sales_status'] = "WAIT_SELLER_CONFIRM_GOODS";
        } else {

            if ($info['progress'] != '2' && $info['aftersales_type'] != 'EXCHANGING_GOODS') {
                throw new \LogicException('不需要重新发货');
            }
            $updateData['sendconfirm_data'] = serialize($data);
            $updateData['progress'] = '4';
            $updateData['status'] = '2';
            $params['after_sales_status'] = "SELLER_SEND_GOODS";
        }

        TradeAftersales::where($filter)->update($updateData);

        $set_data = array(
            'oid'                => $info['oid'],
            'tid'                => $info['tid'],
            'aftersales_type'    => $info['aftersales_type'],
            'progress'           => $updateData['progress'],
            'status'             => $info['status'],
            'mes'                => $info['description'] = $info['description']?? '',
        );
        $this->setAftersaleTrace($set_data);

        $params['oid'] = $info['oid'];
        $params['tid'] = $info['tid'];
        $params['user_id'] = $info['user_id'];


        $getTrade = new \ShopEM\Services\TradeService();
        $tradeInfo = $getTrade->__getTradeInfo($params['tid']);

        $params['tradesData'] = $tradeInfo;
        //更新子订单售后状态
        $getTrade->afterSaleOrderStatusUpdate($params);

        return $info;
    }


    /**
     *  取消售后
     *
     * @Author hfh_wind
     * @param $params
     * @return bool
     * @throws \Exception
     */
    public function cancelftersales($params)
    {

        DB::beginTransaction();
        try {

            //ONLY_REFUND:只退款，REFUND_GOODS:退货退款，EXCHANGING_GOODS:换货

            //重置子订单状态,删掉售后信息
            $info = TradeAftersales::where([
                'oid'             => $params['oid'],
                'aftersales_type' => $params['aftersales_type']
            ])->first();
            if (empty($info)) {
                throw new \LogicException('售后信息不存在');
            }

            if (isset($info['status']) && $info['status'] == '0') {

                TradeAftersales::where(['oid' => $params['oid']])->delete();

                TradeOrder::where('oid', '=', $params['oid'])->update(['after_sales_status' => '']);
            } else {
                throw new \LogicException('售后申请已处理,无法取消!');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw new \LogicException($e->getMessage());
        }
        return true;
    }

    /**
     * 记录售后状态
     * @Author huiho
     * @param $set_data
     * @return bool
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
