<?php
/**
 * @Filename        UserTradeController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use Illuminate\Support\Facades\Cache;
use ShopEM\Models\ActivitiesRewardsSendLogs;
use ShopEM\Models\LogisticsDelivery;
use ShopEM\Models\Shop;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\CurrencyTrade;
use ShopEM\Models\Group;
use ShopEM\Models\GroupsUserJoin;
use ShopEM\Models\GroupsUserOrder;
use ShopEM\Models\TradeCancel;
use ShopEM\Models\QrcodeStore;
use ShopEM\Repositories\TradeRepository;
use ShopEM\Repositories\TradeAfterSalesRepository;

class UserTradeController extends BaseController
{
    /**
     * 用户订单列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @param TradeRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, TradeRepository $repository)
    {
        $query = [];
        $query['user_id'] = $this->user['id'];
        if (!empty($request->status)) {
            $query['status'] = $request->status;
        }
        if (isset($query['status']) && $query['status'] == 'CANCEL') {
            return $this->cancelLists($request);
        } elseif (isset($query['status']) && $query['status'] == 'AFTERSALES') {
            $tradeAfterSalesRepository = new TradeAfterSalesRepository;
            return $this->afterSalesLists($request, $tradeAfterSalesRepository);
        } else {
            $query['per_page'] = config('app.app_per_page');
            $lists = $repository->search($query);
        }

        return $this->resSuccess($lists);
    }

    /**
     * 订单详情
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $tid = $request->only('tid');
        if (empty($tid)) {
            return $this->resFailed(414);
        }
        $tid = $tid['tid'];
        $trade = Trade::where('tid', $tid)->where('user_id', $this->user['id'])->first();

        if (empty($trade)) {
            return $this->resFailed(700);
        }
        //取消订单
        $cancel_info = TradeCancel::where('tid', $tid)->where('user_id', $this->user['id'])->first();

        $trade = $trade->toArray();
        $shop_info = Shop::find($trade['shop_id']);
        $trade['shop_info'] = $shop_info;
        if ($cancel_info) {
            $trade['cancel_info'] = $cancel_info;
        }
        if ($trade['pick_type'] && $trade['status'] == 'WAIT_SELLER_SEND_GOODS') {
            $trade['ziti_qrcode'] = $this->getZitiQrcode($tid);
            $trade['ziti_qrcode_url'] = QrcodeStore::getQr($trade['ziti_qrcode'],'ziti');
            //为未生成提货码的自提订单生成提货码
            if (empty($trade['pick_code'])) {
                $pick_code = rand(100000, 999999);
                Trade::where('tid', $tid)->where('user_id', $this->user['id'])->update(['pick_code' => $pick_code]);
                $trade['pick_code'] = $pick_code;
            }
        }

        //如果是团购
//        $tradebill = TradePaybill::where(['tid' => $tid])->select('payment_id')->first();
        $join = GroupsUserJoin::where(['tid' => $tid])->first();
        if (!empty($join)) {
            $trade['GroupOrder'] = GroupsUserOrder::where(['groups_bn' => $join['groups_bn']])->first();
            $trade['Group'] = Group::where(['id' => $trade['GroupOrder']['groups_id']])->first();
        }

        //如果是订单待付款,返回订单关闭的时间
        if ($trade['status'] == 'WAIT_BUYER_PAY') {
            //目前写死30分钟.
            //将时间点转换为时间戳
            $dateTime = strtotime($trade['created_at']);
            //一天的秒数 1*24*60*60
            $dateTime = date('Y-m-d H:i:s', $dateTime + 2*60 * 60);
            $trade['trade_close_end_time'] = $dateTime;
        }

        /********   实物奖品的send_log的id ***********/
        $actRewardsSendLog = ActivitiesRewardsSendLogs::where('tid',$trade['tid'])->select('id')->first();
        if ($actRewardsSendLog) {
            $trade['act_rewards_log_id'] = $actRewardsSendLog->id;
        } else {
            $trade['act_rewards_log_id'] = 0;
        }

        /*$rids = \ShopEM\Models\TradeRelation::where('tid',$tid)->get()->toArray();
        if (count($rids) > 0) {
            $rids = array_column($rids, 'rid');
            $card_info = \ShopEM\Models\CurrencyTrade::whereIn('tid',$rids)->get()->toArray();
        }else{
            $card_info = \ShopEM\Models\CurrencyTrade::where('tid',$tid)->get()->toArray();
        }
        $cmsapi_model = new \ShopEM\Services\CmsPushService();
        if (count($card_info) > 0) {
            foreach ($card_info as $key => $value) {
                $api_res = $cmsapi_model->cardDetail($value['card_no']);
                $card_info[$key]['status'] = $api_res['result']['status'];
                $card_info[$key]['status_name'] = $api_res['result']['status_name'];
            }
            $trade['card_info'] = $card_info;
        }*/
        /*$recharge_info = \ShopEM\Models\TradeRecharge::where('tid',$tid)->first();
        if ($recharge_info) {
            $trade['recharge_info'] = $recharge_info;
        }*/
//        $trade['ship_list'] = $this->getShipList($tid);


//        $trade['amount'] = round($trade['amount']- $trade['point_discount_fee'], 2);//扣减掉积分抵扣的金额

        return $this->resSuccess($trade);
    }

    /**
     * [getShipList 获取订单物流列表]
     * @Author mssjxzw
     * @param  string $tid [description]
     * @return [type]       [description]
     */
    public function getShipList($tid = '')
    {
        $source = \ShopEM\Models\SourceConfig::where('type', 1)->get()->toArray();
        $source_arr = array_column($source, 'source');
        $name_arr = array_column($source, 'name');
        $rel = array_combine($source_arr, $name_arr);
        $rid = \ShopEM\Models\TradeRelation::where('tid', $tid)->whereIn('source', $source_arr)->get()->toArray();
        if (count($rid) > 0) {
            foreach ($rid as $key => $value) {
                $rid[$key]['ship_name'] = $rel[$value['source']] . '商品的物流';
            }
        }
        $self = 0;
        $order = \ShopEM\Models\TradeOrder::where('tid', $tid)->get();
        foreach ($order as $key => $value) {
            if ($value->source == 'self') {
                $self = 1;
            }
        }
        if ($self) {
            $rid[] = [
                'rid'       => $tid,
                'ship_name' => '平台商品的物流',
            ];
        }
        return $rid;
    }

    /**
     * 订单tab
     *
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function tab()
    {
        $result = [
            'WAIT_BUYER_PAY'           => [
                'count'       => 0,
                'status'      => 'WAIT_BUYER_PAY',
                'status_text' => Trade::$tradeStatusMap['WAIT_BUYER_PAY']
            ],
            'WAIT_SELLER_SEND_GOODS'   => [
                'count'       => 0,
                'status'      => 'WAIT_SELLER_SEND_GOODS',
                'status_text' => Trade::$tradeStatusMap['WAIT_SELLER_SEND_GOODS']
            ],
            'WAIT_BUYER_CONFIRM_GOODS' => [
                'count'       => 0,
                'status'      => 'WAIT_BUYER_CONFIRM_GOODS',
                'status_text' => Trade::$tradeStatusMap['WAIT_BUYER_CONFIRM_GOODS']
            ],
            'TRADE_FINISHED'           => ['count' => 0, 'status' => 'TRADE_FINISHED', 'status_text' => '待评价'],
            'CANCEL'                   => ['count' => 0, 'status' => 'CANCEL', 'status_text' => '售后'],
        ];
        $params = Trade::where('user_id', $this->user['id'])->where('cancel_status',
            'NO_APPLY_CANCEL')->get()->groupBy('status'); // 按status分组
        if ($params) {
            foreach ($params as $key => $value) {
                $result[$key]['count'] = count($value);
            }
        }
        $result['CANCEL']['count'] = Trade::select('trades.*')
            ->join('trade_cancels', 'trades.tid', '=', 'trade_cancels.tid')
            ->where('trades.user_id', $this->user['id'])
            ->count();
        return $this->resSuccess(array_values($result));
    }

    /**
     * 用户取消订单列表
     *
     * @Author djw
     * @param Request $request
     * @param TradeRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelLists(Request $request)
    {
        $query = [];
        $query['user_id'] = $this->user['id'];
        $query['per_page'] = config('app.app_per_page');

        $lists = Trade::orderBy('tid', 'desc')
            ->select('trades.*', 'trade_cancels.process', 'trade_cancels.refunds_status')
            ->join('trade_cancels', 'trades.tid', '=', 'trade_cancels.tid')
            ->where('trades.user_id', $query['user_id'])
            ->paginate($query['per_page']);

        $process_text = [
            0 => '提交申请',
            1 => '取消处理',
            2 => '退款处理',
            3 => '完成',
        ];
        $refunds_status_text = [
            'WAIT_CHECK'       => '等待审核',
            'WAIT_REFUND'      => '等待退款',
            'SHOP_CHECK_FAILS' => '商家审核不通过',
            'FAILS'            => '退款失败',
            'SUCCESS'          => '退款成功',
        ];
        $cancel_status_text = [
            'WAIT_CHECK'       => '等待审核',
            'WAIT_REFUND'      => '等待退款',
            'SHOP_CHECK_FAILS' => '商家审核不通过',
            'FAILS'            => '取消失败',
            'SUCCESS'          => '取消成功',
        ];

        if ($lists) {
            foreach ($lists as $key => $value) {
                $lists[$key]['process_text'] = $process_text[$value['process']];
                $lists[$key]['refunds_status_text'] = $cancel_status_text[$value['refunds_status']];
                if ($value['is_refund']) {
                    $lists[$key]['refunds_status_text'] = $refunds_status_text[$value['refunds_status']];
                }
//                $lists[$key]['amount'] = round($value['amount']- $value['point_discount_fee'], 2);//扣减掉积分抵扣的金额
            }
        }
        return $this->resSuccess($lists);
    }

    /**
     * 用户售后订单列表
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function afterSalesLists(Request $request, TradeAfterSalesRepository $TradeAfterSalesRepository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['user_id'] = $this->user['id'];
        unset($input_data['status']);

//        $lists = $TradeAfterSalesRepository->search($input_data);
//
//        if (empty($lists)) {
//            return $this->resFailed(700);
//        }
        $status_text = [
            0 => '待处理',
            1 => '处理中',
            2 => '已处理',
            3 => '已驳回',
        ];

        $lists = $TradeAfterSalesRepository->search($input_data);

        if ($lists) {
            $tradeOrder = new \ShopEM\Models\TradeOrder;
            foreach ($lists as $key => $value) {
                $value['status_text'] = $status_text[$value['status']];
                $trade_order = $tradeOrder->select('goods_price', 'quantity', 'amount', 'total_fee', 'avg_discount_fee',
                    'avg_points_fee', 'goods_image')->where('oid', $value['oid'])->first();
                if ($trade_order) {
                    $trade_order = $trade_order->toArray();
                    $after_salesL = $value->toArray();
                    $value = array_merge($after_salesL, $trade_order);
                }
                $value['shop_info'] = Shop::select('id', 'shop_name', 'shop_logo')->where('id',
                    $value['shop_id'])->first();
                $lists[$key] = $value;
            }
        }

        return $this->resSuccess($lists);
    }


    /**
     * [getZitiQrcode 获取自提二维码加密数据]
     * @Author mssjxzw
     * @param  [type]  $tid [description]
     * @return [type]       [description]
     */
    public function getZitiQrcode($tid)
    {
        $sign = 'tid';
        $sign_arr = str_split($sign);
        $sign_l = count($sign_arr) - 1;
        $tid_arr = str_split($tid);
        $tid_l = count($tid_arr);
        $n = mt_rand(3, 5);
        for ($i = 0; $i < $n; $i++) {
            $s = $sign_arr[mt_rand(0, $sign_l)] . getRandStr(3, $sign);
            $z = mt_rand(1, $tid_l - 1);
            if (mt_rand(0, 1)) {
                $tid_arr[$z] = $tid_arr[$z] . $s;
            } else {
                $tid_arr[$z] = $s . $tid_arr[$z];
            }
        }
        //不进行加密 nlx 2020-5-12 17:15:14
        $data = [0=>$sign,1=>$tid];
        return implode(':', $data);
        // return base64_encode(getRandStr(3, $sign) . implode('', $tid_arr));
    }


    /**
     * [getCurrencyTrades 获取游戏币订单信息]
     * @Author mssjxzw
     * @param  Request $requist [description]
     * @param  CurrencyTrade $mod [description]
     * @return [type]                 [description]
     */
    public function getCurrencyTrades(Request $requist, CurrencyTrade $mod)
    {
        $payment_id = $requist->payment_id;

        $cacheTradeKey = 'cachecurrency_trade_data_id_' . $payment_id;
        $getcacheTradeData = Cache::get($cacheTradeKey, []);

        $tids = $getcacheTradeData['tid']?? false;
        if (!$tids) {
            $bill = TradePaybill::where('payment_id', $payment_id)->get()->toArray();
            $tids = array_column($bill, 'tid');
        }

        $receiver_tel = $getcacheTradeData['receiver_tel']?? false;
        if (!$receiver_tel) {
            $trade = \ShopEM\Models\Trade::whereIn('tid', $tids)->get()->toArray();
            $receiver_tel = array_column($trade, 'receiver_tel');
        }

        $rids = $getcacheTradeData['rids']?? false;
        if (!$rids) {
            $rids = \ShopEM\Models\TradeRelation::whereIn('tid', $tids)->get()->toArray();
            $rids = array_column($rids, 'rid');
            Cache::put('cachecurrency_trade_relation_id_' . $payment_id, $rids, cacheExpires());
        }

        $goods = $getcacheTradeData['goods']?? false;
        if (!$goods) {
            $order = \ShopEM\Models\TradeOrder::whereIn('tid', $tids)->get()->toArray();
            $goods_ids = array_column($order, 'goods_id');
            $goods = \ShopEM\Models\Goods::whereIn('id', $goods_ids)->get();
        }

        $cacheTradeData = array(
            'tids'         => $tids,
            'rids'         => $rids,
            'goods'        => $goods,
            'receiver_tel' => $receiver_tel,
        );
        Cache::put($cacheTradeKey, $cacheTradeData, cacheExpires());


        $currencyTrade = Cache::get('cachecurrency_key_payment_id_' . $payment_id, [], cacheExpires());
        if (count($currencyTrade) == 0) {
            $currencyTrade = $mod->whereIn('tid', $rids)->get();
            if (count($currencyTrade) > 0) {
                Cache::put('cachecurrency_key_payment_id_' . $payment_id, $currencyTrade, cacheExpires());
            }
        }


        $return = [
            'receiver_tel'        => $receiver_tel[0]??'',
            'currency_trade_data' => $currencyTrade,
            'goods_data'          => $goods,
        ];
        return $this->resSuccess($return);
    }

    /**
     * [getShipInfo 获取物流信息]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function getShipInfo(Request $request)
    {
        if (!$request->filled('rid')) {
            return $this->resFailed(414, '参数不全');
        }
        $service = new \ShopEM\Services\TradeService();
        $res = $service->getShip($request->rid);
        return $this->resSuccess($res);
    }

    /**
     * 子订单详情
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderDetail(Request $request)
    {
        $oid = $request->only('oid');
        if (empty($oid)) {
            return $this->resFailed(414);
        }
        $oid = $oid['oid'];
        $trade = TradeOrder::where('oid', $oid)->where('user_id', $this->user['id'])->first();

        if (empty($trade)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($trade);
    }

    /**
     *  根据发货订单获取物流轨迹(暂时就只有EMS)
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetLogisticsInfo(Request $request)
    {
        $tid = $request->tid;

        if (empty($tid)) {
            return $this->resFailed(414, '订单号必传!');
        }
        $logisticsDelivery = LogisticsDelivery::where(['tid' => $tid])->select('logi_name', 'corp_code',
            'logi_no')->first();

        if (empty($logisticsDelivery)) {
            return $this->resFailed(700, '无法查询发货信息,或尚未发货!');
        }

        $service = new   \ShopEM\Services\LogisticsService();
        $return['msg'] = 'succ';
        $return['logisticsInfo'] = $service->Pullkd100($logisticsDelivery['logi_no'],$logisticsDelivery['corp_code']);
        $return['logi_name'] = $logisticsDelivery['logi_name'];//物流公司名称
        $return['logi_no'] = $logisticsDelivery['logi_no'];//物流单号

       /* if ($logisticsDelivery['corp_code'] == 'EMS') {
            $service = new   \ShopEM\Services\LogisticsService();
            $return['msg'] = 'succ';
            $return['logisticsInfo'] = $service->getEMS($logisticsDelivery['logi_no']);
            $return['logi_name'] = $logisticsDelivery['logi_name'];//物流公司名称
            $return['logi_no'] = $logisticsDelivery['logi_no'];//物流单号
        } else {
            $return['msg'] = 'false';
            $return['logisticsInfo'] = '';
            $return['logi_name'] = $logisticsDelivery['logi_name'];//物流公司名称
            $return['logi_no'] = $logisticsDelivery['logi_no'];//物流单号
        }*/

        return $this->resSuccess($return);
    }


}
