<?php
/**
 * @Filename        CartController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\AddCartRequest;
use ShopEM\Models\Cart;
use ShopEM\Models\GoodsSku;
use ShopEM\Models\Group;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\PointActivityGoods;
use ShopEM\Models\SecKillGood;
use ShopEM\Models\SecKillOrder;
use ShopEM\Services\CartService;
use ShopEM\Services\GoodsService;
use ShopEM\Services\Marketing\Activity;
use ShopEM\Jobs\GroupClearUser;
use ShopEM\Services\TradeService;

class CartController extends BaseController
{
    /**
     * 店铺购物车数量
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function CartNum(Request $request)
    {
        $user_id = $this->user->id ?? null;
        $gm_id = $request['gm_id']??$this->GMID;
        $num = 0;
        if ($user_id) {
            $num = CartService::cartSum($user_id,$gm_id);
        }

        return $this->resSuccess(['num' => $num]);
    }

    /**
     * 添加商品到购买车
     *
     * @Author moocde <mo@mocode.cn>
     * @param AddCartRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AddCartRequest $request)
    {
        $type = isset($request['type']) ? $request['type'] : '';
        $group_bn = isset($request['groups_bn']) ? $request['groups_bn'] : '';
        $data = $request->only('sku_id', 'quantity');

        $data['user_id'] = $this->user->id;
        $goodsSaleAble = GoodsService::saleAble($data['sku_id'], $data['user_id'], $data['quantity']);

//        dd(Redis::get($data['user_id'].'group'));

        if ($goodsSaleAble['code'] === 0) {
            return $this->resFailed(406, $goodsSaleAble['message']);
        }

        //默认选中
        if (isset($request->is_checked) && $request->is_checked == 1) {
            $data['is_checked'] = 1;
        }

        /*$seckill_good_key = 'seckill_good_' . $data['sku_id']; //秒杀商品的缓存key
        if (Cache::get($seckill_good_key)) {
            return $this->resFailed(406, '该商品正在参与秒杀');
        }*/

        $goods = $goodsSaleAble['data_sku'];

        $data['goods_id'] = $goods->goods_id;
        $data['sku_id'] = $goods->id;
        $data['shop_id'] = $goods->shop_id;
        $data['goods_name'] = $goods->goods_name;
        $data['goods_info'] = $goods->goods_info;
        $data['goods_image'] = $goods->goods_image;
        $data['transport_id'] = $goodsSaleAble['data_goods']['transport_id'];
        $data['gm_id'] = $goods->gm_id;

        if ($type && $type == 'fastbuy') {

            $data['goods_info'] = [];
            $data['goods_price'] = $goods->goods_price;
            $data['goods_info']['id'] = $goods->id;
            $data['goods_info']['goods_name'] = $goods->goods_name;
            $data['goods_info']['goods_info'] = $goods->goods_info ?: '';
            $data['goods_info']['shop_id'] = $goods->shop_id;
            $data['goods_info']['gc_id'] = $goods->gc_id;
            $data['goods_info']['goods_serial'] = $goods->goods_serial;
            $data['goods_info']['goods_stock'] = $goods->goods_stock;
            $data['goods_info']['goods_marketprice'] = $goods->goods_marketprice ?? 0;

            //当前sku信息
            $sku_info = [];
            $spec_name_array = empty($goods->spec_name) ? null : unserialize($goods->spec_name);
            if ($spec_name_array && is_array($spec_name_array)) {
                $goods_spec_array = array_values($goods->goods_spec);
                foreach ($spec_name_array as $k => $spec_name) {
                    $goods_spec = isset($goods_spec_array[$k]) ? ':' . $goods_spec_array[$k] : '';
                    $sku_info[] = $spec_name . $goods_spec;
                }
            }
            $data['sku_info'] = implode(' ', $sku_info);

            //活动标识团购
            if (isset($request['actSign']) && $request['actSign'] == 'is_group') {
                $data['actSign'] = 'is_group';
                $data['params'] = 'is_group';
                $data['activity_id'] = $request['activity_id'];
                $get_group = Group::where('id', '=', $data['activity_id'])->first()->toArray();
                if(empty($get_group)){
                    return $this->resFailed(406, "团购活动不存在!");
                }
                $data['group_info'] = $get_group;

                if ($data['quantity'] > 3) {
                    return $this->resFailed(406, "团购商品每单限购3个!");
                }


                $group_sale_stock_key = $get_group['sku_id'] . '_group_sale_stock_' . $get_group['id'];//已经销售
                $group_stock_key = $get_group['sku_id'] . "_group_stock_" . $get_group['id'];//团购库存

                $group_sale_stock = Redis::get($group_sale_stock_key);//已经销售
                $group_stock = Redis::get($group_stock_key);//团购库存

                /*if ($group_stock == $group_sale_stock) {
                    return $this->resFailed(406, "该团购商品-已经结束,请重试!");
                }*/
                if ($goods->goods_stock <= 0) {
                    return $this->resFailed(406, "该团购商品-已经结束,请重试!");
                }

                //有$group_bn说明是跟团,无则是建团
                if ($group_bn) {
                    $data['groups_bn'] = $group_bn;

                    //判断团员入团
                    $GroupService = new \ShopEM\Services\GroupService;

                    $main_user_id = $GroupService->CheckGroup($group_bn);//返回团长id

                     //入团
                     $group_join=$GroupService->joinTuan($main_user_id, $group_bn, $data['user_id'], $get_group['group_size'], $get_group['group_validhours']);
                    //计入队列,未生成订单的用户踢掉
                    /*$info['user_id']=$data['user_id'];
                    $info['groups_bn']=$group_bn;
                    GroupClearUser::dispatch($info);*/

                    if (in_array($group_join['status'],[0,2,3,4])) {
                        return $this->resFailed(406, $group_join['msg']);
                    }

                    $set_group_bn_key="group_".$get_group['id'].'_'.$data['user_id'];
                    Redis::set($set_group_bn_key,$group_bn);
                }
                $data['goods_price'] = $get_group['group_price'];
            }


            if (isset($request['actSign']) && $request['actSign'] == 'point_goods') {
                $point_goods = PointActivityGoods::where('goods_id', $goods->goods_id)->latest()->first();
                if (!$point_goods) {
                    return $this->resFailed(406, '非积分活动商品');
                }
                $yitiangroup_service = new \ShopEM\Services\YitianGroupServices($point_goods->gm_id);
                $user_point = $yitiangroup_service->updateUserRewardTotal($this->user->mobile);
                if ($user_point < $point_goods->point_fee) {
                    return $this->resFailed(406, '积分不够');
                }
                
                //积分商品限制判断
                $pointGoodsObj = new \ShopEM\Services\Marketing\PointGoods;
                $check = $pointGoodsObj->buyCheck($data['user_id'],$point_goods,$data['quantity']);
                if (isset($check['code']) && $check['code']>0) {
                    return $this->resFailed(406, $check['msg']);
                }
                // $buy_point_count = TradeOrder::countUserPointGoodsBuy($data['user_id'],$point_goods->id);
                // if ($buy_point_count+$data['quantity'] > $point_goods->buy_max) {
                //     return $this->resFailed(406, '该活动商品每人限购'.$point_goods->buy_max. '件');
                // }
                $data['point_price'] = $point_goods->point_price;
                $data['point_fee'] = $point_goods->point_fee;
                $data['actSign'] = 'point_goods';
                $data['goods_price'] = $point_goods->point_price;
                $data['params'] = 'ponit_goods';
            }

            //记录redis
            $this->fastBuyStore($data);
            return $this->resSuccess();
        }

        $hasGoods = CartService::existGoods($data['user_id'], $data['sku_id']);
        if (empty($hasGoods)) {
            Cart::create($data);
        } else {
            //判断加上购物车里的商品数量后库存是否足够
            $data['quantity'] = $data['quantity'] + $hasGoods->quantity;
            if ($goods->goods_stock < $data['quantity']) {
                return $this->resFailed(406, '库存不足');
            }

            Cart::where('user_id', $data['user_id'])
                ->where('sku_id', $data['sku_id'])
                ->update($data);
        }

        return $this->resSuccess();
    }






    /**
     *  立即购买储存数据
     * @Author hfh_wind
     * @param $params
     * @return bool
     */
    public function fastBuyStore($params)
    {
        $params['is_checked'] = '1';
        $key = md5($params['user_id'] . 'cart_fastbuy');
        $params = json_encode($params);

        $res = Redis::set($key, $params);
        return true;
    }


    /**
     * 购物车列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request)
    {
        $user_id = $this->user->id;
        if (!$user_id) {
            return $this->resFailed(414, '参数错误');
        }

        $gm_id = $request['gm_id']??$this->GMID;

        $cart = new CartService();
        $lists = $cart::cartGoods($user_id,$gm_id);

        $couponService = new \ShopEM\Services\Marketing\Coupon();
        $platform_coupon_lists = $couponService->getFullPlatformCoupon($user_id, $lists);
        //属于该会员的所有商品
        $cart_info['lists'] = $cart->getBasicCart($lists);
        $cart_info['platform_coupon_lists'] = $platform_coupon_lists;
        //购物车升级成集团维度
        $cart_info['groupLists'] = $cart->newGroupLists($cart_info['lists']);
        return $this->resSuccess($cart_info);
    }

    /**
     * 获取店铺可用活动
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getShopActList(Request $request)
    {
        $user_id = $this->user->id;
        $shop_id= $request->shop_id;
        if (!$user_id || !$shop_id) {
            return $this->resFailed(414, '参数错误');
        }
        $act_lists = [];

        $lists = Cart::where('user_id', $user_id)->where('shop_id', $shop_id)->where('is_checked', 1)->get();
        if ($lists) {
            $cart = new CartService();
            $Activity = new Activity();
            foreach ($lists as $key => $value) {
                $checkRe = $cart->checkGood($value['goods_id']);
                if (!$checkRe) {
                    $lists[$key]['unusual'] = true;
                }
            }
            $params['user_id'] = $user_id;
            $params['shop_id'] = $shop_id;
            $act_lists = $Activity->userfulList($params, $lists);
        }

        return $this->resSuccess($act_lists);
    }


    /**
     * 修改购物车商品数量
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeGoodsNum(Request $request)
    {
        $cart = Cart::find($request->cart_id);

        if (empty($cart)) {
            return $this->resFailed(406);
        }
        $user_id = $this->user->id;
        $respon = GoodsService::getUserBuyLimit($cart['goods_id'], $user_id, $request->quantity);

        if ($respon['code'] === 0) {
            return $this->resFailed(406, $respon['message']);
        }

        //购物车更改商品数量时判断库存是否足够
        $sku = GoodsSku::find($cart->sku_id);
        if ($sku->goods_stock < $request->quantity) {
            return $this->resFailed(406, '库存不足');
        }

        $cart->update(['quantity' => $request->quantity]);

        return $this->resSuccess();
    }

    /**
     * 删除购物车中的商品
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delGoods(Request $request)
    {
        $seckill = new \ShopEM\Services\SecKillService();
        DB::beginTransaction();
        try {
            if (is_array($request->cart_id)) {
                $info = Cart::whereIn('id', $request->cart_id)->get();

                foreach ($info as $key => $value) {
                    //如果是秒杀清除缓存
                    if ($value->activity_id) {
                        $map['user_id'] = $value->user_id;
                        $map['sku_id'] = $value->sku_id;
                        $map['quantity'] = $value->quantity;
                        $map['seckill_ap_id'] = $value->activity_id;
                        $seckill->clearSecKillRedis($map);
                    }
                }
                Cart::whereIn('id', $request->cart_id)
                    ->delete();
            } else {
                $cart = Cart::find($request->cart_id);

                if (empty($cart)) {
                    return $this->resFailed(406);
                }
                //如果是秒杀清除
                if ($cart->activity_id) {
                    $map['user_id'] = $cart->user_id;
                    $map['sku_id'] = $cart->sku_id;
                    $map['quantity'] = $cart->quantity;
                    $map['seckill_ap_id'] = $cart->activity_id;
                    $seckill->clearSecKillRedis($map);
                }

                $cart->delete();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 更新购物车选中商品
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSelected(Request $request)
    {
        if (!empty($request->cart_ids)) 
        {
            $car_ids = is_array($request->cart_ids) ? $request->cart_ids : json_decode($request->cart_ids, true);
            $gm_ids = Cart::where('user_id', $this->user['id'])->whereIn('id', $car_ids)->pluck('gm_id')->toArray();
            $gm_ids = array_unique($gm_ids);
            if (count($gm_ids)>1) 
            {
                return $this->resFailed(701, '不支持多项目结算');
            }
        }
        Cart::where('user_id', $this->user['id'])
            ->update(['is_checked' => 0]);

        if (!empty($request->cart_ids)) 
        {
            Cart::where('user_id', $this->user['id'])
                ->whereIn('id', $car_ids)
                ->update(['is_checked' => 1]);
        }

        return $this->resSuccess();
    }

    /**
     * 返回可用的平台券
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserPlatformCouponList(Request $request)
    {
        $user_id = $this->user->id;
        if (!$user_id) {
            return $this->resFailed(414, '参数错误');
        }
        $couponService = new \ShopEM\Services\Marketing\Coupon();
        if (isset($request->type) && $request->type == 'fastbuy') {
            $type = 1;
            $lists = CartService::checkOrderGoods($user_id, $type);
            $gm_id = CartService::checkOrderGmid($user_id, $type);

        } else {
            $lists = CartService::checkOrderGoods($user_id);
            $gm_id = CartService::checkOrderGmid($user_id);
        }

        $platform_coupon_lists = [];

        if ($lists) {
            //获取使用的活动
            $Activity = new Activity();
            $act_ids = $request->act_ids;
            if ($act_ids) {
                $lists = $Activity->updateCartGoodsToAct($lists, $act_ids);
            }

            $coupon_ids = $request->coupon_ids;
            if ($coupon_ids) {
                $lists = $couponService->updateCartGoodsToCoupon($coupon_ids, $lists);
            }
            $platform_coupon_lists = $couponService->getFullPlatformCoupon($user_id, $lists, $gm_id);
        }

        return $this->resSuccess($platform_coupon_lists);
    }

    /**
     * 返回可用的活动
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserActList(Request $request)
    {
        $user_id = $this->user->id;
        if (!$user_id) {
            return $this->resFailed(414, '参数错误');
        }

        if (isset($request->type) && $request->type == 'fastbuy') {
            $type = 1;
            $lists = CartService::checkOrderGoods($user_id, $type);

        } else {
            $lists = CartService::checkOrderGoods($user_id);
        }

        $act_lists = [];

        if ($lists) {
            $params['user_id'] = $user_id;
            $Activity = new Activity();
            foreach ($lists as $shop_id => $shop) {
                $params['shop_id'] = $shop_id;
                $act_lists[]['shopActivityList'] = $Activity->userfulList($params, $shop);
            }
        }

        return $this->resSuccess($act_lists);
    }


    /**
     *  存储用户选择的活动
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function selectedAct(Request $request)
    {
        if (!$request->has('act_ids')) {
            return $this->resFailed(414, '参数错误');
        }
        $user_id = $this->user->id;
        $key = md5($user_id . 'selected_act');
        $params = json_encode($request->act_ids);

        $res = Redis::set($key, $params);
        return $this->resSuccess();
    }
}
