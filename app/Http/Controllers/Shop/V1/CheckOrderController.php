<?php
/**
 * @Filename        CheckOrderController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.mocode.cn> All rights reserved.
 * @License         Licensed <http://www.mocode.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\Activity as ActivityModel;
use ShopEM\Models\Group;
use ShopEM\Models\Shop;
use ShopEM\Repositories\CouponRepository;
use ShopEM\Services\CartService;
use ShopEM\Services\Marketing\Activity;
use ShopEM\Services\TradeService;
use ShopEM\Services\LogisticsTemplatesServices;

class CheckOrderController extends BaseController
{
    /**
     * 结算选中商品列表       【注：后续需要进行商品是否可以结算进行判断】
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @param CouponRepository $couponRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, CouponRepository $couponRepository)
    {

        $cart = new CartService();
        $type='';
        if(isset($request->type) &&  $request->type =='fastbuy'){
            $type=1;
            $lists = CartService::checkOrderGoods($this->user->id,$type);
            $cartGmId = CartService::checkOrderGmid($this->user->id,$type);

        }else{
            $lists = CartService::checkOrderGoods($this->user->id);
            $cartGmId = CartService::checkOrderGmid($this->user->id);
        }
        $pick_type_arr = ['快递','自提'];
        $type = 0;
        $recharge_num = 0;
        $goods_num = 0;
        $open_point = 1;
        $more_project = []; //是否是多项目
        foreach ($lists as $key => $value) {
            $goods_num += count($value);
            foreach ($value as $k => $v) {

                /*$seckill_good_key = 'seckill_good_' . $v['sku_id']; //秒杀商品的缓存key
                if (Cache::get($seckill_good_key)) {
                    return $this->resFailed(406, '商品"'. $v['goods_name'] .'"正在参与秒杀');
                }*/

                $sku = \ShopEM\Models\GoodsSku::where('id',$v['sku_id'])->first();
                $goods = \ShopEM\Models\Goods::where('id',$sku->goods_id)->first();
                foreach ($pick_type_arr as $kk => $vv) {
                    if (!in_array($kk, $goods->pick_type)) {
                        unset($pick_type_arr[$kk]);
                    }
                }
                /*if ($goods->source != 'self' && isset($pick_type_arr[1])) {
                    unset($pick_type_arr[1]);
                }*/
                if ($goods->trade_type == 2 && $goods->is_need_qq == 0) {
                    $type = ($type == 2)?2:1;
                    $recharge_num += 1;
                }
                if ($goods->trade_type == 2 && $goods->is_need_qq == 1) {
                    $type = 2;
                    $recharge_num += 1;
                }

                if (isset($v['params']) && ($v['params'] == 'seckill' || $v['params'] == 'seckill')) {
                    $open_point = 0;
                }
                $more_project[$goods->gm_id] = $goods->gm_id;
                $lists[$key][$k]['transport_id'] = $goods->transport_id ?? $v['transport_id']; //获取商品当前的运费模板
            }
        }

        if (count($more_project) > 1)
        {
            return $this->resFailed(500, '不支持多项目同时结算');
        }

        if (count($pick_type_arr) == 0) {
            return $this->resFailed(500, '不能同时选择只能快递和只能自提的商品下单');
        }else{
            $pick_type_new = [];
            foreach ($pick_type_arr as $key => $value) {
                $pick_type_new[] = $value;
            }
            $pick_type_arr = $pick_type_new;
        }
        if ($recharge_num == $goods_num) {
            $pick_type_arr = [];
        }

        //获取使用的活动
        $Activity = new Activity();
        $act_ids = $request->act_ids;
        if ($act_ids) {
            $lists = $Activity->updateCartGoodsToAct($lists, $act_ids);
        }

        $couponService = new \ShopEM\Services\Marketing\Coupon();
        $platform_coupon_lists = $couponService->getFullPlatformCoupon($this->user->id, $lists);
        //属于该会员的所有商品
        $adr_type['checkOrder']=1;
        $adr_type['user_id']=$this->user->id;

        if(isset($request->pick_type)  && empty($request->pick_type)){

            if(!isset($request->addr_id)){
                return $this->resFailed(700, '请选择收货地址!');
            }
            $tradeService=new  TradeService();
            //$request->pick_type为3才计算运费
            $input_data['addr_id']=$request->addr_id; //收货地址
            $input_data['pick_type']=$request->pick_type;
            $buyerAddr= $tradeService->buyerAddr($adr_type['user_id'],$input_data);
            $adr_type['buyerAddr']=$buyerAddr;
        }

        $cat_info= $cart->getBasicCart($lists,$adr_type);

        $info = \ShopEM\Models\UserProfile::find($this->user->id);

        foreach (array_column($cat_info, 'goods_lists') as $goodsList) {
            foreach ($goodsList as $goods){
                if (isset($goods['actSign']) && $goods['actSign'] == 'is_group') {
                    $group = Group::where('id', $goods['activity_id'])->select('is_use_point')->first();
                    if ($group->is_use_point == 0) $open_point = 0;
                }
                $check_act = (new Activity())->GoodsActing($goods['goods_id']);
                if ($check_act) {
                    foreach ($check_act as $act) {
                        if ($act['is_use_point'] == 0) $open_point = 0;
                    }
                }
            }
        }

        $obtain_point_rate = 0;
        // $repository = new \ShopEM\Repositories\ConfigRepository;
        // //判断是否开启了下单送积分
        // $point_config = $repository->configItem('shop', 'point', current($more_project) );
        // if (isset($point_config['open_point_gain']) && $point_config['open_point_gain']['value']) {
        //     //下单送积分的比率
        //     if (isset($point_config['gain_point_number']) && $point_config['gain_point_number']['value']) {
        //         $obtain_point_rate = $point_config['gain_point_number']['value'];
        //     }
        // }
        $point_unit = '积分';
        $selfGmId = \ShopEM\Models\GmPlatform::gmSelf();
        if ($selfGmId == $cartGmId) {
            $point_unit = '牛币';
        }
        return $this->resSuccess([
            'lists' => $cat_info,
            'ziti_info'=>[
                'mobile'=>$this->user->mobile,
            ],
            'point_unit' => $point_unit,
            'trade_type'=>$type,
            'pick_type'=>$pick_type_arr,
            'coupons' => ['platform' => $platform_coupon_lists],
            'obtain_point_rate' => $obtain_point_rate,
            'open_point' => $open_point,
            'current_gm_id' => current($more_project),
        ]);
    }

    /**
     * 总金额处理(判断是否可以使用积分抵扣和是否可以抵扣运费)
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reckonPoint(Request $request){
        $result = [
            'consume_point_fee' => 0,
            'points_fee' => 0,
            'is_enable' => false
        ];
        $user_id=$this->user->id;
        $open_point = $request->open_point ?? 1;
        if (isset($request->type) && $request->type == 'fastbuy') {
            $type = 1;
            $gm_id = CartService::checkOrderGmid($user_id, $type);
            $shop_ids = CartService::checkOrderShopIds($user_id, $type);

        } else {
            $gm_id = CartService::checkOrderGmid($user_id);
            $shop_ids = CartService::checkOrderShopIds($user_id);
        }
        //判断是否开启了积分抵扣
        //2020-3-17 16:03:25 nlx 改版 升级成针对店铺
        $isOpenPointDeduction = TradeService::isOpenPointDeduction($gm_id);
        if ($isOpenPointDeduction && $open_point) {
            $total_money = $request->total_money;
            if (!$total_money) {
                return $this->resFailed(414, '参数不全');
            }
            //必须店铺同时都开启积分抵扣功能 ego+
            $shopOpenPoint = Shop::where('open_point_deduction',0)->whereIn('id',$shop_ids)->doesntExist();
            if ($shopOpenPoint)
            {
                /**
                 * 积分抵扣的判断
                 **/
                $yitiangroup_service = new \ShopEM\Services\YitianGroupServices($gm_id);
                $user_point = $yitiangroup_service->updateUserRewardTotal($this->user->mobile);
                $compute = TradeService::computePoint($total_money,$gm_id);
                $result = $compute;
                $result['is_enable'] = false;
                if ($user_point >= $compute['consume_point_fee']) {
                    $result['is_enable'] = true;
                }
                if ($compute['points_fee'] == 0) {
                    $result['is_enable'] = false;
                }
            }
        }

        //减免的邮费金额
        $service = new LogisticsTemplatesServices();
        $result['freeOrderPost']=$service->freeOrderPost($request->total_money,$user_id, $gm_id);

        return $this->resSuccess($result);
    }

    /**
     * [zitiLists 店铺自提列表]
     * @Author mssjxzw
     * @param  integer $shop_id [description]
     * @return [type]           [description]
     */
    public function zitiLists($shop_id = 0)
    {
        if ($shop_id < 1) {
            return $this->resFailed(414, '参数不全');
        }else{
            $data['shop_id'] = $shop_id;
        }
        $data['statue'] = 1;
        $lists = \ShopEM\Models\ShopZiti::where($data)->get();
        return $this->resSuccess($lists);
    }
}
