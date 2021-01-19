<?php

/**
 * @Author: swl
 * @Date:   2020-03-10
 */
namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use ShopEM\Models\GroupsUserJoin;
use ShopEM\Models\GroupsUserOrder;
use ShopEM\Models\Trade;
use ShopEM\Repositories\TradeRepository;
use ShopEM\Repositories\TradePolymorphicRepository;
use ShopEM\Http\Requests\Seller\TradeCancelRequest;
use ShopEM\Repositories\TradeAfterCancelRepository;
use ShopEM\Models\DownloadLog;
use ShopEM\Jobs\DownloadLogAct;
use ShopEM\Repositories\TradeStockReturnLogRepository;

class TradeController extends BaseController
{
	  /**
     * 订单列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, TradeRepository $tradeRepository)
    {
        $input_data = $request->all();

        $input_data['per_page'] = config('app.per_page');
        $input_data['total_data_status'] = 'trade_main';
        $lists = $tradeRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        foreach ($lists['data'] as $key => &$value) {
            $trade_order = [
                'data'=>$value['trade_order'],
                'field'=>[
                    ['key' => 'goods_image', 'dataIndex' => 'goods_image', 'title' => '商品主图','scopedSlots'=>['customRender'=>'goods_image']],
                    ['key' => 'goods_name', 'dataIndex' => 'goods_name', 'title' => '商品名称'],
                    ['key' => 'goods_price', 'dataIndex' => 'goods_price', 'title' => '商品价格'],
                    ['key' => 'goods_marketprice', 'dataIndex' => 'goods_marketprice', 'title' => '商品市场价'],
                    ['key' => 'sku_info', 'dataIndex' => 'sku_info', 'title' => 'SKU信息'],
                    ['key' => 'after_sales_status_text', 'dataIndex' => 'after_sales_status_text', 'title' => '售后状态'],
                    ['key' => 'quantity', 'dataIndex' => 'quantity', 'title' => '购买数量'],
                ],
            ];
            $value['trade_order'] = $trade_order;
            $value['receiver_addr'] = $value['receiver_province'].$value['receiver_city'].$value['receiver_county'].$value['receiver_address'];

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
        $field = $tradeRepository->listShowFields('group');
        //$field[] = ['dataIndex' => 'payment_id', 'title' => '支付单号', 'key'=>'payment_id'];
        $field[] = ['dataIndex' => 'shop_name', 'title' => '店铺名称', 'key'=>'shop_name'];
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $field,
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
            'field' => $TradeAfterCancelRepository->listShowFields('group'),
            'total_fee_data' => $total_fee_data,
        ]);
    }

      /**
     * 订单详情
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $tid = $request->tid;

        if (empty($tid)) {
            return $this->resFailed(414);
        }

        $trade = Trade::find($tid);

        if (empty($trade))
            return $this->resFailed(700);

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

        return $this->resSuccess($trade);
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
        if (isset($input['shop_name'])) {
            $shop = \ShopEM\Models\Shop::where('shop_name',$input['shop_name'])->first();
            if (!$shop) {
                return $this->resFailed(414, '找不到该店铺');
            }
            $input['shop_id'] = $shop->id;
        }//转换shop_id
        if (isset($input['mobile'])) {
            $user = \ShopEM\Models\UserAccount::where('mobile',$input['mobile'])->first();
            if (!$user) {
                return $this->resFailed(414, '找不到该会员');
            }
            $input['user_id'] = $user->id;
        }//转换user_id

        // $input['gm_id'] = $this->GMID;
        $filterables = [
            'shop_id' => ['field' => 'shop_id', 'operator' => '='],
            'user_id' => ['field' => 'user_id', 'operator' => '='],
            'cancel_status' => ['field' => 'cancel_status', 'operator' => '='],
            'status'   => ['field' => 'status', 'operator' => '='],
            'pick_type'   => ['field' => 'pick_type', 'operator' => '='],
            'gm_id'   => ['field' => 'gm_id', 'operator' => '='],
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
                    $model = $model->whereDate('pay_time','>=',$from)->whereDate('pay_time','<=',$to);
                    break;
                case 'end_time':
                    $model = $model->whereDate('end_time','>=',$from)->whereDate('end_time','<=',$to)->where('status', 'TRADE_FINISHED');
                    break;
                case 'created_at':
                    $model = $model->whereDate('created_at','>=',$from)->whereDate('created_at','<=',$to);
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
            $value->login_account = isset($user_info->login_account)?$user_info->login_account:'数据丢失';
            $value->trade_mobile = isset($user_info->mobile)?$user_info->mobile:'数据丢失';
            $value->receiver_addr_info = $value->receiver_province.$value->receiver_city.$value->receiver_county.$value->receiver_address;
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
                //$order['end_time'] = $value->end_time ?? '--';
                $order['activity_sign_text'] = $value->activity_sign_text ?? '线上交易';
                $order_list[] = $order;
            }

//            $order_list = array_merge($order_list,$value->trade_order->toArray());
        }
        /*$return['trade']['tHeader'] = ['订单号','所属店铺','用户账号','用户手机','订单状态','取消状态','取消原因','下单时间','支付方式','支付时间','实付金额','积分抵消','总价','邮费','优惠金额','买家获得积分','买家消耗积分','收货人姓名','收货人电话','收货人地址','买家留言','卖家备注','提货方式','快递方式','快递单号','提货码','提货状态','自提地址'];
        $return['trade']['filterVal'] = ['tid','shop_name','login_account','trade_mobile','status_text','cancel_text','cancel_reason','created_at','pay_type_text','pay_time','trade_amount','points_fee','trade_total_fee','post_fee','discount_fee','obtain_point_fee','consume_point_fee','receiver_name','receiver_tel','receiver_addr_info','buyer_message','shop_memo','pick_type_name','shipping_type','invoice_no','pick_code','pick_statue_text','ziti_addr'];
        $return['trade']['list'] = $res;*/
        $return['order']['tHeader'] = ['所属项目','商户订单号','订单号','子订单号','商品名称','SKU信息','商品货号','商品价格','商品成本价','商品市场价','购买数量','子订单实付金额','应付金额','优惠分摊','积分抵扣的金额','退款金额','所属店铺','用户账号','用户手机','订单状态','取消状态','取消原因','下单时间','支付方式','支付时间','总实付金额','积分抵消','总价','邮费','优惠金额','买家获得积分','买家消耗积分','收货人姓名','收货人电话','收货人地址','买家留言','卖家备注','提货方式','快递方式','快递单号','提货码','提货状态','自提地址','用户确认收货时间/商品核销时间',//'订单完成时间',
            '活动渠道'];
        $return['order']['filterVal'] = ['gm_name','payment_id','tid','oid','goods_name','sku_info','goods_serial','goods_price','goods_cost','goods_marketprice','quantity','amount_text','total_fee','avg_discount_fee','avg_points_fee','refund_fee_text','shop_name','login_account','trade_mobile','status_text','cancel_text','cancel_reason','created_at','pay_type_text','pay_time','trade_amount','points_fee','trade_total_fee','post_fee','discount_fee','obtain_point_fee','consume_point_fee','receiver_name','receiver_tel','receiver_addr_info','buyer_message','shop_memo','pick_type_name','shipping_type','invoice_no','pick_code','pick_statue_text','ziti_addr','confirm_at',//'end_time',
            'activity_sign_text'];

        $return['order']['list'] = $order_list;
        return $this->resSuccess($return);
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
     * 成本价订单列表导出
     *
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodsCostDown(Request $request, TradePolymorphicRepository $repository)
    {
        $input_data = $request->all();

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
     * 商品统计列表
     *
     * @Author Huiho
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodSaleList(Request $request, TradePolymorphicRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = $input_data['per_page'] ?? 15;
        $input_data['total_data_status'] = true;

        $lists = $repository->GoodSaleSearch($input_data);

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        if (empty($lists))
        {
            return $this->resFailed(700);
        }

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
     * 销售日报列表
     *
     * @Author Huiho
     * @return \Illuminate\Http\JsonResponse
     */
    public function DailySalesList(Request $request, TradePolymorphicRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = $input_data['per_page'] ?? 15;
        $input_data['total_data_status'] = true;

        $lists = $repository->dailySalesSearch($input_data);


//        if (empty($lists))
//        {
//            return $this->resFailed(700);
//        }

        if (empty($lists['total_fee_data']))
        {
            $lists['total_fee_data'] = [];
        }

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->dailySalesListFields(),
            'total_fee_data' => $total_fee_data,

        ]);

    }

    /**
     * 销售日报列表导出
     *
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function DailySalesDown(Request $request, TradePolymorphicRepository $repository)
    {
        $input_data = $request->all();
        $lists = $repository->dailySalesSearch($input_data, 1);
//
//        if (empty($lists)) {
//            return $this->resFailed(700);
//        }

        $title = $repository->dailySalesListFields();

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

        //转换shop_id
        if (isset($input_data['shop_name']))
        {
            $shop = \ShopEM\Models\Shop::where('shop_name',$input_data['shop_name'])->first();
            if (!$shop)
            {
                return $this->resFailed(414, '找不到该店铺');
            }
            unset($input_data['shop_name']);
            $input_data['shop_id'] = $shop->id;
        }

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
        $insert['type'] = 'GoodsCost';
        $insert['desc'] = json_encode($input_data);
        $insert['gm_id'] = 0;

        $res = DownloadLog::create($insert);

        $data['log_id'] = $res['id'];
        DownloadLogAct::dispatch($data);
        return $this->resSuccess('导出中请等待!');
    }

    /**
     * 订单库存回传日志列表
     * @param Request $request
     * @param TradeStockReturnLogRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTradeStockReturnLogList(Request $request,TradeStockReturnLogRepository $repository)
    {
        $data = $request->only('gm_id','status','tid','per_page');
        $lists = $repository->search($data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listFields(),
        ]);
    }
}
