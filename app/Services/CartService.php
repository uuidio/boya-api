<?php
/**
 * @Filename        CartService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */
namespace ShopEM\Services;

use ShopEM\Models\Cart;
use ShopEM\Models\Goods;
use ShopEM\Models\Shop;
use ShopEM\Models\UserAddress;
use ShopEM\Models\PayWalletConfig;
use ShopEM\Services\Marketing\Coupon;
use ShopEM\Services\Marketing\Activity;
use Illuminate\Support\Facades\Redis;

class CartService
{

    /**
     * 购物车商品数量
     *
     * @Author moocde <mo@mocode.cn>
     * @param $user_id
     * @return mixed
     */
    public static function cartSum($user_id,$gm_id=0)
    {
        return Cart::where('user_id', $user_id)
            ->leftJoin('goods', 'goods.id', '=', 'carts.goods_id')
            ->where('goods.goods_state', 1)
            ->where('carts.gm_id', $gm_id)
//            ->where('shop_id', $shop_id)
            ->sum('carts.quantity');
    }

    /**
     * 购物车里是否存在同样的商品
     *
     * @Author moocde <mo@mocode.cn>
     * @param $user_id
     * @param $goods_id
     * @return mixed
     */
    public static function existGoods($user_id, $sku_id)
    {
        return Cart::where('user_id', $user_id)
            ->where('sku_id', $sku_id)
            ->first();
    }

    /**
     * 购物车会员所有商品
     *
     * @Author moocde <mo@mocode.cn>
     * @param $user_id
     * @param $shop_id
     * @return mixed
     */
    public static function cartGoods($user_id,$gm_id=0)
    {
        $where['user_id']=$user_id;
        if($gm_id){
            $where['gm_id']=$gm_id;
        }
        return Cart::where($where)->orderBy('created_at', 'desc')
            ->get()->groupBy('shop_id'); // 可按shop_id分组
    }


    /**
     * 结算选中商品
     *
     * @Author moocde <mo@mocode.cn>
     * @param $user_id
     * @param $shop_id
     * @return mixed
     */
    public static function checkOrderGoods($user_id,$type='')
    {
        $retrunData=[];
        if(!$type){
            $retrunData= Cart::where('user_id', $user_id)
                ->where('is_checked', 1)
                ->orderBy('created_at', 'desc')
                ->get()->groupBy('shop_id')->toArray(); // 可按shop_id分组
        }else{
         $lists=[];
        $key = md5($user_id.'cart_fastbuy');
        //var_dump($params);
        $fastData=  Redis::get($key);
        if($fastData){
            $fastData=json_decode($fastData,true);
            $lists[$fastData['shop_id']][]=$fastData;
        }

        $retrunData= $lists;
       }
        return $retrunData;
    }

    public static function checkOrderGmid($user_id,$type='')
    {
        if (!$type) 
        {
            $gm_id = Cart::where('user_id', $user_id)->where('is_checked', 1)->value('gm_id');
        }
        else
        {
            $key = md5($user_id.'cart_fastbuy');
            $fastData = Redis::get($key);
            $fastData = json_decode($fastData,true);
            $gm_id = $fastData['gm_id'];
        }

        return $gm_id;
    }

    /**
     * [checkOrderShopIds 结算选中商家id数组]
     * @param  [type] $user_id [description]
     * @param  string $type    [description]
     * @return [type]          [description]
     */
    public static function checkOrderShopIds($user_id,$type='')
    {
        if (!$type) 
        {
            $shop_ids = Cart::where('user_id', $user_id)->where('is_checked', 1)->pluck('shop_id')->toArray();
            $shop_ids = array_merge(array_unique($shop_ids));
        }
        else
        {
            $key = md5($user_id.'cart_fastbuy');
            $fastData = Redis::get($key);
            $fastData = json_decode($fastData,true);
            $shop_ids[] = $fastData['shop_id'];
        }

        return $shop_ids;
    }

    /**
     * 生成订单后删除购物车选中商品
     *
     * @Author hfh_wind
     * @param $user_id
     * @return bool
     * @throws \Exception
     */
    public static function destroyCart($user_id)
    {

        try {
            Cart::where('user_id', $user_id)
                ->where('is_checked', 1)
                ->delete();
        } catch (\Exception $e) {

            throw new \Exception('删除购物车失败!' . $e->getMessage());
        }

        return true;
    }


    /**
     *
     * 根据条件查询到基本的购物车数据
     *
     * @Author hfh_wind
     * @param $filter
     * @param $type
     * @return mixed
     */
    public function getBasicCart($params,$type='')
    {
        //购物车购买和快速购买合并,不需要处理
        $setData = [];
        $Coupon = new Coupon();
        $Activity = new Activity();
        foreach ($params as $set_key => $set_value) {

//            dd($set_value);
            $shop_id = $set_value[0]['shop_id'];
            $user_id = $set_value[0]['user_id'];

            $shopInfo = Shop::where('id', $shop_id)
                ->select('id', 'shop_name', 'point_id', 'housing_id', 'shop_logo', 'is_own_shop', 'shop_type',
                    'post_fee','open_point_deduction','gm_id')
                ->first();

//            dd($setData[$set_key]['goods_lists']);
            $setData[$set_key]['goods_lists'] = $set_value;
            $setData[$set_key]['shopInfo'] = $shopInfo;
            $setData[$set_key]['groupInfo'] = ['gm_id'=>$shopInfo->gm_id,'gm_name'=>$shopInfo->gm_name];

            $logisticsTemplates = new LogisticsTemplatesServices();
            $total_price = 0;
            $total_post_fee=0;//邮费
            $total_weight=0;//重量暂时不计算设置0
            $logistics_total = [];
            foreach ($set_value as $key => $value) {

                $goods_price = $value['goods_price'] * $value['quantity'];
                $total_price += $goods_price;

                $checkRe = $this->checkGood($value['goods_id']);
                if (!$checkRe) {
                    $setData[$set_key]['goods_lists'][$key]['unusual'] = true;
                }

                if(isset($type['buyerAddr']['area_code']) &&  !empty($value['transport_id']))
                {
                    if (!isset($logistics_total[$value['transport_id']]))
                    {
                        $logistics_total[$value['transport_id']]['goods_price'] = 0;
                        $logistics_total[$value['transport_id']]['quantity'] = 0;
                        $logistics_total[$value['transport_id']]['total_weight'] = 0;
                    } 
                    $logistics_total[$value['transport_id']]['goods_price'] += $goods_price;
                    $logistics_total[$value['transport_id']]['area_code'] = $type['buyerAddr']['area_code'];
                    $logistics_total[$value['transport_id']]['quantity'] += $value['quantity'];
                    $logistics_total[$value['transport_id']]['total_weight'] += $total_weight;

                    // $post_fee=$logisticsTemplates->countFee($value['transport_id'], $type['buyerAddr']['area_code'], $goods_price, $value['quantity'], $total_weight);
                    // //商品邮费金额
                    // $total_post_fee +=$post_fee;
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
                // //商品邮费金额
                // $total_post_fee= \ShopEM\Services\TradeService::getMinInArray($post_fee_arr);
            }
            
//            $rule = \ShopEM\Models\ShopShip::where(['shop_id'=>$shop_id,'default'=>1])->first();
//            $condition = 0;
//            if ($rule) {
//                foreach ($rule->rules as $key => $value) {
//                    if ($total_price >= $value['limit'] && $condition <= $value['limit']) {
//                        $post = $value['post'];
//                        $condition = $value['limit'];
//                    }
//                }
//                //不符合设定好的运费计算规则时
//                if (!isset($post)) {
//                    $post = ($total_price >= 99)?0:$shopInfo['post_fee'];
//                }
//            }else{
//                $post = ($total_price >= 99)?0:$shopInfo['post_fee'];
//            }

            //返回店铺优惠劵信息
            $params['user_id'] = $user_id;
            $params['shop_id'] = $shop_id;
            $setData[$set_key]['shopPostFee'] = number_format($total_post_fee, 2);
            $setData[$set_key]['shopCouponList'] = $Coupon->userfulList($params, $set_value);
            $setData[$set_key]['shopActivityList'] = $Activity->userfulList($params, $set_value);
        }
        $setData = array_values($setData);
        return $setData;
    }
    /**
     * [newLists 升级成集团维度]
     * @param  [type] $lists [description]
     * @return [type]        [description]
     */
    public function newGroupLists($lists)
    {
        $data = [];
        if (!empty($lists)) 
        {
            $groupInfos = resetKey(array_column($lists, 'groupInfo'),'gm_id');
            foreach ($lists as $key => $value) 
            {
                $gmKey = $value['groupInfo']['gm_id'];
                if (!isset($data[$gmKey])) $data[$gmKey] = [];
                $data[$gmKey]['lists'][] = $value;
                $data[$gmKey]['groupInfo'] = $groupInfos[$gmKey];
            }
        }
        // sort($data);
        $data = array_values($data);
        return $data;
    }
    /**
     * 购物车归属店铺的商品商品小计(暂时不用,前端处理)
     *
     * @Author hfh_wind
     * @param $shopCartData
     * @param $shop_info
     * @param $coupon_data
     * @return array
     */
    public static function cartShopGoods($shopCartData, $coupon_data)
    {
        $total_fee = 0;
        $discount_fee = 0;

        foreach ($shopCartData as $item_key => $item_val) {
            $total_fee += $item_val->goods_price * $item_val->quantity;
        }

        if (isset($coupon_data['coupon_id'])) {
            //查找会员有无使用此劵
            $coupon_user = CouponStockOnline::where('user_id', $coupon_data['user_id'])
                ->where('coupon_id', $coupon_data['coupon_id'])
                ->where('status', '1')
                ->first();

            //如果有使用优惠劵
            if ($coupon_user) {
//                $discount_fee = self::discountFee($total_fee, $coupon_data['coupon_id']);
            }
        }

//        $post_fee = $shop_info->post_fee;
        $amount = $total_fee - $discount_fee + $post_fee;

        return [
            'amount'       => $amount,
            'points_fee'   => 0,
            'total_fee'    => $total_fee,
            'discount_fee' => $discount_fee,
        ];
    }


    public function checkGood($id)
    {
        $resInfo = Goods::where(['id' => $id])->where(['goods_state' => 1])->first();
        $result = true;
        if (empty($resInfo)) {
            $result = false;
        }
        return $result;
    }

    //判断结算的店铺能否使用钱包功能
    public function checkWalletPay($user_id, $type_key)
    {
        $type = ($type_key == 'fastbuy') ? 1 : '';
        $gm_id = self::checkOrderGmid($user_id, $type);
        $shop_ids = self::checkOrderShopIds($user_id, $type);
        $limit_shop = (new PayWalletConfig)->getLimitShop($gm_id);
        
        if($limit_shop == 'all') return true;

        foreach ($shop_ids as $shop_id) 
        {
            if (in_array($shop_id,$limit_shop)) return false;
        }
        return true;
    }


}