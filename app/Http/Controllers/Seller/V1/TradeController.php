<?php
/**
 * @Filename        TradeController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Models\GroupsUserJoin;
use ShopEM\Models\GroupsUserOrder;
use ShopEM\Models\LogisticsDeliveryDetail;
use ShopEM\Models\Trade;
use ShopEM\Models\ShopRelSeller;
use ShopEM\Models\TradeCancel;
use ShopEM\Models\TradeShippingNote;
use ShopEM\Models\LogisticsDelivery;
use ShopEM\Models\DownloadLog;
use ShopEM\Models\UserAccount;
use ShopEM\Repositories\TradeRepository;
use ShopEM\Repositories\TradeAfterCancelRepository;
use ShopEM\Repositories\TradePolymorphicRepository;
use ShopEM\Services\TradeService;
use ShopEM\Http\Requests\Seller\DeliveryTradeRequest;
use ShopEM\Http\Requests\Seller\TradeCancelRequest;
use ShopEM\Http\Requests\Seller\TradeCancelShopreplyRequest;
use ShopEM\Jobs\DownloadLogAct;


class TradeController extends BaseController
{
    /**
     * 订单列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, TradeRepository $tradeRepository)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;
        $input_data['per_page'] = config('app.per_page');
        $input_data['total_data_status'] = 'trade_main';

        $lists = $tradeRepository->search($input_data);
        if (empty($lists)) {
            return $this->resFailed(700);
        }
        foreach ($lists['data'] as $key => &$value) {
            $trade_order = [
                'data'  => $value['trade_order'],
                'field' => [
                    [
                        'key'         => 'goods_image',
                        'dataIndex'   => 'goods_image',
                        'title'       => '商品主图',
                        'scopedSlots' => ['customRender' => 'goods_image']
                    ],
                    ['key' => 'goods_name', 'dataIndex' => 'goods_name', 'title' => '商品名称'],
                    ['key' => 'goods_price', 'dataIndex' => 'goods_price', 'title' => '商品价格'],
                    ['key' => 'goods_marketprice', 'dataIndex' => 'goods_marketprice', 'title' => '商品市场价'],
                    ['key' => 'sku_info', 'dataIndex' => 'sku_info', 'title' => 'SKU信息'],
                    ['key' => 'after_sales_status_text', 'dataIndex' => 'after_sales_status_text', 'title' => '售后状态'],
                    ['key' => 'quantity', 'dataIndex' => 'quantity', 'title' => '购买数量'],
                ],
            ];
            $value['trade_order'] = $trade_order;
            $value['receiver_addr'] = $value['receiver_province'] . $value['receiver_city'] . $value['receiver_county'] . $value['receiver_address'];

            //拼团成功的判断
            if ($value['activity_sign'] == 'is_group') {
                $join = GroupsUserJoin::where(['tid' => $value['tid']])->first();
                if (!empty($join)) {
                    $groupOrder = GroupsUserOrder::where(['groups_bn' => $join['groups_bn']])->first();
                    $group_status_text = [
                        0 => '拼团失败',
                        1 => '正在拼团',
                        2 => '拼团成功',
                        3 => '拼团超时',
                    ];
                    $value['group_status_text'] = $group_status_text[$groupOrder['status']] ?? '--';
                }
            }
        }

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $tradeRepository->listShowFields(),
            'total_fee_data' => $total_fee_data,
        ]);
    }


    /**
     * 取消订单列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function cancelLists(Request $request, TradeAfterCancelRepository $TradeAfterCancelRepository)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;
        $input_data['per_page'] = config('app.per_page');

        $input_data['total_data_status'] = true;

        $lists = $TradeAfterCancelRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $TradeAfterCancelRepository->listShowFields(),
            'total_fee_data' => $total_fee_data,
        ]);
    }

    /**
     * [shopRemarks 保存商家备注]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function shopRemarks(Request $request)
    {
        if ($request->filled(['remarks', 'id'])) {
            $trade = Trade::find($request->id);
            if ($trade) {
                $trade->shop_memo = $request->remarks;
                $trade->save();
                return $this->resSuccess();
            } else {
                return $this->resFailed(500, '无此订单');
            }
        } else {
            return $this->resFailed(414, '缺少有效参数');
        }
    }


    /**
     * 订单详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $trade = Trade::find($request->tid);

        if (empty($trade)) {
            return $this->resFailed(700);
        }
        if ($trade->shop_id != $this->shop->id) {
            return $this->resFailed(700);
        }

        $trade = $trade->toArray();
        //拼团成功的判断
        if ($trade['activity_sign'] == 'is_group') {
            if (!empty($trade['group_info'])) {
                $group_status_text = [
                    0 => '拼团失败',
                    1 => '正在拼团',
                    2 => '拼团成功',
                    3 => '拼团超时',
                ];
                $trade['group_status_text'] = $group_status_text[$trade['group_info']['status']] ?? '--';
                if ($trade['group_info']['group_users']) {
                    foreach ($trade['group_info']['group_users'] as &$group_user) {
                        $group_user['trade'] = Trade::find($group_user['tid']);
                    }
                    unset($group_user);
                }
            }
        }


//        $trade['amount'] = round($trade['amount']- $trade['point_discount_fee'], 2);//扣减掉积分抵扣的金额

        return $this->resSuccess($trade);
    }

    /**
     * 记录订单发货信息
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function shipping(Request $request)
    {
        $data = $request->only('tid', 'note');

        try {
            TradeShippingNote::create($data);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }


        return $this->resSuccess();
    }


    /**
     * 商家取消订单
     *
     * @Author hfh_wind
     * @param TradeCancelRequest $params
     * @return array
     */

    public function tradeCancelCreate(TradeCancelRequest $params)
    {
        $params = $params->all();

        $create = new \ShopEM\Services\TradeService;

        $shop_id = $this->shop->id;

        $tid = $params['tid'];
        $cancelReason = trim($params['cancel_reason']);
        $cancelFromType = $shop_id ? 'shop' : 'admin';

        $group_trade = Trade::where(['tid' => $tid, 'activity_sign' => 'is_group'])->first();
        //团购订单调用团购商品的取消处理
        if ($group_trade) {
            $user_orders = DB::table('groups_user_orders')->where('tid', '=', $tid)->where('status', '=',2)->first();
            if (!$user_orders) {
                $GroupService = new \ShopEM\Services\GroupService;
                $GroupService->clearGroupInfo($tid, 0);
            }
        }
        $create->setCancelFromType($cancelFromType)
            ->setCancelId($shop_id)
            ->tradeCancelCreate($tid, $cancelReason, '');

        return $this->resSuccess([], '申请成功!!');
    }


    /**
     * 商家取消订单
     *
     * @Author hfh_wind
     * @param TradeCancelRequest $params
     * @return array
     */

    public function tradeCanceldetail(Request $request)
    {
        $tid = $request->tid;
        if ($tid <= 0) {
            return $this->resFailed(414);
        }

        $shop_id = $this->shop->id;

        $pagedata['trade_info'] = Trade::where(['tid' => $tid, 'shop_id' => $shop_id])->first();

        $pagedata['cancel_info'] = TradeCancel::where(['tid' => $tid, 'shop_id' => $shop_id])->first();

        return $this->resSuccess([
            'info' => $pagedata,
        ]);
    }


    /**
     * 商家审核取消订单
     *
     * @Author hfh_wind
     * @param TradeCancelShopreplyRequest $params
     * @return bool
     * @throws \ShopEM\Services\Exception
     */
    public function cancelShopReply(TradeCancelShopreplyRequest $params, TradeService $trade)
    {
        $shop_id = $this->shop->id;
        if ($params['status'] == 'agree') {
            //商家审核同意取消订单
            $trade->cancelShopAgree($params['cancel_id'], $shop_id);
        } else {
            if (empty($params['reason'])) {
                throw new \LogicException('审核拒绝理由必填');
            }
            //商家审核拒绝取消订单
            $trade->cancelShopReject($params['cancel_id'], $shop_id, $params['reason']);
        }

        return $this->resSuccess([], '审核成功!');
    }


    /**
     * 对指定订单进行发货，交易发货
     *
     * @Author hfh_wind
     * @param $params
     * @return mixed
     */
    public function deliveryTrade(DeliveryTradeRequest $params)
    {
        $data = $params->only('tid', 'corp_code', 'logi_no', 'ziti_memo', 'memo', 'shop_id', 'seller_id');
        $shop_id = $this->shop->id;

        if (!empty($shop_id)) {
            $shop_rel = ShopRelSeller::where(['shop_id' => $shop_id])->first();
        }
        $tid = $data['tid'];
        $corpCode = $data['corp_code'];
        $logiNo = $data['logi_no'];
        $zitiMemo = !empty($data['ziti_memo']) ? $data['ziti_memo'] : '';
        $memo = !empty($data['memo']) ? $data['memo'] : '';
        $shopUserData = [
            'shop_id'   => $shop_id,
            'seller_id' => $shop_rel->seller_id,
        ];
        try {
            //  unset($params);
            $doDelivery = new \ShopEM\Services\TradeService();
            $doDelivery->doDelivery($tid, $corpCode, $logiNo, $shopUserData, $zitiMemo, $memo);

        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess([], '发货成功!');
    }

    /**
     * [pickUp 提货操作]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function pickUp(Request $request)
    {
        if (!$request->filled('id') || !$request->filled('code')) {
            return $this->resFailed(414, '参数不全');
        }
        $data = $request->only('id', 'code');
        try {
            /*$trade = Trade::where('id',$data['id'])->first();
            $tid = $trade->tid;
            $trade->pick_statue = 1;
            $trade->status = 'TRADE_FINISHED';
            $trade->end_time = now()->toDateTimeString();
            $trade->save();
            $orders = \ShopEM\Models\TradeOrder::where('tid',$tid)->get();
            foreach ($orders as $key => $value) {
                \ShopEM\Models\TradeOrder::where('id',$value->id)->update(['status'=>'TRADE_FINISHED']);
            }
            $rel = \ShopEM\Models\TradePickCode::where('tid',$tid)->delete();*/


            $trade = Trade::where('id', $data['id'])->where('shop_id', $this->shop->id)->where('pick_code', $data['code'])->first();
            if (!$trade) {
                return $this->resFailed(500, '提货码错误');
            }
            if ($trade->pick_statue) {
                return $this->resFailed(500, '该订单已提货');
            }

            //拼团成功的判断
            $join = GroupsUserJoin::where(['tid' => $trade['tid']])->first();
            if (!empty($join)) {
                $groupOrder = GroupsUserOrder::where(['groups_bn' => $join['groups_bn']])->first();
                if ($groupOrder['status'] != 2) {
                    return $this->resFailed(500, '该订单未拼团成功');
                }
            }

            switch ($trade->status) {
                case 'TRADE_FINISHED':
                    return $this->resFailed(500, '该订单已完成');
                    break;
                case 'TRADE_CLOSED':
                    return $this->resFailed(500, '该订单已关闭');
                    break;
                case 'TRADE_CLOSED_BY_SYSTEM':
                    return $this->resFailed(500, '该订单已被系统关闭');
                    break;
                case 'WAIT_BUYER_PAY':
                    return $this->resFailed(500, '该订单未付款');
                    break;
            }
            $endtime = now()->toDateTimeString();
            $trade->pick_statue = 1;
            $trade->status = 'TRADE_FINISHED';
            $trade->end_time = $endtime;
            $trade->confirm_at = $endtime;
            $trade->save();
            $tid = $trade->tid;

            \ShopEM\Models\TradeOrder::where('tid', $tid)->update([
                'status' => 'TRADE_FINISHED',
                'end_time' => $endtime,
                'confirm_at' => $endtime
            ]);

            //收货后的操作
            $tradeService = new TradeService;
            $tradeService->gainPonit($tid);
            $tradeService->confirmTradeEvent($trade);
        } catch (\Exception $e) {
            return $this->resFailed(414, $e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * [qrcodePickUp 二维码提货操作]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function qrcodePickUp(Request $request)
    {
        if (!$request->filled('data')) {
            return $this->resFailed(414, '参数不全');
        }
        try {
            // $tid = $this->tid_decode(base64_decode($request->data));
            $tid = $this->tid_decode($request->data);
            $trade = Trade::where('tid', $tid)->where('shop_id', $this->shop->id)->first();
            if (!$trade) {
                return $this->resFailed(500, '二维码有误');
            }
            if ($trade->pick_statue) {
                return $this->resFailed(500, '该订单已提货');
            }

            //拼团成功的判断
            $join = GroupsUserJoin::where(['tid' => $trade['tid']])->first();
            if (!empty($join)) {
                $groupOrder = GroupsUserOrder::where(['groups_bn' => $join['groups_bn']])->first();
                if ($groupOrder['status'] != 2) {
                    return $this->resFailed(500, '该订单未拼团成功');
                }
            }

            switch ($trade->status) {
                case 'TRADE_FINISHED':
                    return $this->resFailed(500, '该订单已完成');
                    break;
                case 'TRADE_CLOSED':
                    return $this->resFailed(500, '该订单已关闭');
                    break;
                case 'TRADE_CLOSED_BY_SYSTEM':
                    return $this->resFailed(500, '该订单已被系统关闭');
                    break;
                case 'WAIT_BUYER_PAY':
                    return $this->resFailed(500, '该订单未付款');
                    break;
            }
            $endtime = now()->toDateTimeString();
            $trade->pick_statue = 1;
            $trade->status = 'TRADE_FINISHED';
            $trade->end_time = $endtime;
            $trade->confirm_at = $endtime;
            $trade->save();
            $tid = $trade->tid;

            \ShopEM\Models\TradeOrder::where('tid', $tid)->update([
                'status' => 'TRADE_FINISHED',
                'end_time' => $endtime,
                'confirm_at' => $endtime
            ]);
            //\ShopEM\Models\TradePickCode::where('tid',$tid)->delete();

            //收货后的操作
            $tradeService = new TradeService;
            $tradeService->gainPonit($tid);
            $tradeService->confirmTradeEvent($trade);

        } catch (Exception $e) {
            return $this->resFailed(414, $e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * [qrcodePickInfo 提货码用户信息]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function qrcodePickInfo(Request $request)
    {
        if (!$request->filled('data')) {
            return $this->resFailed(414, '参数不全');
        }
        $tid = $this->tid_decode($request->data);
        $trade = Trade::where('tid', $tid)->where('shop_id', $this->shop->id)->first();
        if (!$trade) {
            return $this->resFailed(500, '提货码有误');
        }
        if ($trade->pick_statue) {
            return $this->resFailed(500, '该订单已提货');
        }

        $result['code'] = $trade->pick_code;
        $result['mobile'] = $trade->receiver_tel;

        return $this->resSuccess($result);
    }

    /**
     * [pickUpList 提货列表]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @param  TradeRepository $tradeRepository [description]
     * @return [type]                           [description]
     */
    public function pickUpList(Request $request, TradeRepository $tradeRepository)
    {
        if (!$request->filled('code')) {
            return $this->resFailed(414, '请输入提货码');
        }
        if (!$request->filled('mobile')) {
            return $this->resFailed(414, '请输入手机号');
        }
        /*$obj = new \ShopEM\Models\TradePickCode();
        $rel = $obj->where(['pick_code'=>$request->code,'mobile'=>$request->mobile])->get();
        $lists = [];
        if (count($rel) > 0) {
            foreach ($rel as $key => $value) {
                $tids[] = $value->tid;
            }
            $lists = Trade::whereIn('tid',$tids)->get();
        }*/

        $lists = Trade::where(['pick_code' => $request->code, 'receiver_tel' => $request->mobile])->where('shop_id', $this->shop->id)->get();
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $tradeRepository->listShowFields(),
        ]);
    }

    /**
     * [filterExport 筛选导出订单]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function filterExport(Request $request)
    {
        $input = $request->all();
        if (isset($input['s'])) {
            unset($input['s']);
        }
        if (empty($input)) {
            return $this->resFailed(414, '至少1个筛选条件');
        }
        $input['shop_id'] = $this->shop->id;
        if (isset($input['mobile'])) {
            $user = \ShopEM\Models\UserAccount::where('mobile', $input['mobile'])->first();
            $input['user_id'] = $user->id;
        }//转换user_id
        $filterables = [
            'shop_id'       => ['field' => 'shop_id', 'operator' => '='],
            'user_id'       => ['field' => 'user_id', 'operator' => '='],
            'cancel_status' => ['field' => 'cancel_status', 'operator' => '='],
            'status'        => ['field' => 'status', 'operator' => '='],
            'pick_type'     => ['field' => 'pick_type', 'operator' => '='],
        ];//过滤筛选条件
        $model = new Trade();
        $model = filterModel($model, $filterables, $input);
        if (isset($input['time'])) {
//            $from = \Carbon\Carbon::parse($input['time']['from'])->toDateTimeString();
//            $to = \Carbon\Carbon::parse($input['time']['to'])->toDateTimeString();
            $from = $input['time']['from'];
            $to = $input['time']['to'];
            switch ($input['time']['type']) {
                case 'pay_time':
                    $model = $model->where('pay_time', '>=', $from)->where('pay_time', '<=', $to);
                    break;
                case 'end_time':
                    $model = $model->where('end_time', '>=', $from)->where('end_time', '<=',
                        $to)->where('status', 'TRADE_FINISHED');
                    break;
                case 'created_at':
                    $model = $model->where('created_at', '>=', $from)->where('created_at', '<=', $to);
                    break;
            }
        }
        $res = $model->get();
        $order_list = [];
        /*
         *组装导出表结构
         */
        foreach ($res as $key => &$value) {
            $value->shop_name = $value->shop_info->shop_name;
            $user_info = \ShopEM\Models\UserAccount::find($value->user_id);
            $value->login_account = isset($user_info->login_account) ? $user_info->login_account : '数据丢失';
            $value->trade_mobile = isset($user_info->mobile) ? $user_info->mobile : '数据丢失';
            $value->receiver_addr_info = $value->receiver_province . $value->receiver_city . $value->receiver_county . $value->receiver_address;
            $value->pick_statue_text = '';
            if ($value->pick_type == 1) {
                $value->pick_statue_text = ($value->pick_statue == 1) ? '已提货' : '未提货';
            }
            $trade_order = $value->trade_order->toArray();
            foreach ($trade_order as $order) {
                $order['payment_id'] = $value->payment_id;
                $order['shop_name'] = $value->shop_name;
                $order['login_account'] = $value->login_account;
                $order['trade_mobile'] = $value->trade_mobile;
                $order['status_text'] = $value->status_text;
                $order['cancel_text'] = $value->cancel_text;
                $order['cancel_reason'] = $value->cancel_reason;
                $order['created_at'] = \Carbon\Carbon::parse($value->created_at)->toDateTimeString();
                $order['pay_type_text'] = $value->pay_type_text;
                $order['pay_time'] = $value->pay_time;
                $order['trade_amount'] = $value->amount_text;
                $order['points_fee'] = $value->points_fee;
                $order['trade_total_fee'] = $value->total_fee;
                $order['post_fee'] = $value->post_fee;
                $order['discount_fee'] = $value->discount_fee;
                $order['platform_discount'] = $value->platform_discount;
                $order['seller_coupon_discount'] = $value->seller_coupon_discount;
                $order['seller_discount'] = $value->seller_discount;
                $order['obtain_point_fee'] = $value->obtain_point_fee;
                $order['consume_point_fee'] = $value->consume_point_fee;
                $order['receiver_name'] = $value->receiver_name;
                $order['receiver_tel'] = $value->receiver_tel;
                $order['receiver_addr_info'] = $value->receiver_addr_info;
                $order['buyer_message'] = $value->buyer_message;
                $order['shop_memo'] = $value->shop_memo;
                $order['pick_type_name'] = $value->pick_type_name;
                $order['shipping_type'] = $value->shipping_type;
                $order['invoice_no'] = $value->invoice_no;
                $order['pick_code'] = $value->pick_code;
                $order['pick_statue_text'] = $value->pick_statue_text;
                $order['ziti_addr'] = $value->ziti_addr;
                $order['confirm_at'] = $value->confirm_at ?? '--';
                $order['end_time'] = $value->end_time ?? '--';
                $order['activity_sign_text'] = $value->activity_sign_text ?? '线上交易';
                $order_list[] = $order;
            }

//            $order_list = array_merge($order_list,$value->trade_order->toArray());
        }

        $return['order']['tHeader'] = [
            '商户订单号',
            '订单号',
            '子订单号',
            '商品名称',
            'SKU信息',
            '商品货号',
            '商品价格',
            '商品成本价',
            '商品市场价',
            '购买数量',
            '子订单实付金额',
            '应付金额',
            '优惠分摊',
            '积分抵扣的金额',
            '退款金额',
            '所属店铺',
            '用户账号',
            '用户手机',
            '订单状态',
            '取消状态',
            '取消原因',
            '下单时间',
            '支付方式',
            '支付时间',
            '总实付金额',
            '积分抵消',
            '总价',
            '邮费',
            '优惠金额',
            '平台优惠券金额',
            '商家优惠券金额',
            '店铺促销金额',
            '买家获得积分',
            '买家消耗积分',
            '收货人姓名',
            '收货人电话',
            '收货人地址',
            '买家留言',
            '卖家备注',
            '提货方式',
            '快递方式',
            '快递单号',
            '提货码',
            '提货状态',
            '自提地址',
            '用户确认收货时间/商品核销时间',
            //'订单完成时间',
            '活动渠道',
        ];
        $return['order']['filterVal'] = [
            'payment_id',
            'tid',
            'oid',
            'goods_name',
            'sku_info',
            'goods_serial',
            'goods_price',
            'goods_cost',
            'goods_marketprice',
            'quantity',
            'amount_text',
            'total_fee',
            'avg_discount_fee',
            'avg_points_fee',
            //'refund_fee',
            'refund_fee_text',
            'shop_name',
            'login_account',
            'trade_mobile',
            'status_text',
            'cancel_text',
            'cancel_reason',
            'created_at',
            'pay_type_text',
            'pay_time',
            'trade_amount',
            'points_fee',
            'trade_total_fee',
            'post_fee',
            'discount_fee',
            'platform_discount',
            'seller_coupon_discount',
            'seller_discount',
            'obtain_point_fee',
            'consume_point_fee',
            'receiver_name',
            'receiver_tel',
            'receiver_addr_info',
            'buyer_message',
            'shop_memo',
            'pick_type_name',
            'shipping_type',
            'invoice_no',
            'pick_code',
            'pick_statue_text',
            'ziti_addr',
            'confirm_at',
            //'end_time',
            'activity_sign_text'
        ];
        $return['order']['list'] = $order_list;
        return $this->resSuccess($return);
    }

    private function tid_decode($str)
    {
        //不进行加密 nlx 2020-5-12 17:15:14
        $data = explode(':', $str);
        return $data[1];

        $sign = 'tid';
        $sign_arr = str_split($sign);
        foreach ($sign_arr as $key => $value) {
            $str = str_replace($value, ',', $str);
        }
        $str_arr = explode(',', $str);
        foreach ($str_arr as $k => &$v) {
            $v = substr($v, 3);
        }
        return implode($str_arr);
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
        $return['logisticsInfo'] = $service->Pullkd100($logisticsDelivery['logi_no'], $logisticsDelivery['corp_code']);
        $return['logi_name'] = $logisticsDelivery['logi_name'];//物流公司名称
        $return['logi_no'] = $logisticsDelivery['logi_no'];//物流单号

        /*
        if ($logisticsDelivery['corp_code'] == 'EMS') {
            $service = new   \ShopEM\Services\LogisticsService();
            $return['msg'] = 'succ';
            $return['logisticsInfo'] = $service->getEMS($logisticsDelivery['logi_no']);
            $return['logi_name'] = $logisticsDelivery['logi_name'];//物流公司名称
            $return['logi_no'] = $logisticsDelivery['logi_no'];//物流单号
        }
        else {
            $return['msg'] = 'false';
            $return['logisticsInfo'] = '';
            $return['logi_name'] = $logisticsDelivery['logi_name'];//物流公司名称
            $return['logi_no'] = $logisticsDelivery['logi_no'];//物流单号
        }*/

        return $this->resSuccess($return);
    }

    /**
     *  追加备注
     *
     * @Author swl
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addRemark(Request $request)
    {
        $trade = $request->only('tid','remarks');
        if (empty($trade['tid'])) {
            return $this->resFailed(414, '订单号必传!');
        }

         try
         {
            Trade::where('id',$trade['tid'])->update(['add_remarks'=>$trade['remarks']]);

            } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * 确认收货订单列表
     *
     * @Author Huiho
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmOrderLists(Request $request, TradePolymorphicRepository $repository)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;
        $input_data['per_page'] = $input_data['per_page'] ?? config('app.per_page');
        $input_data['total_data_status'] = true;

        $lists = $repository->confirmSearch($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->confirmOrderListFields(),
            'total_fee_data' => $total_fee_data,
        ]);
    }


    /**
     * 确认收货订单列表导出
     *
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmOrdersDown(Request $request, TradePolymorphicRepository $repository)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;

        $lists = $repository->confirmSearch($input_data, 1);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        $title = $repository->confirmOrderListFields();

        $return['order']['tHeader']= array_column($title,'title'); //表头
        $return['order']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['order']['list']= $lists; //表头

        return $this->resSuccess($return);
    }


    /**
     * 成本价订单列表
     *
     * @Author Huiho
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodsCostLists(Request $request, TradePolymorphicRepository $repository)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;
        $input_data['per_page'] = $input_data['per_page'] ?? config('app.per_page');
        $input_data['total_data_status'] = true;

        $lists = $repository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }


        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->GoodsCostListFields(),
            'total_fee_data' => $total_fee_data,
        ]);
    }


    /**
     * 成本价订单列表单出
     *
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodsCostDown(Request $request, TradePolymorphicRepository $repository)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;

        $lists = $repository->search($input_data, 1);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        $title = $repository->GoodsCostListFields();

        $return['order']['tHeader']= array_column($title,'title'); //表头
        $return['order']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['order']['list']= $lists; //表头

        return $this->resSuccess($return);
    }


    /**
     * 自提商品核销列表
     *
     * @Author Huiho
     * @return \Illuminate\Http\JsonResponse
     */
    public function selfExtractingLists(Request $request, TradePolymorphicRepository $repository)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;
        $input_data['pick_type'] = 1;
        $input_data['per_page'] = $input_data['per_page'] ?? 15;

        $lists = $repository->selfExtractingSearch($input_data);


        if (empty($lists)) {
            return $this->resFailed(700);
        }

        foreach ($lists['data'] as $key => &$value)
        {
                $value->pick_statue_text = ($value->pick_statue == 1) ? '已提货' : '未提货';
                $value->gm_name = DB::table('gm_platforms')->where('gm_id', '=', $value->gm_id)->value('platform_name');
                $value->shop_name = DB::table('shops')->where('id', '=', $value->shop_id)->value('shop_name');
                $value->goods_serial =  DB::table('goods_skus')->where('id', $value->sku_id)->value('goods_serial');;
        }


        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->selfExtractingListsFields(),
        ]);
    }


    /**
     * 自提商品核销列表导出
     *
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function selfExtractingDown(Request $request, TradePolymorphicRepository $repository)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;
        $input_data['pick_type'] = 1;

        $lists = $repository->selfExtractingSearch($input_data, 1);


        if (empty($lists)) {
            return $this->resFailed(700);
        }

        foreach ($lists as $key => &$value)
        {
            $value->pick_statue_text = ($value->pick_statue == 1) ? '已提货' : '未提货';
            $value->gm_name = DB::table('gm_platforms')->where('gm_id', '=', $value->gm_id)->value('platform_name');
            $value->shop_name = DB::table('shops')->where('id', '=', $value->shop_id)->value('shop_name');
            $value->goods_serial =  DB::table('goods_skus')->where('id', $value->sku_id)->value('goods_serial');;
        }
//        $order_list = [];
//        foreach ($lists as $key => &$value) {
//            $value['pick_statue_text']= ($value['pick_statue'] == 1) ? '已提货' : '未提货';
//            foreach ($value['trade_order'] as $order_key => &$order_value) {
//                $order['gm_name'] = $value['gm_name'];
//                $order['shop_name'] = $value['shop_name'];
//                $order['tid'] = $order_value['tid'];
//                $order['oid'] = $order_value['oid'];
//                $order['goods_serial'] =  DB::table('goods_skus')->where('id',$order_value['sku_id'])->value('goods_serial');;
//                $order['goods_name'] = $order_value['goods_name'];
//                $order['created_at'] = $order_value['created_at'];
//                $order['pick_statue_text'] = $value['pick_statue_text'];
//                $order_list[] = $order;
//            }
//        }


        $title = $repository->selfExtractingListsFields();

        $return['order']['tHeader']= array_column($title,'title'); //表头
        $return['order']['filterVal']= array_column($title,'dataIndex'); //表头字段

       // $return['order']['list']= $order_list; //表头
        $return['order']['list']= $lists; //表头

        return $this->resSuccess($return);
    }


    /**
     * 商品统计列表
     *
     * @Author Huiho
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodSaleList(Request $request, TradePolymorphicRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = $input_data['per_page'] ?? 15;
        $input_data['shop_id'] = $this->shop->id;
        $input_data['total_data_status'] = true;

        $lists = $repository->GoodSaleSearch($input_data);


        if (empty($lists))
        {
            return $this->resFailed(700);
        }

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->GoodSaleListFields(),
            'total_fee_data' => $total_fee_data,
        ]);

    }


    /**
     * 商品统计列表导出
     *
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodSaleDown(Request $request, TradePolymorphicRepository $repository)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;
        $lists = $repository->GoodSaleSearch($input_data, 1);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        $title = $repository->GoodSaleListFields();

        $return['order']['tHeader']= array_column($title,'title'); //表头
        $return['order']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['order']['list']= $lists; //表头

        return $this->resSuccess($return);
    }

    /**
     * 新订单导出
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function newTradeOrder(Request $request)
    {
        //$input_data = $request->only('exportForm');
        $input_data = $request->all();;
        if (isset($input_data['s']))
        {
            unset($input_data['s']);
        }
        if (empty($input_data))
        {
            return $this->resFailed(414, '至少1个筛选条件');
        }
        //$input_data = $input_data['exportForm'];

        $input_data['shop_id'] = $this->shop->id;

        //转换user_id
        if (isset($input_data['mobile']))
        {
            $user = \ShopEM\Models\UserAccount::where('mobile',$input_data['mobile'])->first();
            if (!$user)
            {
                return $this->resFailed(414, '找不到该会员');
            }
            unset($input_data['mobile']);
            $input['user_id'] = $user->id;
        }


        $insert['type'] = 'TradeOrder';
        $insert['desc'] = json_encode($input_data);
        $insert['shop_id'] = $input_data['shop_id'];
        $insert['gm_id'] = 0;

        $res = DownloadLog::create($insert);

        $data['log_id'] = $res['id'];

        DownloadLogAct::dispatch($data);

        return $this->resSuccess('导出中请等待!');
    }

     /**
     * 成本价订单列表导出
     *
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function newGoodsCostDown(Request $request)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;
        $insert['type'] = 'GoodsCost';
        $insert['desc'] = json_encode($input_data);
        $insert['shop_id'] = $input_data['shop_id'];
        $insert['gm_id'] = 0;

        $res = DownloadLog::create($insert);

        $data['log_id'] = $res['id'];
        DownloadLogAct::dispatch($data);
        return $this->resSuccess('导出中请等待!');
    }

}
