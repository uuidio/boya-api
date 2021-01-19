<?php
/**
 * @Filename TradeUserController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\BuildTradeRequest;
use ShopEM\Http\Requests\Shop\TradeCancelRequest;
use ShopEM\Jobs\CloseTrade;
use ShopEM\Models\Activity;
use ShopEM\Models\CouponStockOnline;
use ShopEM\Models\GroupsUserJoin;
use ShopEM\Models\SecKillOrder;
use ShopEM\Models\Shop;
use ShopEM\Models\TradeActivityDetail;
use ShopEM\Services\TradeService;
use ShopEM\Services\CartService;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\Payment;
use ShopEM\Jobs\CloseSecKillTrade;
use ShopEM\Services\PaymentService;
use ShopEM\Services\GoodsService;


class TradeUserController extends BaseController
{

    /**
     * 购物车提交
     *
     * @Author hfh_wind
     * @param BuildTradeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(BuildTradeRequest $request)
    {
        $user_id = $this->user->id;
//        $user_id = $request['user_id'];
        $input_data = $request->only('pay_app', 'addr_id', 'coupon_ids', 'pick_type', 'ziti_addr', 'buyer_message',
            'fastbuy', 'recharge_info', 'platform_coupon_id', 'act_ids','pay_type');
        $input_data['mobile'] = $this->user->mobile;
        $input_data['addr_id'] = $input_data['addr_id'] ?? '';
        $input_data['per_page'] = config('app.app_per_page');
        //0是快递,1是自提
        if (empty($input_data['pick_type']) && empty($input_data['addr_id'])) {
            return $this->resFailed(700, "请选择地址!");
        }


        //避免并发生成订单
        /*    $orderNum="seconds_orders";
            $seconds_count=Redis::incr($orderNum);
            if($seconds_count==1){
                Redis::expire($orderNum,2);// 设置有效期为 2 秒
            }
            if ($seconds_count > 1) {
                return true ;
            }*/

        $coupon_ids = is_array($request->coupon_ids) ? $request->coupon_ids : json_decode($request->coupon_ids, true);
        if ($coupon_ids) {
            $coupon_ids = array_column($coupon_ids, 'coupon_id', 'shop_id');
            $input_data['coupon_data'] = $coupon_ids;
        }

        $act_ids = is_array($request->act_ids) ? $request->act_ids : json_decode($request->act_ids, true);
        if ($act_ids) {
            $act_ids = array_column($act_ids, 'act_id', 'shop_id');
            $input_data['act_data'] = $act_ids;
            foreach ($act_ids as $shop_id => $act_id) {
                if (isset($coupon_ids[$shop_id])) {
                    return $this->resFailed(700, "满减满折活动与店铺优惠券不能共享!");
                }
            }
        }

        //获取使用的活动
        /*if ($request->has('actSign') && $request->actSign == 'selected_act') {
            $key = md5($user_id . 'selected_act');
            $act_ids = Redis::get($key);
            if ($act_ids) {
                $act_ids = json_decode($act_ids, true);
                $input_data['act_ids'] = $act_ids;
                $act_ids = array_column($act_ids, 'act_id', 'shop_id');
                $input_data['act_data'] = $act_ids;
            }
        }*/
        $type = isset($input_data['fastbuy']) ? $input_data['fastbuy'] : 0;
        $gm_id = CartService::checkOrderGmid($user_id,$type);
        $shop_ids = CartService::checkOrderShopIds($user_id, $type);

        //开启积分抵扣时才接收积分抵扣金额和使用积分
        $isOpenPointDeduction = TradeService::isOpenPointDeduction($gm_id);
        if ($isOpenPointDeduction)
        {
            //必须店铺同时都开启积分抵扣功能 ego+
            $shopOpenPoint = Shop::where('open_point_deduction',0)->whereIn('id',$shop_ids)->doesntExist();
            if ($shopOpenPoint)
            {
                if ($request->has('consume_point_fee')) {
                    $input_data['consume_point_fee'] = $request->consume_point_fee;
                }
                if ($request->has('points_fee')) {
                    $input_data['points_fee'] = $request->points_fee;
                }
            }
        }

        try {
            $tradeResult = self::storeTrade($user_id, $input_data);
            if (!empty($tradeResult)) {
                if ($coupon_ids) {
                    foreach ($coupon_ids as $coupon_v) {
                        $res_coupon = CouponStockOnline::where([
                            'coupon_id' => $coupon_v,
                            'user_id'   => $user_id,
                            'status'    => 1
                        ])->first();
                        if ($res_coupon) {
                            CouponStockOnline::succUseCoupon($res_coupon);
                            // CouponStockOnline::where(['id' => $res_coupon->id])->update(['status' => 2]);
                        }
                    }
                }


                //处理团购
                if (isset($input_data['fastbuy'])) {
                    $key = md5($user_id . 'cart_fastbuy');
                    //var_dump($params);
                    $fastData = Redis::get($key);
                    $getGroupInfo = $fastInfo = json_decode($fastData, true);
                    if (isset($fastInfo['actSign']) && $fastInfo['actSign'] == 'is_group') {
                        $GroupService = new \ShopEM\Services\GroupService;

                        if (!isset($fastInfo['groups_bn'])) {
                            //开团-----go
                            //开团id
                            $act_id = $fastInfo['activity_id'] . $tradeResult['payment_id'];

                         // $res = $GroupService->CheckGroup($user_id, $act_id);
                            //支付成功回调里记录成团信息
                            $fastInfo['payment_id'] = $tradeResult['payment_id'];
                            $fastInfo['tid'] = $tradeResult['tids'][0];
                            $fastInfo['act_bn'] = $act_id;
                            $key_group = $user_id . 'group' . $tradeResult['payment_id'];
                            //拼团等候时间
                            $set_time = $fastInfo['group_info']['group_validhours'] * 3600;
                            $fastInfo = json_encode($fastInfo);
                            Redis::setex($key_group, $set_time, $fastInfo);
                            $set_type='group';
                        } else {
                            //处理团员入团
                            $act_id = $fastInfo['groups_bn'];
                            $fastInfo['payment_id'] = $tradeResult['payment_id'];
                            $fastInfo['tid'] = $tradeResult['tids'][0];

                            $main_user_id = $GroupService->CheckGroup($act_id);//团长id

                            if ($main_user_id == $user_id) {
                                return $this->resFailed(700, "这是您的团,不能参与!");
                            }

                            //支付成功回调里记录成团信息
                            $fastInfo['payment_id'] = $tradeResult['payment_id'];
                            $key_group = $user_id . 'groupjoin' . $tradeResult['payment_id'];
                            //拼团等候时间
                            $set_time = $fastInfo['group_info']['group_validhours'] * 3600;
                            $fastInfo['main_user_id'] = $main_user_id;
                            //dd($fastInfo);
                            $fastGroup = json_encode($fastInfo);
                            Redis::setex($key_group, $set_time, $fastGroup);
                            //生成订单之后,生成团购订单记录(未付款)
                            $GroupService->createGroupJoin($user_id, $fastInfo, $act_id);
                            $set_type='groupjoin';
                        }

                        //扣减库存
                        $group_sale_stock_key = $getGroupInfo['group_info']['sku_id'] . '_group_sale_stock_' . $getGroupInfo['group_info']['id'];//已经销售
                        $group_stock_key = $getGroupInfo['group_info']['sku_id'] . "_group_stock_" . $getGroupInfo['group_info']['id'];

                        $group_sale_stock = Redis::get($group_sale_stock_key);
                        $group_stock = Redis::get($group_stock_key);

                        if ($group_stock > $group_sale_stock) {

                            $group_decr = Redis::incr($group_sale_stock_key);//增加一个已购团购库存

                            $group_payment_key = 'group_pay_'.$tradeResult['payment_id'];
                            $group_pay['set_type']=$set_type;
                            $group_pay['user_id']=$user_id;
                            $group_pay=json_encode($group_pay);
                            //记录团购订单,付款的时候判断是否过期
                            Redis::set($group_payment_key,$group_pay);
                        }

                    }
                }


                //把平台券标为已使用
                if (isset($input_data['platform_coupon_id'])) {
                    $platform_coupon_id = $input_data['platform_coupon_id'];
                    $res_coupon = CouponStockOnline::where([
                        'coupon_id' => $platform_coupon_id,
                        'user_id'   => $user_id,
                        'status'    => 1,
                    ])->first();
                    if ($res_coupon) {
                        CouponStockOnline::succUseCoupon($res_coupon,$tradeResult['payment_id']);
                        // CouponStockOnline::where(['id' => $res_coupon->id])->update([
                        //     'status'     => 2,
                        //     'payment_id' => $tradeResult['payment_id']
                        // ]);
                    }
                }

                //清空已选中购物车的商品
                if (isset($input_data['fastbuy']) && !empty($input_data['fastbuy'])) {
                    $key = md5($user_id . 'cart_fastbuy');
                    Redis::del($key);
                } else {
                    CartService::destroyCart($user_id);
                }

                //清空已选中的优惠活动
                if ($request->has('actSign') && $request->actSign == 'selected_act') {
                    $key = md5($user_id . 'selected_act');
                    Redis::del($key);
                }
                return $this->resSuccess($tradeResult);
            }

        } catch (\Exception $e) {
            return $this->resFailed(700, $e->getMessage());
        }
    }


    /**
     *  生成订单
     *
     * @Author hfh_wind
     * @param $user_id
     * @param $input_data
     * @return array
     * @throws \Exception
     */
    public static function storeTrade($user_id, $input_data)
    {
        $activity = new \ShopEM\Services\Marketing\Activity();
        $type = isset($input_data['fastbuy']) ? $input_data['fastbuy'] : 0;
        $cart_goods = CartService::checkOrderGoods($user_id, $type);
        $gm_id = CartService::checkOrderGmid($user_id,$type);
        $user_mobile = $input_data['mobile'];

        if (!count($cart_goods) || empty($cart_goods)) {
            throw new \Exception('请求频繁，请重新提交订单');
        }

        //商品是否可以购买的判断
        $open_point = 1;
        foreach ($cart_goods as $shopId => $shopCartData) {
            foreach ($shopCartData as $item_key => $item_val) {
                if (!isset($item_val['params']) || $item_val['params'] != 'seckill') {
                    $goodsSaleAble = GoodsService::saleAble($item_val['sku_id'], $item_val['user_id'],
                        $item_val['quantity']);
                    if ($goodsSaleAble['code'] === 0) {
                        throw new \Exception($goodsSaleAble['message']);
                    }
                }
                if (isset($item_val['params']) && ($item_val['params'] == 'seckill')) {
                    $open_point = 0;
                }

                /*$seckill_good_key = 'seckill_good_' . $item_val['sku_id']; //秒杀商品的缓存key
                if (Cache::get($seckill_good_key)) {
                    throw new \Exception('商品"'. $item_val['goods_name'] .'"正在参与秒杀');
                }*/
                $cart_goods[$shopId][$item_key]['transport_id'] = $goodsSaleAble['data_goods']->transport_id ?? $item_val['transport_id']; //获取商品当前的运费模板
            }
        }

        //格式化订单数据，含订单优惠, 金额数据，运费计算
        $tradeData = TradeService::_chgdata($user_id, $cart_goods, $input_data);

        $result = [];
        //记录tid和商品名，用于扣减积分
        $tid_array = [];
        $goods_name_array = [];
        $consume_point_fee = 0;
        $seckill_sign = 0;//记录是否秒杀
        $point_goods_sign = false;//记录是否是积分商品
        DB::beginTransaction();
        try {
            //校验使用积分是否正常
            if ($tradeData['payment']['consume_point_fee']) {
                if ($open_point == 0) {
                    throw new \LogicException('不能使用积分抵扣!');
                }
                $compute = TradeService::computePoint($tradeData['payment']['amount'],$gm_id);
                //判断是否相同
                if ($tradeData['payment']['consume_point_fee'] != $compute['consume_point_fee'] || $tradeData['payment']['points_fee'] != $compute['points_fee']) {
                    throw new \LogicException('积分使用出现异常!');
                }

                //扣减金额
                $tradeData['payment']['amount'] -= $compute['points_fee'];
            }

            $payment_id = $tradeData['payment']['payment_id'];
            //关闭订单
            CloseTrade::dispatch($payment_id);

            //保存订单数据
            foreach ($tradeData as $shopId => $row) {
                if (isset($row['trade'])) {
                    if ($tradeData['payment']['consume_point_fee']) {
                        //使用积分的分摊
                        $point_data = TradeService::getPointData($tradeData['payment']['amount'],
                            $tradeData['payment']['points_fee'], $row['trade']['amount'],
                            $tradeData['payment']['consume_point_fee']);
                        $row['trade']['amount'] = $row['trade']['amount'] - $point_data['points_fee'];
                        $row['trade']['points_fee'] = $point_data['points_fee'];
                        $row['trade']['consume_point_fee'] = $point_data['consume_point_fee'];
                    }

                    $result = Trade::create($row['trade']);
                    $tid_array[] = $row['trade']['tid'];
                    //保存支付单据
                    TradePaybill::create(TradeService::makePaybillInfo($payment_id, $row['trade']['tid'], $user_id,
                        $row['trade']['amount']));


                    //积分专区商品
                    if (isset($row['trade']['consume_point_fee'])) {
                        $consume_point_fee += $row['trade']['consume_point_fee'];
                    }
                    if ($row['trade']['activity_sign'] == 'point_goods')
                    {
                        $point_goods_sign = true;
                    }
                }
                if (!$result) {
                    throw new \LogicException('主订单生成失败!');
                }


                if (isset($row['order'])) {
                    $params['shop_id'] = $row['trade']['shop_id'];
                    $act_lists = [];
                    if (isset($input_data['act_ids']) && isset($input_data['act_data'][$params['shop_id']])) {
                        $params['act_ids'] = [$input_data['act_data'][$params['shop_id']]];
                        $act_lists = $activity->userfulList($params, $cart_goods[$row['trade']['shop_id']]);
                    }
                    foreach ($row['order'] as $order => $order_val) {
                        //记录优惠活动具体信息
                        if ($act_lists) {
                            foreach ($act_lists as $act) {
                                if (in_array($order_val['goods_id'], $act['goods_ids'])) {
                                    $activity_type = '';
                                    $activity_tag = '';
                                    switch ($act['type']) {
                                        case 1:
                                            $activity_type = 'fullminus';
                                            $activity_tag = '满减';
                                            break;
                                        case 2:
                                            $activity_type = 'discount';
                                            $activity_tag = '满折';
                                            break;
                                    }
                                    $data = [
                                        'tid'           => $order_val['tid'],
                                        'oid'           => $order_val['oid'],
                                        'user_id'       => $order_val['user_id'],
                                        'activity_id'   => $act['id'],
                                        'goods_id'      => $order_val['goods_id'],
                                        'sku_id'        => $order_val['sku_id'],
                                        'activity_type' => $activity_type,
                                        'activity_tag'  => $activity_tag,
                                        'activity_name' => $act['name'],
                                        'activity_desc' => $act['desc'],
                                        'rule'          => $act['rule'],
                                    ];
                                    TradeActivityDetail::create($data);
                                }
                            }
                        }

                        if (isset($order_val['activity_sign']) && $order_val['activity_type'] == 'seckill') {
                            $seckill_sign = 1;
                            $seckill_order_key = "seckill_order_" . $user_id;
                            $seckillOrder_redis = Redis::get($seckill_order_key);
                            //将秒杀订单改为未付款
                            $seckillOrder = SecKillOrder::where([
                                'user_id'       => $user_id,
                                'seckill_ap_id' => $order_val['activity_sign'],
                                'sku_id'        => $order_val['sku_id'],
                                //'id'    => $seckillOrder_redis,
                                'state'         => '0',
                            ])->update(['state' => '2', 'tid' => $row['trade']['tid'], 'payment_id' => $payment_id]);

                            $user_queue_key = "seckill_" . $order_val['sku_id'] . "_user_" . $order_val['activity_sign'];//当前商品队列的用户情况
                            $record_key = "seckill_" . $order_val['sku_id'] . "_buy_record_" . $order_val['activity_sign'] . "_u_id_" . $user_id;//标识已购买

                            $goods_buy_key = "seckill_" . $order_val['sku_id'] . "_good_record_" . $order_val['activity_sign'];//已买库存
                            $goods_queue_key = "seckill_" . $order_val['sku_id'] . "_good_" . $order_val['activity_sign'];//当前商品的库存队列

                            $redis = new Redis();

                            $goods_stock = $redis::get($goods_queue_key);//当前商品的库存队列

                            $good_sold = $redis::get($goods_buy_key) ? $redis::get($goods_buy_key) : 0;//已卖

                            if ($goods_stock <= $good_sold) {
                                throw new \LogicException('商品已售罄!');
                            }

                            $user_dictate_redis = $redis::hGet($user_queue_key, $order_val['user_id']);
                            if (!$user_dictate_redis) {
                                throw new \LogicException('无秒杀资格!');
                            }
                        }


                        //处理团购
                        if (isset($order_val['activity_sign']) && $order_val['activity_type'] == 'is_group') {

                            $key = md5($user_id . 'cart_fastbuy');
                            //var_dump($params);
                            $fastData = Redis::get($key);
                            $getGroupInfo = json_decode($fastData, true);
                            //判断团是否已失败
                            if (isset($getGroupInfo['groups_bn'])) {
                                $act_id = $getGroupInfo['groups_bn'];
                                $group_member = Redis::hGetAll($act_id);
                                if (empty($group_member)) {
                                    //所选的团不存在
                                    throw new \LogicException('所选的团不存在!');
                                }
                            }
                            //扣减库存
                            $group_sale_stock_key = $getGroupInfo['group_info']['sku_id'] . '_group_sale_stock_' . $getGroupInfo['group_info']['id'];//已经销售
                            $group_stock_key = $getGroupInfo['group_info']['sku_id'] . "_group_stock_" . $getGroupInfo['group_info']['id'];

                            $group_sale_stock = Redis::get($group_sale_stock_key);
                            $group_stock = Redis::get($group_stock_key);

                            /*if ($group_stock <= $group_sale_stock) {
                                throw new \LogicException('团购无库存,请重试!');
                            }*/
                        }

                        //处理积分商品
                        if (isset($order_val['activity_sign']) && $order_val['activity_type'] == 'point_goods') {
                            $point_goods = \ShopEM\Models\PointActivityGoods::where('goods_id', $order_val['goods_id'])->latest()->first();
                            if (!$point_goods) {
                                throw new \LogicException($order_val['goods_name'] . '非积分活动商品');
                            }
                            //积分商品限制判断
                            $pointGoodsObj = new \ShopEM\Services\Marketing\PointGoods;
                            $check = $pointGoodsObj->buyCheck($order_val['user_id'],$point_goods,$order_val['quantity']);
                            if (isset($check['code']) && $check['code']>0) {
                                throw new \LogicException($check['msg']);
                            }
                            // $cache_point_key = 'point_goods_obj_id_'.$point_goods->id;
                            // if (Cache::has($cache_point_key)) {
                            //     $cache_point_id = Cache::get($cache_point_key,0);
                            //     if ($cache_point_id > 0) {
                            //         throw new \LogicException('活动火爆，请重试');
                            //     }
                            // }
                            // Cache::forever($cache_point_key,1);
                        }


                        if ($tradeData['payment']['consume_point_fee']) {
                            //使用积分的分摊
                            $point_data = TradeService::getPointData($row['trade']['amount'],
                            $row['trade']['points_fee'], $order_val['amount'], $row['trade']['consume_point_fee']);
                            $order_val['amount'] = $order_val['amount'] - $point_data['points_fee'];
                            $order_val['avg_points_fee'] = $point_data['points_fee'];
                        }
                        $order_val['profit'] = $order_val['goods_price'] - $order_val['goods_cost'];
                        // 保存子订单
                        TradeOrder::create($order_val);

                        $goods_name_array[]['goods_name'] = $order_val['goods_name'];

                        if ($seckill_sign == 1) {
                            //关闭秒杀订单
                            CloseSecKillTrade::dispatch($order_val['tid']);
                        }
                    }
                }
            }

            // 保存支付单
            Payment::create($tradeData['payment']);

            //下单就扣减库存
            PaymentService::freezeGoodStore($payment_id, true);

            //秒杀标识
            if ($seckill_sign == 1) {

                $goods_stock = $redis::get($goods_queue_key);//当前商品的库存队列

                $good_sold = $redis::get($goods_buy_key) ? $redis::get($goods_buy_key) : 0;//已卖

                if ($goods_stock > $good_sold) {
                    //已购买的
                    $act_sold = $redis::incr($goods_buy_key);

                    if ($act_sold > 0 && $act_sold <= $goods_stock) {
                        //删除订单标识
                        $redis::del($seckill_order_key);
                        //标识已购买,一直存在
                        $redis::set($record_key, $payment_id);
                    } else {
                        throw new \LogicException(' 不好意思呢，已经被抢完了!');
                    }
                }
            }

            //如果使用了益田积分，则扣减益田积分
            if (isset($tradeData['payment']['consume_point_fee']) && $tradeData['payment']['consume_point_fee']) {
                $consume_point_fee = $tradeData['payment']['consume_point_fee'];
            }
            if ($consume_point_fee) {

                $yitiangroup_service = new \ShopEM\Services\YitianGroupServices($gm_id);
                $user_point = $yitiangroup_service->updateUserRewardTotal($user_mobile);
                if ($user_point < $consume_point_fee) {
                    throw new \LogicException('积分不够扣减!');
                }

                $tids = implode($tid_array, '\\');
                $pointdata = $expdata = array(
                    'user_id'  => $tradeData['payment']['user_id'],
                    // 'order'    => $goods_name_array, v1
                    'order'    => json_encode($goods_name_array),   //v2
                    'type'     => 'consume',
                    'num'      => $consume_point_fee,
                    // 'behavior' => "订单号： " . $tids,
                    'behavior' => "积分抵扣",
                    'remark'   => '交易扣减',
                    'log_type' => $point_goods_sign ? 'exchange' : 'trade',
                    'log_obj'  => $tids,
                    'payment_id' => $payment_id,
                    'gm_id'    => $gm_id,
                );
                //改成支付成功后才扣减积分 v2 弃用
                \ShopEM\Models\TradeWillPaymentPoint::create($pointdata);
                //v1
                // $yitiangroup_service = new \ShopEM\Services\YitianGroupServices($gm_id);
                $pointdata['order'] = $goods_name_array;
                $result = $yitiangroup_service->updateUserYitianPoint($pointdata);
                if ($result === false) {
                    pointErrorLog($user_mobile . '-积分扣减失败::'.json_encode($pointdata));
                    throw new \LogicException('积分扣减失败!');
                }
                \ShopEM\Models\TradeWillPaymentPoint::where(['payment_id'=>$payment_id,'status'=>0])->update(['status'=>1]);
            }

            DB::commit();

            return ['payment_id' => $payment_id, 'tids' => $tid_array];
        } catch (\Exception $e) {
            testLog($e->getMessage());
            DB::rollBack();
            throw new \Exception('订单创建失败,' . $e->getMessage());
        }
    }

    /**
     * 用户申请取消订单/平台强制取消订单
     *
     * @Author hfh_wind
     * @param TradeCancelRequest $params
     * @return array
     */
    public function tradeCancelCreate(TradeCancelRequest $params)
    {
        $params = $params->all();

        $create = new \ShopEM\Services\TradeService;

        $user_id = $this->user->id;

        $service = new   \ShopEM\Services\SecKillService();
        $order = $service->isActionAllowed($user_id, "cancel_order", 2 * 1000, 1);
        if ($order) {
            $tid = $params['tid'];

            $bill = TradePaybill::where('tid',$tid)->first();
            $payment = Payment::where('payment_id',$bill->payment_id)->select('pay_app','payment_id')->first();
            if (in_array($payment->pay_app,['WalletPhysical','WalletVirtual'])) {
                return $this->resFailed(500, '钱包支付订单不支持取消申请');
            }

            $cancelReason = trim($params['cancel_reason']);
            $cancelFromType = $user_id ? 'buyer' : 'shopadmin';

            $group_trade = Trade::where(['tid' => $tid, 'activity_sign' => 'is_group'])->first();
            //团购订单走团购取消订单流程
            if ($group_trade) {
                $user_orders = DB::table('groups_user_orders')->where('tid', '=', $tid)->where('status', '=',2)->first();
                if (!$user_orders) {
                    $GroupService = new \ShopEM\Services\GroupService;
                    $GroupService->clearGroupInfo($tid, 0);
                }
            }
            $create->setCancelFromType($cancelFromType)
                ->setCancelId($user_id)
                ->tradeCancelCreate($tid, $cancelReason, '');

            return $this->resSuccess([], '申请成功!!');
        }

        return $this->resFailed(700, '点击过快');
    }


    /**
     * 确认收货
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmReceipt(Request $request)
    {
        $tid = $request->tid;
        if (!$tid) {
            return $this->resFailed(406);
        }
        try {
            $TradeService = new \ShopEM\Services\TradeService;
            $TradeService->confirmReceipt($tid, $this->user['id']);
            /*$trade = \ShopEM\Models\TradeRelation::where('tid', $tid)->get();
            if (count($trade) > 0) {
                $lhyServices = new \ShopEM\Services\LhyPushService();
                foreach ($trade as $key => $value) {
                    $lhyServices->finishTrade($value->rid);
                }
            }*/
        } catch (\LogicException $e) {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess('订单确认收货完成');
    }


    // 选择物流后
    public function Chooeslogistics()
    {
        $logistics = DB::table('logistics_dlycorps')->where('is_show',1)->select('corp_code', 'corp_name')->get();
        return $this->resSuccess($logistics);
    }


}
