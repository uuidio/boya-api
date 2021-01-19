<?php
/**
 * @Filename        TradeService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use ShopEM\Jobs\SendSubscribeMessage;
use ShopEM\Jobs\TradePush;
use ShopEM\Jobs\TradePushErp;
use ShopEM\Models\Config;
use ShopEM\Models\Coupon;
use ShopEM\Models\CouponStockOnline;
use ShopEM\Models\Goods;
use ShopEM\Models\GoodsSku;
use ShopEM\Models\GroupsUserJoin;
use ShopEM\Models\GroupsUserOrder;
use ShopEM\Models\PartnerRelatedLog;
use ShopEM\Models\RelatedLogs;
use ShopEM\Models\SecKillApplie;
use ShopEM\Models\SecKillGood;
use ShopEM\Models\Shop;
use ShopEM\Models\ShopAttr;
use ShopEM\Models\TradeCancel;
use ShopEM\Models\Payment;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeEstimates;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\TradeRefunds;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserAddress;
use ShopEM\Models\UserRelYitianInfo;
use ShopEM\Models\TradeLog;
use ShopEM\Models\UserDeposit;
use ShopEM\Models\WechatMessage;
use ShopEM\Models\PanymentType;
use ShopEM\Models\GmPlatform;
use ShopEM\Services\User\UserPointsService;
use ShopEM\Services\User\UserExperienceService;
use ShopEM\Services\Marketing\Activity;


class TradeService
{


    /**
     * 当前发货生成的发货单号
     */
    protected $deliveryId = null;

    /**
     * 生成ID
     *
     * @Author moocde <mo@mocode.cn>
     * @param $type
     * @return bool|string
     * @throws \Exception
     */
    public static function createId($type,$payParams=[])
    {
        $id = '';
        switch ($type) {
            case 'payment_id':
                // $id = '100';
                return self::createPayId($payParams);
                break;
            case 'tid':
                $id = '300';
                break;
            case 'oid':
                $id = '500';
                break;
            case 'aftersales_bn':
                $id = '700';
                break;
            case 'refund_bn':
                $id = '800';
                break;
            case 'trade_cancel':
                $id = '900';
                break;
            case 'cash':
                $id = '434';
                break;
        }
        $id .= date('ymdHis');
        $cache_key = 'create_payment_id_' . $id;
        for ($i = 0; $i < 10; $i++) {
            // 随机生成 6 位的数字
            $id = $id . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            // 判断是否已经存在
            $cache_value = !empty(Cache::get($cache_key)) ? Cache::get($cache_key) : [];
            if (!in_array($id, $cache_value)) {
                array_push($cache_value, $id);
                Cache::put($cache_key, $cache_value, Carbon::now()->addSeconds(10));
                return $id;
            }
            usleep(100);
        }

        return false;
    }

    //重新构造支付单号
    public static function createPayId($data=[])
    {
        #交易平台 pos-61 tp-62 ，商城-63
        $trade_code = '63';
        #交易平台2+支付方式4+项目编号3+pos编号6+明文时间戳12+流水号3
        $gm_id = $data['gm_id'] ?? 0 ;
        #支付方式4
        $pay_type = $data['pay_type'] ?? 'wechat' ;
        $pay_code = (string)PanymentType::getPayCode($gm_id,$pay_type);
        if(empty($pay_code)) $pay_code = '0000';
        $pay_code = preg_replace('/[^0-9.]/','0',substr($pay_code,-4));
        
        #项目编号3
        $corp_code = (string)GmPlatform::getCorpCode($gm_id);
        if(empty($corp_code)) $corp_code = '000';
        $corp_code = preg_replace('/[^0-9.]/','0',substr($corp_code,-3));

        #pos编号6
        $shop_ids = $data['shop_ids'] ?? [] ;
        $pos_code =  Shop::getAnShopPosCode($shop_ids);
        if(empty($pos_code)) $pos_code = '999999';
        $pos_code = preg_replace('/[^0-9.]/','0',substr($pos_code,-6));

        $id = $trade_code.$pay_code.$corp_code.$pos_code.date('ymdHis');

        $cache_key = 'create_new_payment_gm_' . $gm_id;
        for ($i = 0; $i < 10; $i++) {
            // 随机生成 3 位的数字
            $id = $id . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
            // 判断是否已经存在
            $cache_value = !empty(Cache::get($cache_key)) ? Cache::get($cache_key) : [];
            if (!in_array($id, $cache_value)) {
                array_push($cache_value, $id);
                Cache::put($cache_key, $cache_value, Carbon::now()->addSeconds(10));
                return $id;
            }
            usleep(100);
        }

        return false;
    }


    /**
     *
     *  返回订单保存基本数据
     *
     * @Author hfh_wind
     * @param $tradeParams
     * @param array $aCart
     * @return mixed
     */
    public static function _chgdata($user_id, $aCart = array(), $input_data)
    {
        $total = 0;//实付金额
        $platform_coupon_total = 0; //符合平台优惠券的金额
        $input_data['user_id'] = $user_id;
        //创建店铺主订单公用的数据
        $trade_addr = $user_addr = self::buyerAddr($user_id, $input_data);
        // $user_addr = self::buyerAddr($user_id, $input_data);

        $orderData = [];
        $cart_calc = [];
        $shop_ids_arr = [];

        //判断是否使用了可用的平台券
        $in_platform_coupon = false;
        if (isset($input_data['platform_coupon_id'])) {
            $in_platform_coupon = CouponStockOnline::where([
                'coupon_id' => $input_data['platform_coupon_id'],
                'user_id'   => $user_id,
                'status'    => 1
            ])->first();
        }

        foreach ($aCart as $shopId => $shopCartData) {
            $discount_fee = 0;//优惠金额
            $buyer_message = '';
            $shop_info = Shop::find($shopId);
            $shop_ids_arr[] = $shopId;
            $shop_gm_id = $shop_info->gm_id;

            $cart_calc = self::cartGoodsCalc($shopCartData, $shop_info, $input_data, $user_addr);

            if (!empty($cart_calc['discount_fee'])) {
                $discount_fee = $cart_calc['discount_fee'];
            }
            if (!empty($input_data['buyer_message'])) {
                $input_data['buyer_message'] = is_array($input_data['buyer_message']) ? $input_data['buyer_message'] : json_decode($input_data['buyer_message'],
                    true);

                $search = array_search($shopId, array_column($input_data['buyer_message'], 'shop_id'));
                if ($search !== null) {
                    $buyer_message = $input_data['buyer_message'][$search]['buyer_message'];
                }
            }

            $tid = self::createId('tid');
            // 订单主表数据
            $shopTradeData = [
                'tid'              => $tid,
                'shop_id'          => $shopId,
                'user_id'          => $user_id,
                'amount'           => ($cart_calc['amount'] > 0) ? $cart_calc['amount'] : 0,
                'total_fee'        => $cart_calc['total_fee'],
                'points_fee'       => $cart_calc['points_fee'],
                'post_fee'         => $cart_calc['post_fee'],
                'discount_fee'     => $discount_fee,
                //'obtain_point_fee' => $cart_calc['total_fee'],
                'ip'               => request()->getClientIp(),
                'buyer_message'    => $buyer_message,
                'activity_sign'    => $cart_calc['activity_sign'],
                'activity_sign_id' => $cart_calc['activity_sign_id'],
                'dlytmpl_ids'      => $cart_calc['dlytmpl_ids'],
                'is_first_order'   => $cart_calc['is_first_order'] ?? 0,
                'seller_coupon_discount'   => $cart_calc['seller_coupon_discount'] ?? 0,
                'seller_discount'   => $cart_calc['seller_discount'] ?? 0,
            ];
            $shopTradeData['pick_type'] = $input_data['pick_type'];
            //自提发短信 pick_type=1
            if ($input_data['pick_type'] == 1 || $input_data['pick_type'] == 2) {
                if (isset($input_data['ziti_addr']['ziti_addr'])) {
                    $shopTradeData['ziti_addr'] = $input_data['ziti_addr']['ziti_addr'];
                }
                $shopTradeData['amount'] = $cart_calc['amount'] - $cart_calc['post_fee'];
                $cart_calc['amount'] = $cart_calc['amount'] - $cart_calc['post_fee'];
                $shopTradeData['post_fee'] = 0;
            }
            if (isset($input_data['coupon_data'])) {
                $coupon_id = $input_data['coupon_data'][$shopId] ?? 0;
                if ($coupon_id) {
                    $res_coupon = CouponStockOnline::where([
                        'coupon_id' => $coupon_id,
                        'user_id'   => $user_id,
                        'status'    => 1
                    ])->first();
                    if ($res_coupon) {
                        $res_coupon->tid = $tid;
                        $res_coupon->save();
                    }
                }
            }

            if (isset($trade_addr['area_code'])) {
                unset($trade_addr['area_code']);
            }
            //主订单基本数据
            $orderData[$shopId]['trade'] = array_merge($shopTradeData, $trade_addr);
            $couponService = new \ShopEM\Services\Marketing\Coupon();
            $goods_total = 0;


            foreach ($shopCartData as $k => $cartItem) {
                $cartItem['act_discount_fee'] = $cart_calc['act_discount_fee'];
                $cartItem['is_act_goods'] = false;
                $cartItem['act_goods_total_fee'] = 0;

                if ($cart_calc['act_lists']) {
                    foreach ($cart_calc['act_lists'] as $act) {
                        //给优惠活动的商品带上活动的优惠金额
                        if (in_array($cartItem['goods_id'], $act['goods_ids'])) {
                            $cartItem['act_goods_total_fee'] = $act['price'];
                            $cartItem['act_goods_discount_fee'] = $act['discount_fee'];
                            $cartItem['is_act_goods'] = true;
                        }
                    }
                }

                //子订单数据
                $shopOrderData = self::__orderItemData($shopTradeData, $cartItem);


                //推物分润标识
                if (isset($shopOrderData['is_distribution'])) {
                    $orderData[$shopId]['trade']['is_distribution'] = 1;
                }

                //分成标识
                if (isset($shopOrderData['profit_sign'])) {
                    $orderData[$shopId]['trade']['profit_sign'] = 1;//是否分成订单
                }

                //如果使用了平台券，就把适用商品的总金额存起来（秒杀活动商品不适用）
                // if ($in_platform_coupon && (!isset($cartItem['params']) || $cartItem['params'] != 'seckill')) {
                if ($in_platform_coupon) {
                    $goods_info = [
                        'shop_id'  => $shopOrderData['shop_id'],
                        'gc_id'    => $shopOrderData['gc_id'],
                        'goods_id' => $shopOrderData['goods_id'],
                    ];
                    $is_full = $couponService->isFullPlatformCouponGoods($input_data['platform_coupon_id'],
                        $goods_info);
                    if ($is_full) {
                        $goods_total += $shopOrderData['amount'];
                        $shopOrderData['is_full_platform_coupon'] = true;
                    }
                }


                //处理积分专区的商品
                if (isset($cartItem['actSign']) && $cartItem['actSign'] == 'point_goods') {
                    $orderData[$shopId]['trade']['consume_point_fee'] = $cartItem['point_fee'] * $cartItem['quantity'];
                }

                //减去库存
                   // $this->__minusStore($shopOrderData);
                $orderData[$shopId]['order'][] = $shopOrderData;
            }
            if ($in_platform_coupon) {
                $orderData[$shopId]['trade']['platform_coupon_total'] = $goods_total;
                $platform_coupon_total += $goods_total;//统计符合平台优惠券的金额
            }

           // 支付单表数据
           // $orderData[$shopId]['paybill'] = $shopTradeData;
           // $orderData[$shopId]['paybill']['payment_id'] = $payment_id;

            $total += $cart_calc['amount'];

        }

        //使用了平台优惠券时的优惠计算
        if ($in_platform_coupon) {
            $coupon = Coupon::find($input_data['platform_coupon_id']);
            foreach ($orderData as $shopId => $shopOrderData) {
                //算出主订单的优惠金额
                if ($shopOrderData['trade']['platform_coupon_total']) {
                    $trade_totle = $shopOrderData['trade']['amount'] - $shopOrderData['trade']['post_fee']; //扣减掉运费
                    $discount_fee = self::avgDiscountFee($platform_coupon_total, $coupon['denominations'],
                        $trade_totle);
                    //通过主订单的优惠金额算出子订单的优惠金额
                    foreach ($shopOrderData['order'] as $k => $order) {
                        if (isset($order['is_full_platform_coupon'])) {
                            $order_discount_fee = self::avgDiscountFee($trade_totle,
                                $discount_fee, $order['amount']);
                            $orderData[$shopId]['order'][$k]['amount'] -= $order_discount_fee;
                            $orderData[$shopId]['order'][$k]['avg_discount_fee'] += $order_discount_fee;
                            //unset掉不需要的参数
                            unset($orderData[$shopId]['order'][$k]['is_full_platform_coupon']);
                        }
                    }
                    //unset掉不需要的参数
                    unset($orderData[$shopId]['trade']['platform_coupon_total']);
                    $orderData[$shopId]['trade']['amount'] -= $discount_fee;
                    $orderData[$shopId]['trade']['discount_fee'] += $discount_fee;
                    $orderData[$shopId]['trade']['platform_discount'] = $coupon['denominations'];//新增平台卷优惠金额
                }
            }
            $total -= $coupon['denominations'];
            $orderData['payment']['platform_coupon_fee'] = $coupon['denominations'];

        }

        $payParams['pay_type'] = $input_data['pay_type']??'wechat';
        $payParams['gm_id'] = $shop_gm_id;
        $payParams['shop_ids'] = $shop_ids_arr;

        $payment_id = self::createId('payment_id',$payParams);
        $orderData['payment']['payment_id'] = $payment_id;
        $orderData['payment']['amount'] = ($total > 0) ? $total : 0;
        $orderData['payment']['user_id'] = $user_id;
        $orderData['payment']['consume_point_fee'] = isset($input_data['consume_point_fee']) ? $input_data['consume_point_fee'] : 0;
        $orderData['payment']['points_fee'] = isset($input_data['points_fee']) ? $input_data['points_fee'] : 0;

        return $orderData;
    }


    /**
     * 返回子订单结构
     *
     * @Author hfh_wind
     * @param $tradeParams
     * @param $cartItem
     * @param $shopId
     * @return array
     */
    private static function __orderItemData($shopTradeData, $cartItem)
    {
        $data = [];
        $data['oid'] = self::createId('oid');
        $data['tid'] = $shopTradeData['tid'];
        $data['shop_id'] = $shopTradeData['shop_id'];
        $data['user_id'] = $shopTradeData['user_id'];
        $data['goods_id'] = $cartItem['goods_id'];
        $data['sku_id'] = $cartItem['sku_id'];
        $data['goods_price'] = $cartItem['goods_price'];
        if (isset($cartItem['goods_info']->goods_marketprice)) {
            $data['goods_marketprice'] = $cartItem['goods_info']->goods_marketprice;
        } else if (isset($cartItem['goods_info']['goods_marketprice'])) {
            $data['goods_marketprice'] = $cartItem['goods_info']['goods_marketprice'];
        }
        $data['quantity'] = $cartItem['quantity'];
        $data['goods_name'] = $cartItem['goods_name'];
        $data['goods_image'] = $cartItem['goods_image'];
        $data['gc_id'] = isset($cartItem['goods_info']->gc_id) ? $cartItem['goods_info']->gc_id : $cartItem['goods_info']['gc_id'];

        $distribution_sign = ShopAttr::where('shop_id', $shopTradeData['shop_id'])->first();

        $sku = GoodsSku::find($cartItem['sku_id']);

        $data['rewards']=0;
        //标记分销订单
        if ($distribution_sign['promo_person']) {
            $data['is_distribution'] = 1;
            $data['rewards'] = $sku['rewards'] * $cartItem['quantity']; //推物返利金额
        }

        //分成标识
        $data['profit_sharing']=0;
        if ($sku['profit_sharing']>0) {
            $data['profit_sign'] = 1;//是否分成订单
            $data['profit_sharing'] = $sku['profit_sharing'] * $cartItem['quantity'];//分成金额
        }

        $sku_info = [];
        if ($sku) {
            $spec_name_array = empty($sku['spec_name']) ? null : unserialize($sku['spec_name']);
            if ($spec_name_array && is_array($spec_name_array)) {
                $goods_spec_array = array_values($sku['goods_spec']);
                foreach ($spec_name_array as $k => $spec_name) {
                    $goods_spec = isset($goods_spec_array[$k]) ? ':' . $goods_spec_array[$k] : '';
                    $sku_info[] = $spec_name . $goods_spec;
                }
            }
        }
        $data['sku_info'] = implode(' ', $sku_info);

//        $data['goods_serial'] = isset($cartItem['goods_info']->goods_serial) ? $cartItem['goods_info']->goods_serial : $cartItem['goods_info']['goods_serial'];
        $goods_serial = DB::table('goods_skus')->where('id', $cartItem['sku_id'])->value('goods_serial');
        $data['goods_serial'] = $goods_serial ?? '';
        $data['goods_cost'] = $sku->goods_cost ?? 0 ;

        //如果是秒杀商品
        if (isset($cartItem['params']) && $cartItem['params'] == 'seckill') {
            $seckill = SecKillGood::where([
                'seckill_ap_id' => $cartItem['activity_id'],
                'sku_id'        => $cartItem['sku_id']
            ])->first();
            $data['total_fee'] = $seckill['seckill_price'];
            $data['activity_sign'] = $cartItem['activity_id'];
            $data['activity_type'] = "seckill";
            //推物和分成的单独设置金额,如果没设置则不分佣
            if($seckill['rewards']>0){
                $data['rewards'] = $seckill['rewards'] * $cartItem['quantity'];
            }else{
                $data['rewards']=0;
            }
            if($seckill['profit_sharing']>0){
                $data['profit_sign'] = 1;//是否分成订单
                $data['profit_sharing'] =  $seckill['profit_sharing'] * $cartItem['quantity'];
            }else{
                $data['profit_sharing']=0;
            }
        }
        //团购价格处理
        if (isset($cartItem['actSign']) && $cartItem['actSign'] == 'is_group') {

            $data['total_fee'] = $cartItem['group_info']['group_price'] * $cartItem['quantity'];
            $data['activity_sign'] = $cartItem['activity_id'];
            $data['activity_type'] = "is_group";
            //推物和分成的单独设置金额
            if($cartItem['group_info']['rewards']>0){
                $data['rewards'] =  $cartItem['group_info']['rewards'] * $cartItem['quantity'];
            }else{
                $data['rewards']=0;
            }
            if($cartItem['group_info']['profit_sharing']>0){
                $data['profit_sign'] = 1;//是否分成订单
                $data['profit_sharing'] =   $cartItem['group_info']['profit_sharing'] * $cartItem['quantity'];
            }else{
                $data['profit_sharing']=0;
            }
        } else {
            $data['total_fee'] = $cartItem['goods_price'] * $cartItem['quantity'];
        }
        //积分子订单记录 积分商品id
        if (isset($cartItem['actSign']) && $cartItem['actSign'] == 'point_goods') {
            $activity_point = \ShopEM\Models\PointActivityGoods::where('goods_id', $shopTradeData['activity_sign_id'])
                                ->select('id','write_off_start','write_off_end','allow_after')->first();
            $data['activity_sign'] = $activity_point->id;
            $data['activity_type'] = "point_goods";
            $data['write_off_start'] = $activity_point->write_off_start;
            $data['write_off_end'] = $activity_point->write_off_end;
            $data['allow_after'] = $activity_point->allow_after;
        }

        //优惠分摊按照商品占子订单与总订单商品总额的比例,应付金额
        $avg_discount_fee = 0;
        $discount_fee = $shopTradeData['discount_fee'] - $cartItem['act_discount_fee'];//去掉优惠活动的分摊
        $goods_total_fee = $data['total_fee'];
        $trade_total_fee = $shopTradeData['total_fee'] - $cartItem['act_discount_fee'];
        //计算优惠活动的分摊
        if ($cartItem['is_act_goods']) {
            $avg_discount_fee += self::avgDiscountFee($cartItem['act_goods_total_fee'],
                $cartItem['act_goods_discount_fee'],
                $data['total_fee']);
            $goods_total_fee -= $avg_discount_fee;
        }
        if ($trade_total_fee > 0) {
            $avg_discount_fee += self::avgDiscountFee($trade_total_fee, $discount_fee, $goods_total_fee);
        }
        $data['avg_discount_fee'] = $avg_discount_fee;
        $data['amount'] = $data['total_fee'] - $data['avg_discount_fee'];
        $data['created_at'] = Carbon::now()->toDateTimeString();
        $data['post_fee'] = $shopTradeData['post_fee'];

        return $data;
    }


    /**
     * 创建支付单据数据
     *
     * @Author moocde <mo@mocode.cn>
     * @param $payment_id
     * @param $tid
     * @param $user_id
     * @param $amount
     * @return array
     */
    public static function makePaybillInfo($payment_id, $tid, $user_id, $amount)
    {
        $data = [
            'payment_id' => $payment_id,
            'tid'        => $tid,
            'user_id'    => $user_id,
            'amount'     => $amount,
        ];

        return $data;
    }


    // 购物车商品计算

    /**
     * 购物车商品计算
     *
     * @Author moocde <mo@mocode.cn>
     * @param     $cart_goods
     * @param     $shop_info
     * @param int $coupon_id
     * @return array
     * @throws \Exception
     */
    public static function cartGoodsCalc(&$shopCartData, $shop_info, $coupon_data, $user_addr)
    {
        $total_fee = 0;
        $discount_fee = 0;

        $total_post_fee = 0;

        //优惠活动的相关参数
        $act_discount_fee = 0;

        //商家优惠卷金额
        $seller_coupon_discount = 0;
        //店铺促销金额
        $seller_discount = 0;

        //选中的活动优惠减免
        $Activity = new Activity();
        $act_lists = [];
        if (isset($coupon_data['act_data']) && $coupon_data['act_data']) {
            $params['shop_id'] = $shop_info->id;
            $params['act_ids'] = $coupon_data['act_data'];
            $act_lists = $Activity->userfulList($params, $shopCartData);
            if ($act_lists) {
                $act_discount_fee = array_sum(array_column($act_lists, 'discount_fee'));
                $discount_fee += $act_discount_fee;
                $seller_discount = $discount_fee;
            }
        }


        $logisticsTemplates = new LogisticsTemplatesServices();
        $dlytmpl_ids = '';
        $logistics_total = [];
        foreach ($shopCartData as $item_key => &$item_val) {
            //判断是否超过了购买上限
            $respon = GoodsService::getUserBuyLimit($item_val['goods_id'], $item_val['user_id'], $item_val['quantity']);
            if ($respon['code'] === 0) {
                throw new \Exception($respon['message']);
            }
            $user_id = $item_val['user_id'];

            $activity_sign_id = 0;
            $activity_sign = '';
            //积分商品的判断
            if (isset($item_val['actSign']) && $item_val['actSign'] == 'point_goods') {
                $point_goods = \ShopEM\Models\PointActivityGoods::where('goods_id', $item_val['goods_id'])->latest()->first();
                if (!$point_goods) {
                    throw new \Exception($item_val['goods_name'] . '非积分活动商品');
                }
                $activity_sign = 'point_goods';
                $activity_sign_id = $item_val['goods_id'];
                //积分商品限制判断
                $pointGoodsObj = new \ShopEM\Services\Marketing\PointGoods;
                $check = $pointGoodsObj->buyCheck($item_val['user_id'],$point_goods,$item_val['quantity']);
                if (isset($check['code']) && $check['code']>0) {
                    throw new \Exception($check['msg']);
                    // return $this->resFailed(406, $check['msg']);
                }
            }
            //如果是秒杀商品
            if (isset($item_val['params']) && $item_val['params'] == 'seckill') {
                $seckill = SecKillGood::where([
                    'seckill_ap_id' => $item_val['activity_id'],
                    'sku_id'        => $item_val['sku_id']
                ])->first();

//                $user_queue_key = "goods_" . $item_val['sku_id'] . "_user_" . $item_val['activity_id'];//当前商品队列的
//                $getUserRedis  = Redis::hGet($user_queue_key, $user_id);//查询用户秒杀资格
//                if (empty($getUserRedis)) {
//                    throw new \Exception("该秒杀商品已经超过购买数量,请重试!");
//                }

                $goods_price = $seckill['seckill_price'] * $item_val['quantity'];
                $total_fee += $goods_price;
                $activity_sign = 'seckill';
                $activity_sign_id = $item_val['activity_id'];
            } elseif (isset($item_val['actSign']) && $item_val['actSign'] == 'is_group') {

                //生成订单之前,这里再判断一次团购库存
                //团购库存
//                $stock_key = $item_val['sku_id'] . '_group_sale_stock_'.$item_val['group_info']['id'];
//                $check = Redis::get($stock_key);

                $group_sale_stock_key = $item_val['sku_id'] . '_group_sale_stock_' . $item_val['group_info']['id'];//已经销售
                $group_stock_key = $item_val['sku_id'] . "_group_stock_" . $item_val['group_info']['id'];//团购库存

                $group_sale_stock = Redis::get($group_sale_stock_key);//已经销售
                $group_stock = Redis::get($group_stock_key);//团购库存

                //判断团员入团
                $GroupService = new \ShopEM\Services\GroupService;

                //判断参团资格
                /*$GroupService->checkGroupValidity($user_id,$item_val['group_info']['id']);
                if ($group_sale_stock == $group_stock) {
                    throw new \Exception('团购活动已经结束,请重试!');
                }*/
                //团购价格处理
                $goods_price = $item_val['group_info']['group_price'] * $item_val['quantity'];
                $total_fee += $goods_price;
                $activity_sign = 'is_group';
                $activity_sign_id = $item_val['group_info']['id'];
            } else {
                $goods_price = $item_val['goods_price'] * $item_val['quantity'];
                $total_fee += $goods_price;
            }


            //单个商品邮费计算,如果有area_code则计算邮费
            if (isset($user_addr['area_code']) && !empty($item_val['transport_id'])) {
                //单个商品的邮费
                $total_weight = 0;//暂时不考虑重量

                if (!isset($logistics_total[$item_val['transport_id']]))
                {
                    $logistics_total[$item_val['transport_id']]['goods_price'] = 0;
                    $logistics_total[$item_val['transport_id']]['quantity'] = 0;
                    $logistics_total[$item_val['transport_id']]['total_weight'] = 0;
                } 
                $logistics_total[$item_val['transport_id']]['goods_price'] += $goods_price;
                $logistics_total[$item_val['transport_id']]['area_code'] = $user_addr['area_code'];
                $logistics_total[$item_val['transport_id']]['quantity'] += $item_val['quantity'];
                $logistics_total[$item_val['transport_id']]['total_weight'] += $total_weight;


                $post_fee = $logisticsTemplates->countFee($item_val['transport_id'], $user_addr['area_code'],
                    $goods_price, $item_val['quantity'], $total_weight);
                //物流模板id
                $dlytmpl_ids .= $item_val['transport_id'] . ",";
                //商品邮费金额
                $total_post_fee += $post_fee;

                $item_val['post_fee'] = $post_fee;
            }

        }
        if (!empty($logistics_total)) 
        {
            $total_post_fee = 0;
            foreach ($logistics_total as $val_transport_id => $val_logistics) 
            {
                $new_post_fee = $logisticsTemplates->countFee($val_transport_id, $val_logistics['area_code'], $val_logistics['goods_price'], $val_logistics['quantity'], $val_logistics['total_weight']);
                 // 商品邮费金额
                $total_post_fee += $new_post_fee;
            }
            //商品邮费金额
            // $total_post_fee= \ShopEM\Services\TradeService::getMinInArray($post_fee_arr);
        }

        $coupon_id = '';
        //对应店铺使用
        if (isset($coupon_data['coupon_ids']) && isset($coupon_data['coupon_data'][$shop_info->id])) {
            $coupon_id = $coupon_data['coupon_data'][$shop_info->id];
            /*foreach ($coupon_data['coupon_data'] as $key_coupon => $value_coupon) {
                //对应店铺使用
                if ($shop_info->id == $key_coupon) {
                    $coupon_id = $value_coupon;
                }
            }*/

//            $coupon_id=$coupon_data['coupon_data'][$shop_info->id];

            //查找会员有无使用此劵
            $coupon_user = CouponStockOnline::where('user_id', $coupon_data['user_id'])
                ->where('coupon_id', $coupon_id)
                ->where('status', '1')
                ->first();

            //如果有使用优惠劵
            if ($coupon_user) {
                $discount_fee += self::discountFee($total_fee, $coupon_id);
                $seller_coupon_discount = self::discountFee($total_fee, $coupon_id);
            }
        }

        /*  $rule = \ShopEM\Models\ShopShip::where(['shop_id' => $shop_info->id, 'default' => 1])->first();
          $condition = 0;
          if ($rule) {
              foreach ($rule->rules as $key => $value) {
                  if ($total_fee >= $value['limit'] && $condition <= $value['limit']) {
                      $post = $value['post'];
                      $condition = $value['limit'];
                  }
              }
              //不符合设定好的运费计算规则时
              if (!isset($post)) {
                  $post = ($total_fee >= 99) ? 0 : $shop_info->post_fee;
              }
          } else {
              $post = ($total_fee >= 99) ? 0 : $shop_info->post_fee;
          }
          $amount = $total_fee - $discount_fee + $post;*/

        $amount = $total_fee - $discount_fee;
        //减免的邮费金额
        $service = new LogisticsTemplatesServices();
        $freeOrderPost = $service->freeOrderPost($amount, $user_id , $shop_info->gm_id??0);

        //免邮
        if ($freeOrderPost['status'] == '-1') {
            $amount = $total_fee - $discount_fee;
            $total_post_fee = 0;
        } elseif ($freeOrderPost['status'] == '1') {
            //减免邮费,如果大于减免邮费金额,就拿差值,如果小于相当于免邮
            if ($total_post_fee > $freeOrderPost['decr_fee']) {
                //总邮费
                $total_post_fee = $total_post_fee - $freeOrderPost['decr_fee'];
                $amount = $total_fee - $discount_fee + $total_post_fee;
            } else {
                //总邮费,相当于0
                $total_post_fee = 0;
                $amount = $total_fee - $discount_fee;
            }
        }
        //判断是否首单
         if(isset($freeOrderPost['type'])&&$freeOrderPost['type'] == 'new_free')
         {
             $is_first_order = 1;
         }
         else
         {
             $is_first_order = 0;

         }


        return [
            'amount'           => $amount,
            'points_fee'       => 0,
            'total_fee'        => $total_fee,
//            'post_fee'         => $post,
            'post_fee'         => $total_post_fee,//暂时先不切换
            'discount_fee'     => $discount_fee,
            'activity_sign'    => $activity_sign,
            'activity_sign_id' => $activity_sign_id,
            'dlytmpl_ids'      => $dlytmpl_ids,
            'act_discount_fee' => $act_discount_fee,
            'act_lists'        => $act_lists,
            'is_first_order'        => $is_first_order,
            'seller_discount'        => $seller_discount,
            'seller_coupon_discount'        => $seller_coupon_discount,

        ];
    }

    /**
     * 邮费
     *
     * @Author moocde <mo@mocode.cn>
     * @return int
     */
    public static function postFee()
    {
        return 0.01;
    }

    /**
     * 使用优惠券优惠金额
     *
     * @Author moocde <mo@mocode.cn>
     * @param     $total_fee
     * @param int $coupon_id
     * @return mixed
     * @throws \Exception
     */
    public static function discountFee($total_fee, $coupon_id = 0)
    {
        if ($coupon_id == 0) {
            return 0;
        }
        $coupon = Coupon::where('id', $coupon_id)->first();
//            ->where('origin_condition', '<=', $total_fee)
//            ->where('start_at', '<=', Carbon::now()->toDateTimeString())
//            ->where('end_at', '>=', Carbon::now()->toDateTimeString())

        if (empty($coupon)) {
            throw new \Exception('优惠券找不到');
        }

        return $coupon->denominations;
    }

    /**
     * 用户地址
     *
     * @Author moocde <mo@mocode.cn>
     * @param $user_id
     * @param $addr_id
     * @return array
     * @throws \Exception
     */
    public static function buyerAddr($user_id, $input_data)
    {
        $address = UserAddress::where('user_id', $user_id)->where('id', $input_data['addr_id'])->first();
        $user = \ShopEM\Models\UserAccount::find($user_id);
        //快递地址
        if (empty($address) && empty($input_data['pick_type'])) {
            throw new \Exception('收货地址无效');
        }

        if ($input_data['pick_type'] == '1') {
            if (isset($input_data['ziti_addr'])) {
                $input_data['ziti_addr'] = is_array($input_data['ziti_addr']) ? $input_data['ziti_addr'] : json_decode($input_data['ziti_addr'],
                    true);
            }
            if (!isset($input_data['ziti_addr']['receiver_name']) || !$input_data['ziti_addr']['receiver_name']) {
                throw new \Exception('请填写提货人名称');
            }
            if (!isset($input_data['ziti_addr']['receiver_tel']) || !$input_data['ziti_addr']['receiver_tel']) {
                throw new \Exception('请填写提货号码');
            }
            $data = [
                'receiver_name'     => isset($input_data['ziti_addr']['receiver_name']) ? $input_data['ziti_addr']['receiver_name'] : "自提",
                'receiver_province' => "自提",
                'receiver_city'     => "自提",
                'receiver_county'   => "自提",
                'receiver_address'  => isset($input_data['ziti_addr']['ziti_addr']) ? $input_data['ziti_addr']['ziti_addr'] : "自提",
                'receiver_zip'      => "自提",
                'receiver_tel'      => isset($input_data['ziti_addr']['receiver_tel']) ? $input_data['ziti_addr']['receiver_tel'] : "自提",
            ];
        } elseif ($input_data['pick_type'] == '2') {
            $data = [
                'receiver_name'     => $user->login_account,
                'receiver_province' => 'none(充值)',
                'receiver_city'     => 'none(充值)',
                'receiver_county'   => 'none(充值)',
                'receiver_address'  => 'none(充值)',
                'receiver_zip'      => 'none',
                'receiver_tel'      => $input_data['recharge_info']['tel'],
            ];
        } else {
            $data = [
                'receiver_name'         => $address['name'],
                'receiver_province'     => $address['province'],
                'receiver_city'         => $address['city'],
                'receiver_county'       => $address['county'],
                'receiver_address'      => $address['address'],
                'receiver_zip'          => $address['postal_code'] ? $address['postal_code'] : 'none',
                'receiver_tel'          => $address['tel'],
                'receiver_housing_name' => $address['housing_name'],
                'area_code'             => $address['area_code'],
                'receiver_housing_id'   => $address['housing_id'],
            ];
        }


        return $data;
    }

    /**
     * 优惠分摊
     *
     * @Author moocde <mo@mocode.cn>
     * @param $total_fee
     * @param $discount_fee
     * @param $item_price
     * @return float
     */
    public static function avgDiscountFee($total_fee, $discount_fee, $item_price)
    {
        return round(($item_price / $total_fee) * $discount_fee, 2);
    }

    /**
     * 积分分摊
     *
     * @Author moocde <mo@mocode.cn>
     * @param $total_fee
     * @param $max_points_fee
     * @param $points_fee
     * @return float
     */
    public static function avgDiscountPoint($total_fee, $max_points_fee, $points_fee)
    {
        return $total_fee / $max_points_fee * $points_fee;
    }

    /**
     * 使用积分的分摊和积分抵扣金额的分摊
     * @Author djw
     * @param $total_fee
     * @param $discount_fee
     * @param $amount
     * @param $consume_point_fee
     * @return array
     */
    public static function getPointData($total_fee, $discount_fee, $amount, $consume_point_fee)
    {
        $data = [
            'points_fee'        => 0,//优惠金额
            'consume_point_fee' => 0,//使用积分
        ];
        if ($discount_fee) {
            $total = $total_fee + $discount_fee;
            $points_fee = self::avgDiscountFee($total, $discount_fee, $amount); //积分抵扣金额
            if ($points_fee) {
                $consume_point_fee = self::avgDiscountPoint($consume_point_fee, $discount_fee,
                    $points_fee); //抵扣的积分
                $data = [
                    'points_fee'        => $points_fee,
                    'consume_point_fee' => $consume_point_fee,
                ];
            }
        }
        return $data;
    }

    /**
     * 计算积分抵扣
     *
     * @Author moocde <mo@mocode.cn>
     * @param $total_fee
     * @param $discount_fee
     * @param $item_price
     * @return float
     */
    public static function computePoint($total_money,$gm_id=1)
    {
        /**
         * 积分抵扣的判断
         **/
        $repository = new \ShopEM\Repositories\ConfigRepository;
        $point_config = $repository->configItem('shop', 'point', $gm_id);
        //积分抵扣上限
        $point_deduction_max = isset($point_config['point_deduction_max']) ? $point_config['point_deduction_max']['value'] : 10;
        $point_deduction_max = $point_deduction_max ? ($point_deduction_max / 100) : 0.99;

        //积分抵扣比率
        $point_deduction_rate = isset($point_config['point_deduction_rate']) ? $point_config['point_deduction_rate']['value'] : 100;
        $point_deduction_rate = $point_deduction_rate ? $point_deduction_rate : 100;

        //计算可抵扣的最大金额
        $max_points_fee = round($total_money * $point_deduction_max, 2);
        if ($max_points_fee >= $total_money) {
            $max_points_fee = 0;
            $max_points = 0;
        }else{
            //计算可抵扣的最大积分值
            $max_points = floor($max_points_fee * $point_deduction_rate);
        }
        $result = [
            'consume_point_fee' => $max_points,
            'points_fee'        => $max_points_fee,
        ];

        return $result;
    }

    /**
     * 是否开启积分功能
     *
     * @Author djw
     * @return float
     */
    public static function isOpenPointDeduction($gm_id=0)
    {
        if ($gm_id > 0)
        {
            $repository = new \ShopEM\Repositories\ConfigRepository;
            $point_config = $repository->configItem('shop', 'point', $gm_id);
            if (isset($point_config['open_point_deduction']) && $point_config['open_point_deduction']['value']) {
                return true;
            }
        }

        return false;
    }

    /**
     * 是否允许积分专区商品使用优惠券
     *
     * @Author djw
     * @return float
     */
    public static function isOpenPointGoodsDeduction()
    {
        $repository = new \ShopEM\Repositories\ConfigRepository;
        $point_config = $repository->configItem('shop', 'point');
        if (isset($point_config['open_point_goods_deduction']) && $point_config['open_point_goods_deduction']['value']) {
            return true;
        }

        return false;
    }

    /**
     * test trade order data
     *
     * @Author moocde <mo@mocode.cn>
     * @param $trade_data
     * @param $order_data
     * @return array
     */
    private static function testTradeOrderCalc($trade_data, $order_data)
    {
        $sum_order_total_fee = 0;
        $sum_avg_discount_fee = 0;
        $sum_amount = 0;
        foreach ($order_data as $item) {
            $sum_order_total_fee += $item['total_fee'];
            $sum_amount += $item['amount'];
            $sum_avg_discount_fee += $item['avg_discount_fee'];
        }

        return [
            'total_fee'    => [
                'result' => $sum_order_total_fee === $trade_data['total_fee'],
                'trade'  => $trade_data['total_fee'],
                'order'  => $sum_order_total_fee,
            ],
            'amount'       => [
                'result' => $sum_amount === $trade_data['total_fee'] - self::postFee(),
                'trade'  => $trade_data['amount'] - self::postFee(),
                'order'  => $sum_amount,
            ],
            'discount_fee' => [
                'result' => $sum_order_total_fee === $trade_data['total_fee'],
                'trade'  => $trade_data['discount_fee'],
                'order'  => $sum_avg_discount_fee,
            ],
        ];
    }


    /**
     * 取消订单操作用户类型
     * shop 商家取消订单
     * buyer 用户取消订单
     * shopadmin 平台操作取消订单
     * system  系统取消订单 系统只能取消未付款的订单
     */
    protected $cancelFromType = null;

    /**
     * 当前取消订单的状态
     */
    private $__tradeStatus = null;

    /**
     * 当前执行取消操作的操作员ID
     */
    protected $id = null;


    /**
     * 设置取消订单用户类型
     */
    public function setCancelFromType($type)
    {
        $this->cancelFromType = $type;
        return $this;
    }

    /**
     * 获取当前取消订单的操作用户类型
     */
    public function getCancelFromType()
    {
        return $this->cancelFromType;
    }

    /**
     * 设置当前取消订单的操作员ID
     *
     * 如果操作类型为用户则为用户ID
     * 如果操作类型为商家则为店铺ID
     * 如果操作类型为平台则为平台账号ID
     */
    public function setCancelId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * 获取当前取消订单的操作员ID
     */
    public function getCancelId()
    {
        return $this->id;
    }


    /**
     * 创建取消订单记录列表
     *
     * @Author hfh_wind
     * @param $tid array | int  需要取消的订单ID
     * @param $cancelReason string 取消订单的原因
     * @param null $returnFreight
     * @return bool
     * @throws Exception
     */

    public function tradeCancelCreate($tid, $cancelReason, $refundBn = null, $returnFreight = null)
    {
        $cancelReason = trim($cancelReason);
        $tradeDataInfo = $this->__getCancelTradeInfo($tid);
        DB::beginTransaction();
        try {
            //未付款 可直接取消 货到付款并且不是消费者申请取消订单则可以直接取消
            if ($tradeDataInfo->status == 'WAIT_BUYER_PAY' || ($tradeDataInfo->pay_type == 'offline' && $this->getCancelFromType() != 'buyer')) {
                $this->__noPayTradeCancel($tradeDataInfo, $cancelReason);
            } else//已付款或者为货到付款的订单需要申请退款
            {
                $this->__payTradeCancel($tradeDataInfo, $cancelReason, $refundBn, $returnFreight);
                //2020-4-9 18:51:46 支付成功推送ERP
                TradePushErp::dispatch($tid,'cancel');
            }


        } catch (\Exception $e) {
            DB::rollback();
            $message = $e->getMessage();
            throw new \Exception($message);
        }
        DB::commit();
        return true;
    }

    /**
     * 获取当前取消订单的订单数据
     *
     * @Author hfh_wind
     * @param $tid
     * @return mixed
     */

    private function __getCancelTradeInfo($tid)
    {
        if ($this->getCancelFromType() == 'buyer') {
            $searchTradeParam['filter']['user_id'] = $this->getCancelId();
        } elseif ($this->getCancelFromType() == 'shop') {
            $searchTradeParam['filter']['shop_id'] = $this->getCancelId();
        }

        $tradeData = Trade::where(['tid' => $tid])->select('tid', 'status', 'pay_type', 'user_id', 'shop_id', 'amount',
            'post_fee', 'activity_sign', 'status','gm_id')->first();

        if (!$tradeData) {
            $msg = "取消的订单不存在";
            throw new \logicexception($msg);
        }

        if ($tradeData->status != 'WAIT_BUYER_PAY' && $tradeData->status != 'WAIT_SELLER_SEND_GOODS') {
            throw new \logicexception('不符合申请取消订单的条件');
        }
        $this->__tradeStatus = $tradeData->status;

        $count = TradeCancel::where('tid', $tid)->where('process', '!=', '3')->where('refunds_status', '!=', 'SUCCESS')->count();
        if ($count) {
            $msg = "订单已提交过取消申请";
            throw new \logicexception($msg);
        }

        return $tradeData;
    }


    /**
     * (平台取消给队列使用)未支付的订单取消 可直接取消的订单，未支付并且为在线支付
     *
     * @Author hfh_wind
     * @param $tid
     * @param $cancelReason
     * @return bool
     * @throws \Exception
     */
    public function PlatformQueueTradeCancel($tid, $cancelReason)
    {
        $cancelReason = trim($cancelReason);
        $tradeData = $this->__getCancelTradeInfo($tid);
        if ($tradeData['status'] === 'WAIT_BUYER_PAY') {
            $tid = $tradeData['tid'];
            $cancelTradeData['tid'] = $tid;
            $cancelTradeData['user_id'] = $tradeData['user_id'];
            $cancelTradeData['shop_id'] = $tradeData['shop_id'];
            $cancelTradeData['cancel_id'] = self::createId('trade_cancel');
            $cancelTradeData['reason'] = $cancelReason;//取消订单原因
            $cancelTradeData['pay_type'] = $tradeData['pay_type'];//取消的订单的支付类型
            //  $cancelTradeData['amount'] = ($tradeData['amount'] && $tradeData['amount'] > 0) ? $tradeData['amount'] : '0';//取消的订单的已支付金额
            $cancelTradeData['cancel_from'] = "admin";
            //线上支付，未支付直接完成
            $cancelTradeData['process'] = '3';//取消订单处理完成
            $cancelTradeData['refunds_status'] = 'SUCCESS';//退款状态为成功


            $cancelId = TradeCancel::create($cancelTradeData);

            if (!$cancelId) {
                throw new \Exception("取消订单失败");
            }


            //取消订单处理秒杀活动,恢复秒杀库存
            $seckill = new \ShopEM\Services\SecKillService();
            $data['user_id'] = $tradeData['user_id'];
            $data['tid'] = $tid;
            $seckill->resetSecKillOrder($data);


            //处理团购活动,如果删除缓存
            $groupService = new \ShopEM\Services\GroupService();

            $groupService->clearGroupInfo($tid);


            //记录取消订单处理日志
            //        $logText = '您的申请已提交';
            //        $this->__addLog($cancelId, $logText);

            //订单取消成功后进行的操作
            $this->__cancelSuccDo($tid, $tradeData['shop_id'], $cancelReason);
            $coupon = CouponStockOnline::where('tid', $tid)->first();
            if ($coupon && $coupon->status == 2) {
                CouponStockOnline::reUseCoupon($coupon);
                // $coupon->tid = 0;
                // $coupon->status = 1;
                // $coupon->save();
            }
            //记录取消订单处理日志
            //        $logText = '您的订单取消成功';
            //        $this->__addLog($cancelId, $logText, 'system');
        }

        return true;
    }

    /**
     * 未支付的订单取消 可直接取消的订单，未支付并且为在线支付
     *
     * @Author hfh_wind
     * @param $tradeData
     * @param $cancelReason
     * @return bool
     * @throws \Exception
     */
    public function __noPayTradeCancel($tradeData, $cancelReason)
    {
        $tid = $tradeData['tid'];
        $cancelTradeData['tid'] = $tid;
        $cancelTradeData['user_id'] = $tradeData['user_id'];
        $cancelTradeData['shop_id'] = $tradeData['shop_id'];
        $cancelTradeData['cancel_id'] = self::createId('trade_cancel');
        $cancelTradeData['reason'] = $cancelReason;//取消订单原因
        $cancelTradeData['pay_type'] = $tradeData['pay_type'];//取消的订单的支付类型
        //  $cancelTradeData['amount'] = ($tradeData['amount'] && $tradeData['amount'] > 0) ? $tradeData['amount'] : '0';//取消的订单的已支付金额
        $cancelTradeData['cancel_from'] =  $this->getCancelFromType() ? $this->getCancelFromType() : "admin";
        //线上支付，未支付直接完成
        $cancelTradeData['process'] = '3';//取消订单处理完成
        $cancelTradeData['refunds_status'] = 'SUCCESS';//退款状态为成功
        $cancelTradeData['gm_id'] = $tradeData['gm_id']??1;

        $cancelId = TradeCancel::create($cancelTradeData);

        if (!$cancelId) {
            throw new \Exception("取消订单失败");
        }


        //取消订单处理秒杀活动,恢复秒杀库存
        $seckill = new \ShopEM\Services\SecKillService();
        $data['user_id'] = $tradeData['user_id'];
        $data['tid'] = $tid;
        $seckill->resetSecKillOrder($data);


        //处理团购活动(团长必定是付款用户,跟团的才有此流程),如果删除缓存
        /*$groupService = new \ShopEM\Services\GroupService();

        $groupService->clearGroupInfo($tid);*/

        //记录取消订单处理日志
//        $logText = '您的申请已提交';
//        $this->__addLog($cancelId, $logText);

        //订单取消成功后进行的操作
        $this->__cancelSuccDo($tid, $tradeData['shop_id'], $cancelReason);
        $coupon = CouponStockOnline::where('tid', $tid)->first();
        if ($coupon && $coupon->status == 2) {
            $coupon->tid = 0;
            $coupon->status = 1;
            $coupon->save();
        }
        //记录取消订单处理日志
//        $logText = '您的订单取消成功';
//        $this->__addLog($cancelId, $logText, 'system');

        return true;
    }

    /**
     * 取消订单成功后处理事件
     * @Author hfh_wind
     * @param $tid
     * @param $shopId
     * @param $cancelReason
     * @return bool
     * @throws \Exception
     */

    public function __cancelSuccDo($tid, $shopId, $cancelReason = null)
    {
        //取消订单成功更新订单状态和信息
        $params['filter']['tid'] = $tid;
        $params['data']['end_time'] = Carbon::now()->toDateTimeString();
        if ($this->getCancelFromType() == 'buyer' && $this->__tradeStatus != 'WAIT_BUYER_PAY') {
            $params['data']['status'] = 'TRADE_CLOSED';
            $orderParams['status'] = 'TRADE_CLOSED_AFTER_PAY';
        } else {
            $params['data']['status'] = 'TRADE_CLOSED_BY_SYSTEM';
            $orderParams['status'] = 'TRADE_CLOSED_BEFORE_PAY';
        }

        if (!$this->updateTrade($params)) {
            throw new \Exception("取消订单失败，更新数据库失败");
        }
        //取消订单成功更新字订单信息
        $orderParams['end_time'] = Carbon::now()->toDateTimeString();
        if (!TradeOrder::where($params['filter'])->update($orderParams)) {
            throw new \Exception("取消订单失败，更新数据库失败");
        }

        //待定
        //取消订单后，积分回退
        if (!$this->__rollbackPoint($tid)) {
            throw new \Exception("取消订单{$tid}失败，积分回退失败");
        }
        //取消订单退还购物券
//            $this->__backVoucher($tid);

        // 恢复、解冻库存
        if (!$this->__recoverStore($tid)) {
            throw new \Exception("取消订单{$tid}失败，恢复库存失败");
        }

        $this->updateCancelStatus($tid, 'SUCCESS', $cancelReason);

        // 返还优惠券，如果有的情况下


        return true;
    }


    /**
     * 已支付订单取消
     *
     * @Author hfh_wind
     * @param $tradeData array 要取消的订单数据
     * @param $cancelReason string 取消订单的原因
     * @param $refundBn
     * @param null $returnFreight
     * @return bool
     * @throws \Exception
     */
    public function __payTradeCancel($tradeData, $cancelReason, $refundBn, $returnFreight = null)
    {
        //创建退款申请单
        if ($this->getCancelFromType() == 'buyer') {
            $params['status'] = '0';//申请状态 未审核
            $cancelTradeData['refunds_status'] = 'WAIT_CHECK';//退款状态 等待审核
            $process = '0';
            $tradeCancelSatus = 'WAIT_PROCESS';
            //团购订单会员手动取消时亦是直接退款
            if ($tradeData['activity_sign'] == 'is_group') {
                $params['status'] = '6';//申请状态 平台强制关单
                $cancelTradeData['refunds_status'] = 'WAIT_REFUND';//退款状态 直接等待平台退款
                $process = '2';
                $tradeCancelSatus = 'REFUND_PROCESS';
            }
        } elseif ($this->getCancelFromType() == 'shop') {
            $params['status'] = '5';//申请状态 商家强制关单
            $cancelTradeData['refunds_status'] = 'WAIT_REFUND';//退款状态 直接等待平台退款
            $process = '2';
            $tradeCancelSatus = 'REFUND_PROCESS';
        } else {
            $params['status'] = '6';//申请状态 平台强制关单
            $cancelTradeData['refunds_status'] = 'WAIT_REFUND';//退款状态 直接等待平台退款
            $process = '2';
            $tradeCancelSatus = 'REFUND_PROCESS';
        }

        $tid = $tradeData['tid'];

        $count = TradeCancel::where(['tid' => $tid, 'process' => '2'])->count();
        //计划任务里退款处理,不让重复生成取消记录
        if ($count) {
            return true;
        }

        $cancelTradeData['tid'] = $tid;
        $cancelTradeData['cancel_id'] = self::createId('trade_cancel');
        $cancelTradeData['user_id'] = $tradeData['user_id'];
        $cancelTradeData['shop_id'] = $tradeData['shop_id'];
        $cancelTradeData['cancel_from'] = $this->getCancelFromType() ? $this->getCancelFromType() : "admin";
        $cancelTradeData['reason'] = $cancelReason;//取消订单原因
        $cancelTradeData['pay_type'] = $tradeData['pay_type'];//取消的订单的支付类型
        $cancelTradeData['refund_fee'] = $tradeData['amount'];//取消的订单的已支付金额
        $cancelTradeData['process'] = $process;//处理进度,提交申请
        $cancelTradeData['gm_id'] = $tradeData['gm_id']??1;

        //生成取消订单记录
        $cancelId = TradeCancel::create($cancelTradeData);
        if (!$cancelId) {
            throw new \Exception("取消订单失败");
        }


//        $logText = '您的申请已提交';
//        $this->__addLog($cancelId, $logText);

        $params['shop_id'] = $tradeData['shop_id'];
        $params['user_id'] = $tradeData['user_id'];
        $params['reason'] = $cancelReason;
        $params['tid'] = $tid;
        $params['refunds_type'] = 'cancel';//申请退款类型，取消订单退款
        if (!is_null($returnFreight)) {
            $params['return_freight'] = $returnFreight;
        }

        if ($refundBn) {
            $params['refund_bn'] = $refundBn;
        }
        //退款单生成
        $TradeAfterRefund = new  \ShopEM\Services\TradeAfterRefundService();
        $refundapplyData = $TradeAfterRefund->apply($params);

        if (!$refundapplyData) {
            throw new \Exception("取消订单失败");
        }

        //is_restore 表示已经退款完成
        if (!$refundapplyData['is_restore']) {
            $this->updateCancelStatus($tid, $tradeCancelSatus, $cancelReason);
        }

        return true;
    }


    /**
     *  消费者申请取消订单，商家审核同意取消订单
     *
     * @Author hfh_wind
     * @param $cancelId
     * @param $shopId
     * @return bool
     * @throws Exception
     */
    public function cancelShopAgree($cancelId, $shopId)
    {
        $tradeCancelData = $this->__preCancelData($cancelId, $shopId);

        DB::beginTransaction();
        $tid = $tradeCancelData['tid'];
        try {
            if ($tradeCancelData['pay_type'] == 'online') {
                //更新取消订单记录状态 退款状态为等待退款
                $tradeCancel=TradeCancel::where(['cancel_id' => $cancelId])->update([
                    'refunds_status' => 'WAIT_REFUND',
                    'process'        => '2'
                ]);
                //更新订单表取消订单的状态
                $this->updateCancelStatus($tid, 'REFUND_PROCESS');

                //更新退款申请单状态
                $params['shop_id'] = $shopId;
                $params['status'] = '3';
                $params['tid'] = $tid;
                $refunds = $this->refundpplyShopReply($params);

//                $logText = '商家同意退款，等待退款处理！';
//                $this->__addLog($cancelId, $logText, $shopId, 'shop');

                //如果是红包全额支付，自动退红包,这时候取消订单会变成已完结
//                if( $tradeInfo['discount_fee'] ==  $tradeInfo['amount'] )
//                {
                //更改退款申请单
//                $apiParams =['refunds_id'=>$refunds['refunds_id'],'return_fee'=>$refunds['total_price']];
                //平台对退款申请进行退款处理
//                $refund->refundApplyRestore($apiParams);

//                }
            } else//线下付款则处理完成
            {
                //更新退款申请单状态
                $params['shop_id'] = $shopId;
                $params['status'] = '1';
                $params['tid'] = $tid;
                $this->refundpplyShopReply($params);

                //更新取消订单记录状态
                $tradeCancel=TradeCancel::where(['cancel_id' => $cancelId])->update([
                    'refunds_status' => 'SUCCESS',
                    'process'        => '3'
                ]);
                //订单取消成功后进行的操作
                $this->__cancelSuccDo($tid, $shopId);

//                $logText = '商家同意取消订单，订单取消成功！';
//                $this->__addLog($cancelId, $logText, $shopId, 'shop');

            }

            //商家同意付款取消的订单,处理秒杀活动,恢复秒杀库存
            $seckill = new \ShopEM\Services\SecKillService();
            $data['user_id'] = $tradeCancelData['user_id'];
            $data['tid'] = $tid;
            $seckill->resetSecKillOrder($data);


            //商家同意付款取消的订单,处理团购活动,如果是团长就删除缓存
            $groupService = new \ShopEM\Services\GroupService();

            $groupService->clearGroupInfo($tid);

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }

        return true;
    }

    /**
     * 消费者申请取消订单，商家拒绝取消订单
     *
     * @Author hfh_wind
     * @param $cancelId
     * @param $shopId
     * @param $reason
     * @return bool
     * @throws Exception
     */

    public function cancelShopReject($cancelId, $shopId, $reason)
    {
        $tradeCancelData = $this->__preCancelData($cancelId, $shopId);

        DB::beginTransaction();

        $tid = $tradeCancelData['tid'];
        try {

            //更新取消订单记录状态
            TradeCancel::where(['cancel_id' => $cancelId])->update([
                'refunds_status'     => 'SHOP_CHECK_FAILS',
                'process'            => '3',
                'shop_reject_reason' => $reason
            ]);
            //更新订单表取消订单的状态
            $this->updateCancelStatus($tid, 'FAILS');

            //需要商家审核的订单，为已支付订单取消，或者为货到付款
            //如果不是货到付款那么则需要更新退款申请单的状态
            if ($tradeCancelData['pay_type'] == 'online') {
                $params['shop_id'] = $shopId;
                $params['status'] = '4';
                $params['tid'] = $tid;
                $this->refundpplyShopReply($params);
            }

//            $logText = '商家拒绝取消订单！';
//            $this->__addLog($cancelId, $logText, $shopId, 'shop');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }

        DB::commit();
        return true;
    }


    /**
     * @brief 未发货的订单取消订单时，回退扣减的积分
     *
     * @param $tid
     *
     * @return
     */
    private function __rollbackPoint($tid)
    {
        $result = true;
        $tradeInfo = Trade::where(['tid' => $tid])->first();
       // $paymentInfo = Payment::where(['payment_id' => $tradeInfo->payment_id])->first();


        $orderData = [];
        foreach ($tradeInfo['trade_order'] as $key => $val) {
            $orderData[$key]['goods_name'] = $val['goods_name'];
        }

        $params['order'] = $orderData;
        $params['user_id'] = $tradeInfo->user_id;
        // $params['behavior'] = "来自于订单：" . $tradeInfo->tid . "的积分回退";
        $params['behavior'] = "订单退还积分";


        // $tradeWillPaymentPoint = new \ShopEM\Models\TradeWillPaymentPoint;
        // $isPayPoint = $tradeWillPaymentPoint->isPayPoint($tradeInfo->payment_id);
        // $payStatus = Payment::where(['payment_id' => $tradeInfo->payment_id,'status' => 'succ'])->exists();
        $payStatus = true;
        //使用积分的优惠分摊
        if ($tradeInfo->consume_point_fee && $payStatus)
        {

            $consume_point_fee = $tradeInfo->consume_point_fee; //抵扣的积分
            $params['type'] = "obtain";
            $params['remark'] = "取消订单返还使用的积分";
            $params['num'] = $consume_point_fee;
            $params['log_type'] = $tradeInfo->activity_sign == 'point_goods' ?"exchange" : 'trade';
            $params['log_obj'] = $tradeInfo->tid;

            if ($params['num'] > 0) {
                $yitiangroup_service = new \ShopEM\Services\YitianGroupServices($tradeInfo->gm_id);
                $result = $yitiangroup_service->updateUserYitianPoint($params);
            }
        }

        return $result;
    }

    private function __preCancelData($cancelId, $shopId)
    {
        $tradeCancelData = TradeCancel::where(['cancel_id' => $cancelId])->select('tid', 'shop_id', 'pay_type',
            'refunds_status', 'cancel_from', 'user_id')->first();

        if (!$tradeCancelData || $tradeCancelData->shop_id != $shopId) {
            throw new \LogicException('待审核的取消订单不存在');
        }

        if ($tradeCancelData['refunds_status'] != 'WAIT_CHECK') {
            throw new \LogicException('该取消订单已审核，不需要审核');
        }

        return $tradeCancelData;
    }


    public function refundpplyShopReply($params)
    {
        if (!empty($params['refunds_id'])) {
            $filter['refunds_id'] = $params['refunds_id'];
        } elseif (!empty($params['aftersales_bn'])) {
            $filter['aftersales_bn'] = $params['aftersales_bn'];
        } elseif (!empty($params['tid'])) {
            $filter['tid'] = $params['tid'];
            $filter['refunds_type'] = '1';
        } else {
            throw new \LogicException('参数错误');
        }

        $status = $params['status'];

        $data = TradeRefunds::where($filter)->select('tid', 'oid', 'user_id', 'refund_bn', 'total_price',
            'refunds_type', 'status', 'shop_id', 'id')->first()->toArray();
        if ($data['status'] != '0') {
            throw new \LogicException('该退款申请已审核，不需要重新审核');
        }

        $refundsfilter['shop_id'] = $params['shop_id'];
        $refundsfilter['id'] = $data['id'];
        $result = TradeRefunds::where($refundsfilter)->update(['status' => $status]);

        return $data;
    }


    /**
     *  修改主订单trade表
     *
     * @Author hfh_wind
     * @param array $params 需要修改的数据
     * @param array $filter 条件
     *
     * @return bool
     */
    public function updateTrade($params)
    {
        $filter = $params['filter'];
        $data = $params['data'];
        $Trade = Trade::where('tid', $filter['tid']);
//        unset($filter['tid']);
//        if ($filter) {
//            foreach ($filter as $key => $value) {
//                $Trade->where($key, $value);
//            }
//        }
        $result = $Trade->update($data);
        if (!$result) {
            $msg = "订单修改失败";
            throw new \LogicException($msg);
            return false;
        }
        return $result;
    }


    /**
     * 更新取消订单状态
     *
     * @param $tid
     * @param $cancelStatus
     */
    public function updateCancelStatus($tid, $cancelStatus, $cancelReason = "")
    {

        $updateData['cancel_status'] = $cancelStatus;
        if ($cancelReason) {
            $updateData['cancel_reason'] = $cancelReason;
        }

        if (!Trade::where(['tid' => $tid])->update($updateData)) {
            throw new \Exception("更新取消订单状态失败");
        }
        return true;
    }


    /**
     * 恢复取消订单的库存
     *
     * @Author hfh_wind
     * @param $tid 单个订单号
     * @return bool
     */
    private function __recoverStore($tid)
    {
        $isRecover = true;
        $orderInfo = TradeOrder::where(['tid' => $tid])->select('oid', 'shop_id', 'status', 'goods_id', 'sku_id',
            'quantity', 'pay_time', 'activity_type','activity_sign')->get();
        $nowTime = date('Y-m-d H:i:s', time());
        foreach ($orderInfo as $oVal) {
            $applyInfo = '';
            if ($oVal['activity_type'] == 'seckill') {
                $applyInfo = SecKillApplie::where('end_time', '>=', $nowTime)->where('id', $oVal['activity_sign'])->first();
            }
            if (!$applyInfo) {
                $tradePay = 1;
                if (!$oVal['pay_time']) {
                    if ($oVal['status'] == 'WAIT_BUYER_PAY' || $oVal['status'] == 'TRADE_CLOSED_BEFORE_PAY') {
                        $tradePay = 0;
                    }
                }

                $arrParam = array(
                    'goods_id' => $oVal['goods_id'],
                    'sku_id' => $oVal['sku_id'],
                    'quantity' => $oVal['quantity'],
//                    'sub_stock' => $oVal['sub_stock'],
                    'tradePay' => $tradePay,//是否支付
                    'oid' => $oVal['oid'],
                    'shop_id' => $oVal['shop_id'],
                );
                $goodStore = new \ShopEM\Services\GoodsService();
                $isRecover = $goodStore->storeRecover($arrParam);
                if (!$isRecover) {
                    return false;
                }
            }

            //赠品暂时没
            /*     if($oVal['gift_data'])
                 {
                     foreach($oVal['gift_data'] as $giftVal)
                     {
                         $arrParam = array(
                             'item_id'  => $giftVal['item_id'],
                             'sku_id'   => $giftVal['sku_id'],
                             'quantity' => $giftVal['gift_num'],
                             'sub_stock' => $giftVal['sub_stock'],
                             'tradePay' => $tradePay,
                         );
                         $isRecover = app::get('systrade')->rpcCall('item.store.recover',$arrParam);
                         if(!$isRecover) return false;
                     }

                 }*/
        }

        return $isRecover;
    }


    /**
     * 对订单进行发货
     *
     * @Author hfh_wind
     * @param $tid string 发货的订单
     * @param $corpCode 物流公司编号
     * @param $logiNo 运单号
     * @param $shopUserData 发货商家用户信息 shop_id seller_id
     * @param $zitiMemo 自提备注
     * @param $memo 发货备注
     * @return bool
     * @throws Exception
     * @throws LogicException
     */
    public function doDelivery($tid, $corpCode, $logiNo, $shopUserData, $zitiMemo, $memo)
    {
        $shopId = $shopUserData['shop_id'];
        $sellerId = $shopUserData['seller_id'];

        $tradeInfo = $this->__getTradeInfo($tid);

        //检查订单是否可以发货
        $this->doDelivery_check($tradeInfo, $shopUserData);

        $oids = implode(',', array_column($tradeInfo['trade_order'], 'oid'));

        DB::beginTransaction();

        try {
            //如果订单正在进行取消，发货的时候拒绝消费者取消订单
            //业务场景为：
            //消费者提交了取消订单申请，但是在后端（OMS）仓库发货未发现该订单取消申请（网络异常，退款申请单同步失败），进行了发货！
            //那么则将消费者的申请拒绝，可以让消费者进行拒收操作
            if (in_array($tradeInfo['cancel_status'], ['WAIT_PROCESS', 'REFUND_PROCESS'])) {

                $tradecanceData = TradeCancel::where(['tid' => $tid])->select('cancel_id')->first();

                $reason = '商家已发货，拒绝取消订单';
                //商家审核拒绝取消订单
                $this->cancelShopReject($tradecanceData->cancel_id, $tradeInfo['shop_id'], $reason);
            }

            //创建发货单
            $this->_createDelivery($tid, $oids, $shopId, $sellerId, $tradeInfo);

            $dlytmplId = $tradeInfo['dlytmpl_ids'];
            $postFee = $tradeInfo['post_fee'];
            //更新发货单
            $detail = $this->_updateDelivery($tid, $dlytmplId, $postFee, $corpCode, $logiNo, $memo);

            //更新订单发货状态
            $tradeData = array(
                'status'       => 'WAIT_BUYER_CONFIRM_GOODS',
                'consign_time' => Carbon::now()->toDateTimeString(),
                'ziti_memo'    => $zitiMemo,
                'invoice_no'   => $logiNo,
            );
            if (!Trade::where(['tid' => $tid])->update($tradeData)) {
                throw new \LogicException("更新订单发货状态失败");
            }
            //更新子订单状态
            foreach ($tradeInfo['trade_order'] as $key => $value) {
//                //删除赠品
//                $updateData['sendnum'] = ecmath::number_plus(array($value['sendnum'], $detail[$value['oid']]['number']));
                $updateData['status'] = "WAIT_BUYER_CONFIRM_GOODS";
                $updateData['consign_time'] = Carbon::now()->toDateTimeString();
                if (!TradeOrder::where(['oid' => $value['oid']])->update($updateData)) {
                    throw new \LogicException("更新子订单发货状态失败");
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
         #发货微信服务通知
        (new WechatMessage())->shipMessage($tid);

        $shipData['corp_code'] = $corpCode;
        $shipData['logi_no'] = $logiNo;
        $shipData['ziti_memo'] = $zitiMemo;
        $shipData['memo'] = $memo;

        $tradeData['tid'] = $tid;
        $tradeData['oids'] = $oids;//逗号隔开的字符串
        $tradeData['shop_id'] = $shopId;
        $tradeData['post_fee'] = $postFee;

        return true;
    }


    /**
     * 检查订单是否可以发货
     * @Author hfh_wind
     * @param $tradeInfo
     * @param $shopUserData
     * @return bool
     * @throws LogicException
     */
    public function doDelivery_check($tradeInfo, $shopUserData)
    {
        if (empty($tradeInfo) || $tradeInfo['shop_id'] != $shopUserData['shop_id']) {
            throw new \LogicException('发货订单不存在');
        }

        if ($tradeInfo['status'] != 'WAIT_SELLER_SEND_GOODS') {
            throw new \LogicException('订单已发货');
        }

        $refunds_status=TradeCancel::where('tid',$tradeInfo['tid'])->value('refunds_status');
        if ($refunds_status  && !in_array($refunds_status,['FAILS','SUCCESS']) ) {
            throw new \LogicException('订单申请取消退款中，请勿操作');
        }

        if ($tradeInfo['pick_type'] == 1) {
            throw new \LogicException('自提订单无法发货');
        }

        //拼团成功的判断
        $join = GroupsUserJoin::where(['tid' => $tradeInfo['tid']])->first();
        if (!empty($join)) {
            $groupOrder = GroupsUserOrder::where(['groups_bn' => $join['groups_bn']])->first();
            if ($groupOrder['status'] != 2) {
                throw new \LogicException('发货订单未拼团成功');
            }
        }

        return true;
    }


    /**
     * 创建发货单
     *
     * @Author hfh_wind
     * @param $tid string 订单ID
     * @param $oids string 子订单ID集合
     * @param $shopId 店铺ID
     * @param $sellerId 商家用户ID
     * @param $tradeInfo 主订单以及子订单信息
     * @return $deliveryId 返回发货单
     * @throws LogicException
     */
    public function _createDelivery($tid, $oids, $shopId, $sellerId, $tradeInfo = '')
    {
        $data = [
            'tid'       => $tid,
            'oids'      => $oids,
            'shop_id'   => $shopId,
            'seller_id' => $sellerId,
            'tradeInfo' => $tradeInfo,
        ];

        $delivery = new \ShopEM\Services\LogisticsService();
        $this->deliveryId = $delivery->deliveryCreate($data);

        if (!$this->deliveryId) {
            throw new \LogicException("创建发货单失败");
        }
        return $this->deliveryId;
    }


    /**
     * 更新发货单
     *
     * @Author hfh_wind
     * @param $tid string 订单ID
     * @param $dlytmplId int 当前发货订单的快递单模版ID
     * @param $postFee float 当前发货的运费
     * @param $corpCode string 物流公司编号
     * @param $logiNo string 运单号
     * @param $memo 发货备注
     * @return mixed
     */
    protected function _updateDelivery($tid, $dlytmplId, $postFee, $corpCode, $logiNo, $memo)
    {
        //更新发货单状态
        $deliveryData = array(
            'delivery_id' => $this->deliveryId,
            'template_id' => $dlytmplId,
            'logi_no'     => $logiNo,
            'tid'         => $tid,
            'post_fee'    => $postFee ? $postFee : 0,
            'corp_code'   => $corpCode,
            'memo'        => $memo,
        );
        $delivery = new \ShopEM\Services\LogisticsService();

        $result = $delivery->deliveryUpdate($deliveryData);
        foreach ($result['detail'] as $key => $value) {
            if ($value['item_type'] == "gift") {
                unset($result['detail'][$key]);
            }
        }

        $detail = $this->array_bind_key($result['detail'], "oid");

        return $detail;
    }

    /**
     * 根据传入的数组和数组中值的键值，将对数组的键进行替换
     *
     * @param array $array
     * @param string $key
     */
    public function array_bind_key($array, $key)
    {
        foreach ((array)$array as $value) {
            if (!empty($value[$key])) {
                $k = $value[$key];
                $result[$k] = $value;
            }
        }
        return $result;
    }

    /**
     *  获取需要发货的订单数据
     *
     * @Author hfh_wind
     * @param $tid 订单ID
     * @return mixed
     */
    public function __getTradeInfo($tid)
    {
        //订单返回的字段，如果有值则返回所有子订单的所有数据
        $tradeInfo = Trade::where(['tid' => $tid])->first();
        if ($tradeInfo) {
            $tradeInfo = $tradeInfo->toArray();
        }
        //$tradeInfo['order'] = TradeOrder::where(['tid' => $tid])->get()->toArray();
        return $tradeInfo;
    }

    /**
     * 订单确认完成
     *
     * @Author djw
     * @param $tid
     * @param null $userId
     * @param null $shopId
     * @return bool
     * @throws \Exception
     */
    public function confirmReceipt($tid, $userId = null, $shopId = null)
    {
        $filter['tid'] = $tid;
        $Trade = Trade::where('tid', $tid);
        if ($userId) {
            $filter['user_id'] = intval($userId);
            $Trade->where('user_id', intval($userId));
        }
        if ($shopId) {
            $filter['shop_id'] = intval($shopId);
            $Trade->where('shop_id', intval($shopId));
        }
        $tradeInfo = $Trade->first();

        $this->__check($tradeInfo);
        //todo 以后再做 分润预估收益奖励
//        kernel::single('systrade_data_reward')->distributionReward($tradeInfo,true);

        DB::beginTransaction();
        try {
            //todo 以后再做 生成结算点明细
//            $isClearing = app::get('systrade')->rpcCall('clearing.detail.add',['tid'=>$tid]);
//            if( ! $isClearing )
//            {
//                throw new \LogicException("结算明细生成失败");
//            }

            $update['filter'] = $filter;
            $endtime = Carbon::now()->toDateTimeString();
            $update['data'] = [
                'status'      => 'TRADE_FINISHED',
                'is_clearing' => 1,
                'end_time'    => $endtime,
                'confirm_at'    => $endtime,
            ];

            if (!$this->updateTrade($update)) {
                throw new \LogicException("订单完成失败，更新数据库失败");
            }

            if (!TradeOrder::where('tid', $tid)->update(['status' => 'TRADE_FINISHED', 'end_time' => $endtime,'confirm_at' => $endtime])) {
                throw new \LogicException("订单的子订单完成失败，更新数据库失败");
            }

            DB::commit();
        } catch (\LogicException $e) {
            DB::rollback();
            throw new \LogicException($e->getMessage());
        }

        //下单赠送积分
        $this->gainPonit($tid);
        $this->confirmTradeEvent($tradeInfo);
        return true;
    }

    /**
     * 定时任务的订单确认完成
     *
     * @Author djw
     * @param $tid
     * @param null $userId
     * @param null $shopId
     * @return bool
     * @throws \Exception
     */
    public function confirmReceiptCommands($tid)
    {
        try{
            return $this->confirmReceipt($tid);
        }catch (\Exception $exception) {
            Log::error($tid.'自动收货:'. $exception->getMessage());
//            testLog('自动收货:'. $exception->getMessage());
            return false;
        }
    }

    private function __check($tradeInfo)
    {
        if (!$tradeInfo) {
            throw new \LogicException("没有需要完成的订单!");
        }

        if ($tradeInfo['status'] != "WAIT_BUYER_CONFIRM_GOODS") {
            throw new \LogicException("未发货订单不可确认收货");
        }

        if ($tradeInfo['cancel_status'] && !in_array($tradeInfo['cancel_status'], ['NO_APPLY_CANCEL', 'FAILS'])) {
            throw new \LogicException("该订单已经处于退款阶段，不能确认收货");
        }

        //子订单在售后时不能确认收货
        if ($tradeInfo['trade_order']) {
            $after_sales_status = ['SUCCESS', 'CLOSED', 'SELLER_REFUSE_BUYER', 'SELLER_SEND_GOODS'];
            foreach ($tradeInfo['trade_order'] as $order) {
                if ($order['after_sales_status'] && !in_array($order['after_sales_status'], $after_sales_status)) {
                    throw new \LogicException("该订单有商品处于售后阶段，不能确认收货");
                }
            }
        }

        return true;
    }

    /**
     * 确认收货触发的事件
     *
     * @param array $tradeInfo 订单数据
     */
    public function confirmTradeEvent($tradeInfo)
    {
        $data['tid'] = $tradeInfo['tid'];
        $data['user_id'] = $tradeInfo['user_id'];
        $data['shop_id'] = $tradeInfo['shop_id'];
        //积分
        $data['obtain_point_fee'] = $tradeInfo['obtain_point_fee'];
        $data['consume_point_fee'] = $tradeInfo['consume_point_fee'];
        $data['payment'] = $tradeInfo['amount'];
        $data['post_fee'] = $tradeInfo['post_fee'];
        $data['points_fee'] = $tradeInfo['points_fee'];

        foreach ($tradeInfo['trade_order'] as $key => $val) {
            $orderData[$key]['oid'] = $val['oid'];
            $orderData[$key]['goods_id'] = $val['goods_id'];
            $orderData[$key]['quantity'] = $val['quantity'];
        }

        $data['order'] = $orderData;

//        event::fire('trade.confirm', [$data, $this->operator]);
        //todo 以后需要修改
        $userInfo = \ShopEM\Models\UserAccount::where('id', $data['user_id'])->first();
        $operator = [
            'op_id'        => $data['user_id'],
            'op_account'   => $userInfo['mobile'],
            'account_type' => 'member',
        ];
        $this->confirmTradeLog($data, $operator);
        $this->updateSoldQuantity($data);
//        $this->confirmPoint($data);
//        $this->confirmExperience($data);
    }

    /**
     * 确认收货订单事件，更新商品销量 --------暂时写在这里
     *
     * @param array $data 保存的订单结构
     */
    public function updateSoldQuantity($data)
    {
        foreach ($data['order'] as $key => $val) {
            $apiData = array('goods_id' => $val['goods_id'], 'quantity' => $val['quantity']);
            try {
                GoodsCountService::updateSoldQuantity($apiData);
            } catch (\Exception $e) {
                $message = $e->getMessage();
                Log::error('商品'. $val['goods_id'] . '销量统计失败' . $message);
                return false;
            }
        }

        return true;
    }

    /**
     * 确认收货订单事件，更新积分 --------暂时写在这里
     *
     * @param array $data 保存的订单结构
     */
    public function confirmPoint($data)
    {
        $params['user_id'] = $data['user_id'];
        $params['type'] = "obtain";
        // $params['behavior'] = "购物获得积分";
        $params['behavior'] = "消费增积分";
        $params['remark'] = "当前积分来自订单：" . $data['tid'];
        $params['num'] = $data['obtain_point_fee'];

        try {
            $result = $this->updateUserPoint($params);
            if (!$result) {
                $message = '更新积分失败[日志]';
//                logger::info('event listeners_confirmPoint:'.$message);
                throw new \LogicException($message);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
//            logger::info('event listeners_confirmPoint:'.$message);
            throw new \Exception($message);
        }

        return true;
    }

    /**
     * 更新积分 --------暂时写在这里
     *
     * @Author djw
     * @param $params
     * @return bool
     */
    public function updateUserPoint($params)
    {
        switch ($params['type']) {
            case "obtain":
                $params['modify_point'] = abs($params['num']);
                break;
            case "consume":
                $params['modify_point'] = 0 - $params['num'];
                break;
        }

        $paramsPoint = array(
            'user_id'       => $params['user_id'],
            'modify_remark' => $params['remark'],
            'modify_point'  => $params['modify_point'],
            'behavior'      => $params['behavior'],
        );
        $result = UserPointsService::changePoint($paramsPoint);
        return $result;
    }

    /**
     * 确认收货订单事件，确认会员经验值 --------暂时写在这里
     *
     * @param array $data 保存的订单结构
     */
    public function confirmExperience($data)
    {
        $params['user_id'] = $data['user_id'];
        $params['type'] = "obtain";
        $params['num'] = $data['payment'] + $data['points_fee'] - $data['post_fee'];
        $params['behavior'] = "购物获得经验值";
        $params['remark'] = "当前经验值来自订单：" . $data['tid'];

        try {
            $result = UserExperienceService::updateUserExp($params);
            if (!$result) {
                $message = '更新会员经验值[日志]';
//                logger::info('event listeners_confirmExperience:'.$message);
                throw new \LogicException($message);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
//            logger::info('event listeners_confirmExperience:'.$message);
            throw new \Exception($message);
        }

        return true;
    }

    /**
     * 确认收货订单事件，确认收货日志 --------暂时写在这里
     *
     * @param array $data 保存的订单结构
     * @param array $operator 操作员参数
     */
    public function confirmTradeLog($data, $operator)
    {
        $logText = '确认订单成功！';

        $logData = array(
            'rel_id'   => $data['tid'],
            'op_id'    => $operator['op_id'],
            'op_name'  => $operator['op_account'] ? $operator['op_account'] : '系统',
            'op_role'  => $operator['account_type'],
            'behavior' => 'confirm',
            'log_text' => $logText,
        );

        if (!TradeLog::create($logData)) {
            $message = '订单确认收货失败[日志]';
//            logger::info('event listeners_confirmTradeLog:'.$message);
            Log::error( '订单'. $data['tid'] . '销量统计失败' . $message);
            return false;
        }

        return true;
    }


    /**
     * 子订单售后状态更新,订单售后状态处理(未完成订单退款成功时更新订单状态为关闭)
     *
     * @Author hfh_wind
     * @param $params
     * @return bool
     * @throws \Exception
     */
    public function afterSaleOrderStatusUpdate($params)
    {
        $ifAll = false;
        $orderData = [];
        $tradesData = $params['tradesData'];
        //数据监测
        try {
            $data = $this->afterSaleOrder__check($params, $orderData, $tradesData, $ifAll);
        } catch (\LogicException $e) {
            throw new \LogicException($e->getMessage());
        }

        DB::beginTransaction();

        try {
            //当平台退款完成时需要改变订单状态
            if ($ifAll && $tradesData && $tradesData['status'] != "TRADE_FINISHED") {
                $updataTradeData['status'] = "TRADE_CLOSED";
                $updataTradeData['tid'] = $params['tid'];
                $updataTradeData['cancel_reason'] = "退款成功，交易自动关闭";
                $result = Trade::where(['tid' => $params['tid']])->update($updataTradeData);
                if (!$result) {
                    throw new \LogicException('退款失败，关闭订单异常');
                }
            } else {
                //售后退款后需要改变订单的最后修改时间
                Trade::where(['tid' => $params['tid']])->update(['updated_at' => Carbon::now()->toDateTimeString()]);
            }

            //子订单售后状态改变
            //售后不改变订单状态
            $result = TradeOrder::where(['oid' => $params['oid']])->update($data);
            if (!$result) {
                throw new \LogicException('退款失败，订单状态更新失败');
            }

            if ($orderData) {
                if ($orderData['status'] == "TRADE_FINISHED") {
                    $orderData['refund_fee'] = $params['total_fee'];
                    //处理积分回扣和经验值回扣(订单完成)
//                    $this->__PointAndExp($orderData);
                    $this->__yitianPoint($orderData);

                    //只有在订单完成后的售后退款才需要进行，结算退款处理
//                    $this->__settlement($tradesData, $orderData);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        return true;
    }

    /**
     * 检查订单数据
     *
     * @Author hfh_wind
     * @param $params
     * @param $orderData
     * @param $tradesData
     * @param $ifAll
     * @return bool
     */

    private function afterSaleOrder__check($params, &$orderData, $tradesData, &$ifAll)
    {

        if ($params['after_sales_status'] == "SUCCESS") {

            $ifAll = true;

            foreach ($tradesData['trade_order'] as $key => $order) {
                if ($order['user_id'] != $params['user_id']) {
                    throw new \LogicException('数据有误，请重新处理');
                    return false;
                }

                if ($order['oid'] != $params['oid'] && $order['after_sales_status'] != 'SUCCESS') {
                    $ifAll = false;
                }

                if ($order['oid'] == $params['oid']) {
                    $orderData = $order;
                }

                if ($order['oid'] == $params['oid'] && $order['status'] != "TRADE_FINISHED") {
                    $data['status'] = "TRADE_CLOSED_AFTER_PAY";
                    $data['refund_fee'] = $params['refund_fee'];
                }
            }
        }

        $data['after_sales_status'] = $params['after_sales_status'];
        return $data;

    }


    /* private function __PointAndExp($orderData)
     {
         $num = app::get('systrade')->rpcCall('user.pointcount', array('money' => $orderData['refund_fee']));
         $pointdata = $expdata = array(
             'user_id'  => $orderData['user_id'],
             'type'     => 'consume',
             'num'      => $num,
             'behavior' => "订单： " . $orderData['tid'],
             'remark'   => '退款扣减购物获得的积分',
         );
         if ($num) {
             $result = app::get('systrade')->rpcCall('user.updateUserPoint', $pointdata);
             if (!$result) {
                 throw new \LogicException('退款失败，关闭订单异常');
             }
         }

         if ($orderData['payment']) {
             $expdata['remark'] = '退款扣除购物获得的经验值';
             $expdata['num'] = $orderData['payment'];
             $result = app::get('systrade')->rpcCall('user.updateUserExp', $expdata);
             if (!$result) {
                 throw new \LogicException('退款失败，关闭订单异常');
             }
         }
     }*/

    //取消订单返还使用的积分
    public function __yitianPoint($orderData)
    {
        $tradesData = Trade::where(['tid' => $orderData['tid']])->first();
//        $paymentInfo = Payment::where(['payment_id' => $tradesData['payment_id']])->first();


        $order = [
            ['goods_name' => $orderData['goods_name']]
        ];
        $params['order'] = $order;
        $params['user_id'] = $tradesData['user_id'];
        $params['behavior'] = "订单号： " . $orderData['tid'];

        //使用的积分优惠分摊
        if ($tradesData->consume_point_fee) {
            if ($tradesData->points_fee && $tradesData->points_fee > 0) {
                $consume_point_fee = self::avgDiscountPoint($tradesData->consume_point_fee, $tradesData->points_fee,
                    $orderData['avg_points_fee']); //抵扣的积分
            } else {
                $consume_point_fee = $tradesData->consume_point_fee;
            }
            $params['type'] = "obtain";
            $params['remark'] = "（子订单：".$orderData['oid']."）退款返还购物使用的积分";
            $params['num'] = $consume_point_fee;
            $params['log_type'] = "trade";
            $params['log_obj'] = $orderData['tid'];

            if ($params['num'] > 0) {
                $yitiangroup_service = new \ShopEM\Services\YitianGroupServices($tradesData->gm_id);
                $result = $yitiangroup_service->updateUserYitianPoint($params);
                if (!$result) {
                    throw new \LogicException('退款失败，关闭订单异常');
                }
            }
        }

        //获得积分的回退
        /*if ($tradesData->obtain_point_fee) {
            $rate = $tradesData->obtain_point_fee / $tradesData->amount;
            $obtain_point_fee = $orderData['amount'] * $rate;
            $params['type'] = "consume";
            $params['remark'] = "退款扣减购物获得的积分";
            $params['num'] = $obtain_point_fee;
            if ($params['num'] > 0) {
                $yitiangroup_service = new \ShopEM\Services\YitianGroupServices;
                $result = $yitiangroup_service->updateUserYitianPoint($params);
                if (!$result) {
                    throw new \LogicException('退款失败，关闭订单异常');
                }
            }
        }*/
    }

    /**
     * [get3rdShip 获取物流信息]
     * @Author mssjxzw
     * @param  [type]  $rid [关联订单号]
     * @return [type]       [description]
     */
    public function getShip($rid)
    {
        $tid = $rid;
        $rid = \ShopEM\Models\TradeRelation::where([['rid', '=', $rid]])->first();
        if ($rid) {
            $source = \ShopEM\Models\SourceConfig::where('source', $rid->source)->first();
            $service = '\\ShopEM\\Services\\' . $source->service;
            try {
                $lhyServices = new $service();
                $res = $lhyServices->getTradeShip($rid->rid);
            } catch (\Exception $e) {
                $res['track']['tracker'] = [];
                $res['logi'] = [
                    'logi_name' => '暂时无法获取到',
                    'logi_no'   => '暂时无法获取到',
                ];
            }
        } else {
            $service = new \ShopEM\Services\LhyPushService();
            $res = $service->getTradeShip($tid);
            if (isset($res['status']) && $res['status'] == 'fail') {
                $logi = \ShopEM\Models\LogisticsDelivery::where('tid', $tid)->first();
                $res['track']['tracker'] = [];
                if (isset($logi->logi_name) && isset($logi->logi_no)) {
                    $res['logi'] = [
                        'logi_name' => $logi->logi_name ? $logi->logi_name : '暂时无法获取',
                        'logi_no'   => $logi->logi_no ? $logi->logi_no : '暂时无法获取',
                    ];
                } else {
                    $res['logi'] = [
                        'logi_name' => '暂时无法获取',
                        'logi_no'   => '暂时无法获取',
                    ];
                }
            }
        }
        return $res;
    }

    /**
     * 支付后赠送益田积分
     *
     * @Author djw
     * @param $payment_id
     * @return bool
     */
    /*public static function gainPonit($payment_id)
    {
        $repository = new \ShopEM\Repositories\ConfigRepository;
        //判断是否开启了下单送积分
        $point_config = $repository->configItem('shop', 'point');
        if (isset($point_config['open_point_gain']) && $point_config['open_point_gain']['value']) {
            //下单送积分的比率
            if (isset($point_config['gain_point_number']) && $point_config['gain_point_number']['value']) {
                //获取支付单信息和订单信息
                $payment = Payment::where('payment_id', $payment_id)->first();
                $trades = Trade::whereIn('tid', function ($query) use ($payment_id) {
                    $query->select('tid')
                        ->from(with(new \ShopEM\Models\TradePaybill)->getTable())
                        ->where('payment_id', $payment_id);
                })->get();

                if ($trades) {
                    $tid_array = [];
                    $goods_name_array = [];
                    //更新订单获得积分字段和存储订单tid和子订单商品名称
                    foreach ($trades as $k => $trade) {
                        $tid_array[] = $trade['tid'];
                        $obtain_point_fee = $trade['amount'] * $point_config['gain_point_number']['value'];
                        Trade::where('tid', $trade['tid'])->update(['obtain_point_fee' => $obtain_point_fee]);
                        foreach ($trade['trade_order'] as $order_val) {
                            $goods_name_array[]['goods_name'] = $order_val['goods_name'];
                        }
                    }

                    $gain_point = $payment['amount'] * $point_config['gain_point_number']['value'];
                    if ($gain_point) {
                        $tids = implode($tid_array, '\\');
                        $pointdata = array(
                            'user_id'  => $payment['user_id'],
                            'order'    => $goods_name_array,
                            'type'     => 'obtain',
                            'num'      => $gain_point,
                            'behavior' => "订单号： " . $tids,
                            'remark'   => '下单赠送积分',
                        );
                        $yitiangroup_service = new \ShopEM\Services\YitianGroupServices;
                        $result = $yitiangroup_service->updateUserYitianPoint($pointdata);
                        if (!$result) {
                            pointErrorLog('支付单' . $payment['payment_id'] . '下单赠送积分失败');
                        }
                    }
                }
            }
        }
        return true;
    }*/

    /**
     * 支付后赠送益田积分
     *
     * @Author djw
     * @param $tid
     * @return bool
     */
    public function gainPonit($tid)
    {
        $trade = Trade::where('tid', $tid)->where('status', 'TRADE_FINISHED')->first();
        if (!$trade) {
            return true;
        }
        $repository = new \ShopEM\Repositories\ConfigRepository;
        //判断是否开启了下单送积分
        $point_config = $repository->configItem('shop', 'point', $trade->gm_id);
        if (isset($point_config['open_point_gain']) && $point_config['open_point_gain']['value']) {
            if ($trade) {
                $shop_id = $trade['shop_id'];
                $shop = Shop::select('user_obtain_point', 'store_code','open_point_deduction','gm_id')->find($shop_id);
                //如果没开启则不赠送积分
                if ($shop['open_point_deduction'] <= 0)
                {
                    return true;
                }
                $fee = $shop['user_obtain_point']['fee']; //每满X元
                $point = $shop['user_obtain_point']['point']; //赠送Y积分
                $amount = $trade['amount'];
                $gain_point = floor($amount / $fee) * $point;//每满5元赠送1积分

                //记录crm积分推送，目前使用的是crm的比例 10:1
                $pointLog = array(
                    'gm_id'     => $trade->gm_id,
                    'amount'    => $amount,
                    'user_id'   => $trade['user_id'],
                    'remark'    => "订单号： " . $tid,
                    // 'behavior'  => '订单完成/确认收货赠送积分',
                    'behavior'  => '确认收货增积分',                    
                    'type'      => 'obtain',
                );
                $log_id = $this->crmPushPoint($pointLog);

                //异步推送订单信息给CRM
                // $cardType = UserRelYitianInfo::where('user_id',$trade['user_id'])->where('gm_id',$shop->gm_id)->value('card_code');
                $info = [
                    'storeCode'        => $shop['store_code'],                         //门店编码
                    'transTime'        => $trade['pay_time'],
                    'user_id'          => $trade['user_id'],
                    'receiptNo'        => $tid,
                    'payableAmount'    => $trade['total_fee'] + $trade['post_fee'],
                    'netAmount'        => $amount,
                    'discountAmount'   => $trade['discount_fee'],
                    'getPointAmount'   => $amount,
                    'log_id'           => $log_id,
                ];

                TradePush::dispatch($info);
            }
        }
        return true;
    }

    /**
     * [crmPushPoint 记录crm推送记录 当前版本使用 2020-3-24 11:01:01]
     * @param string $value [description]
     */
    public function crmPushPoint($params)
    {
        $point = 0.01; // 暂时写死，最后会以crm返回的更新
        switch ($params['type'])
        {
            case "obtain":
                $params['modify_point'] = abs($point);
                break;
            case "consume":
                $params['modify_point'] = 0 - $point;
                break;
        }

        $paramsPoint = array(
            'user_id' => $params['user_id'],
            'modify_remark' => $params['remark'],
            'modify_point' => $params['modify_point'],
            'behavior' => $params['behavior'],
        );
        $gm_id = $params['gm_id'];
        $yitiangroup_service = new \ShopEM\Services\YitianGroupServices($gm_id);
        $result = $yitiangroup_service->tradeChangePoint($paramsPoint);

        return $result;
    }

    /**
     * 线上分润计算,是否预估收益  预估收益不进行真正进出帐
     * @Author hfh_wind
     * @param $trade_Data
     * @return null
     */
    public function DistributionReward($payment_id)
    {
        $tids = TradePaybill::where(['payment_id' => $payment_id])->get();

        if (count($tids) <= 0) {
            return true;
        }

        Try {
            foreach ($tids as $value) {

                $order_infos = TradeOrder::where('tid', $value['tid'])->select('tid', 'oid', 'user_id', 'shop_id',
                    'goods_id', 'sku_id', 'amount', 'act_reward', 'is_distribution', 'quantity', 'goods_name','profit_sharing','rewards')->get();
                foreach ($order_infos as $order_value) {

                    //如果不是推广订单或者已经产生预估收益的订单跳过
                    if ($order_value['is_distribution'] == 0 || $order_value['act_reward'] == 1) {
                        continue;
                    }

                    $user_id = $order_value['user_id'];

                    $shop_site = ShopAttr::where(['shop_id' => $order_value['shop_id']])->first();


                    if (!empty($shop_site) && $shop_site['promo_person'] == 1) {
                        // 获取用户的推广员ID
                        $related_logs = RelatedLogs::where([
                            'user_id' => $user_id,
                            'status'  => 1
                        ])->select('pid')->first();

                        //先判断商家对此商品有没有开启推物
                        $goods_site = Goods::where(['id' => $order_value['goods_id']])->select('is_rebate','rewards')->first();
                        // 判断商品是否开启推广
                        if($goods_site['is_rebate'] == 0){
                            storageLog('商品未开启分销：'.$order_value['goods_id'],'success');
                            continue;
                        }

                        /*   父级分润  start  */
                        if ($related_logs['pid']) {
                            // 查询表中是否存在分销信息
                            $person_count = TradeEstimates::where([
                                'type' => 0,
                                'oid'  => $order_value['oid'],
                                'iord' => 1
                            ])->first();

                            $check_act = (new Activity())->checkPromotion($order_value['goods_id']);
                            if ($check_act['code'] == 1) {
                                $reward_person = 0;
                            } else {
                                $reward_person = $order_value['rewards'];  // 分成佣金
                            }
                            if ($person_count) {

                                $insert_data['shop_id'] = $order_value['shop_id'];
                                $insert_data['goods_id'] = $order_value['goods_id'];
                                $insert_data['user_id'] = $user_id;
                                $insert_data['pid'] = $related_logs['pid'];
                                $insert_data['tid'] = $order_value['tid'];
                                $insert_data['oid'] = $order_value['oid'];
                                $insert_data['reward_value'] = $reward_person;
                                $insert_data['type'] = 0;
                                TradeEstimates::where([
                                    'type' => 0,
                                    'oid'  => $order_value['oid'],
                                    'iord' => 1
                                ])->update($insert_data);
                            } else {

                                $insert_data['shop_id'] = $order_value['shop_id'];
                                $insert_data['goods_id'] = $order_value['goods_id'];
                                $insert_data['user_id'] = $user_id;
                                $insert_data['pid'] = $related_logs['pid'];
                                $insert_data['tid'] = $order_value['tid'];
                                $insert_data['oid'] = $order_value['oid'];
                                $insert_data['reward_value'] = $reward_person;
                                $insert_data['type'] = 0;
                                TradeEstimates::create($insert_data);
                            }

                            //父级分润
                            $count = UserDeposit::where('user_id', $related_logs['pid'])->count();
                            if ($count) {
                                //增加预估收益
                                UserDeposit::where('user_id', $related_logs['pid'])->increment('estimated',
                                    $reward_person);
                            } else {
                                $deposit_data['user_id'] = $related_logs['pid'];
                                $deposit_data['estimated'] = $reward_person;
                                UserDeposit::create($deposit_data);
                            }
                        }

                    }

                    //标识已经生成预估
                    TradeOrder::where('oid', $order_value['oid'])->update(['act_reward' => 1]);
                }

            }

            if (isset($user_id)) {
                //标记买过
                RelatedLogs::where([
                    'user_id' => $user_id,
                    'status'  => 1
                ])->update(['is_buy' => 1]);
            }

        } catch (\Exception $e) {

            $message = $e->getMessage();
            storageLog($message);
            throw new \Exception($message);
        }

        return true;
    }

    /**
     * 支付单处理分成
     * @Author hfh_wind
     * @param $payment_id
     * @return bool
     * @throws \Exception
     */
    public function DistributionProfiles($payment_id)
    {
        $tids = TradePaybill::where(['payment_id' => $payment_id])->get();

        if (count($tids) <= 0) {
            return true;
        }
//        DB::beginTransaction();
        Try {

            foreach ($tids as $value) {

                $order_infos = TradeOrder::where('tid', $value['tid'])->where('profit_sign', 1)->select('tid', 'oid',
                    'user_id', 'shop_id',
                    'goods_id', 'sku_id', 'amount', 'profit_sharing', 'profit_sign', 'quantity', 'goods_name','activity_type')->get();

                //如果不是分成订单跳过
                if (count($order_infos) <= 0) {
                    return true;
                }
                foreach ($order_infos as $order_value) {
                    //砍价商品不参与分佣

                    if($order_value['profit_sharing'] <=0  ||  $order_value['activity_type']  =='is_kanjia'){

                        continue;
                    }

                    $user_id = $order_value['user_id'];

                    $shop_site = ShopAttr::where(['shop_id' => $order_value['shop_id']])->first();

                    //检查店铺是否有权限分销
                    if (!empty($shop_site) && $shop_site['promo_person'] == 1) {

                        if ($shop_site['promo_good'] == 1) {

                            //找到推广员上级
                            $buy_role = DB::table('user_accounts')->where([
                                'id'           => $user_id, //推广员id
                                'partner_status' => 0
                            ])->select('partner_role')->first();
                            //0-普通会员,1-推广员,2-小店,3-分销商,4-经销商

                            //如果是推广员
                            if($buy_role->partner_role==1){
                                //推广员id
                                $get_partner = $buy_role;
                                //推广员id
                                $is_promoter = $user_id;
                            }else{
                                //必须是status =1 才能分推广佣金;
                                $related_log = RelatedLogs::where([
                                    'user_id' => $user_id,
                                    'status'  => 1
                                ])->select('pid')->first();

                                if (empty($related_log)) {
                                    continue;
                                }
                                //推广员id
                                $is_promoter = $related_log['pid'];

                                //查推广员信息
                                $get_partner = DB::table('user_accounts')->where([
                                    'id'           => $is_promoter, //推广员id
                                    'partner_status' => 0
                                ])->select('partner_role')->first();
                                //0-普通会员,1-推广员,2-小店,3-分销商,4-经销商

                                if (empty($get_partner)) {
                                    continue;
                                }
                            }

                            $get_partner_shop_id=0;
                            if($get_partner->partner_role =='2'){
                                //如果推广员是小店,就分给自己
                                $partner_id = $is_promoter;
                                $get_partner_shop_id=$is_promoter;
                            }else{
                                //找到推广员上级
                                $get_partner= PartnerRelatedLog::where(['user_id'=>$is_promoter,'type'=>2,'status'=>1,'is_own'=>0])->first();
                                if (empty($get_partner)   ||  !$get_partner['partner_id']) {
                                    continue;
                                }
                                //小店id
                                $partner_id = $get_partner['partner_id'];
                                $get_partner_shop_id=$get_partner['partner_id'];
                            }

                            //分销商id如果有就记录
                            $get_partner_dt_id=0;
                            if($get_partner_shop_id){
                                //分销商id
                                $get_partner_dt_id= PartnerRelatedLog::where(['user_id'=>$get_partner_shop_id,'type'=>3,'status'=>1])->value('partner_id');
                                $get_partner_dt_id=$get_partner_dt_id?$get_partner_dt_id:0;
                            }


                            $reward_value = $order_value['profit_sharing']; //分成金额

                            $goods_count = TradeEstimates::where([
                                'type' => 3,
                                'oid'  => $order_value['oid'],
                                'iord' => 1
                            ])->count();

                            if ($goods_count) {

                                $insert_good_data['shop_id'] = $order_value['shop_id'];
                                $insert_good_data['goods_id'] = $order_value['goods_id'];
                                $insert_good_data['user_id'] = $user_id;
                                $insert_good_data['is_promoter'] = $is_promoter; //推广员
                                $insert_good_data['pid'] = $partner_id; //这里是小店的id
                                $insert_good_data['tid'] = $order_value['tid'];
                                $insert_good_data['oid'] = $order_value['oid'];
                                $insert_good_data['reward_value'] = $reward_value;
                                $insert_good_data['type'] = 3; //分成金额
                                $insert_good_data['distributor_id'] = $get_partner_dt_id;

                                TradeEstimates::where([
                                    'type' => 3,
                                    'oid'  => $order_value['oid'],
                                    'iord' => 1
                                ])->update($insert_good_data);
                            } else {
                                $insert_good_data['shop_id'] = $order_value['shop_id'];
                                $insert_good_data['goods_id'] = $order_value['goods_id'];
                                $insert_good_data['user_id'] = $user_id;
                                $insert_good_data['is_promoter'] = $is_promoter; //推广员
                                $insert_good_data['pid'] = $partner_id; //这里是小店的id
                                $insert_good_data['tid'] = $order_value['tid'];
                                $insert_good_data['oid'] = $order_value['oid'];
                                $insert_good_data['reward_value'] = $reward_value;
                                $insert_good_data['type'] = 3; //分成金额
                                $insert_good_data['distributor_id'] = $get_partner_dt_id;

                                $create = TradeEstimates::create($insert_good_data);

                                $key = "subscribe_template_1_u_" . $partner_id;
                                $send_wx_sendSubscribe = Redis::llen($key);
                                if ($send_wx_sendSubscribe) {
                                    $get_nickname=DB::table('wx_userinfos')->where(['user_id'=>$partner_id,'user_type'=>1])->select('nickname')->first();
                                    $get_nickname=$get_nickname->nickname??'';
                                    //推送订阅消息
                                    $send_data['user_id'] = $partner_id; //这里是小店的id
                                    $send_data['subscribe_id'] = 1;//佣金提醒
                                    $send_data['data'] = [
                                        $order_value['goods_name'],
                                        $order_value['amount'],
                                        $reward_value,
                                        $get_nickname,
                                        "如若产生退款，将扣除订单产生的分成佣金"
                                    ];//提醒字段
//                                    testLog($send_data);
                                    SendSubscribeMessage::dispatch($send_data);
                                }
                            }
                        }

                    }
                }
            }

//            DB::commit();
        } catch (\Exception $e) {
//            DB::rollback();
            $message = $e->getMessage();
            throw new \Exception($message);
        }

        return true;
    }

}
