<?php
/**
 * @Filename 	优惠券功能服务
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-03-11 17:33:42
 * @version 	V1.0
 */

namespace ShopEM\Services\Marketing;
use ShopEM\Models\Activity as ActivityModel;
use ShopEM\Models\Coupon AS CouponModel;
use ShopEM\Models\CouponStock;
use ShopEM\Models\CouponStockOnline;
use ShopEM\Models\Goods;
use ShopEM\Models\GoodsClass;
use ShopEM\Services\TradeService;
use ShopEM\Jobs\InvalidateCoupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Coupon
{
    /**
     * [invalidateCoupon 优惠券过期作废]
     * @Author mssjxzw
     * @param  [type]  $stock_id [description]
     * @return [type]            [description]
     */
    public function invalidateCoupon($stock_id)
    {
        $stock = CouponStockOnline::find($stock_id);
        if (isset($stock->status) && $stock->status == 1) {
            $stock->status = 3;
            $stock->save();

            //如果派发了线下同时作废
            if ($stock->scenes > 1) {
                $coupon_stock = CouponStock::where('coupon_code',$stock->coupon_code)->first();
                if(!empty($coupon_stock) && $coupon_stock->status == 1){
                    $coupon_stock->status = 3;
                    $coupon_stock->save();
                }
            }
        }
    }
    /**
     * [checkItem 验证商品是否在适用范围]
     * @Author mssjxzw
     * @param  [type]  $coupon_id [优惠券id]
     * @param  [type]  $item_id   [商品id]
     * @return [type]             [description]
     */
    public function checkItem($coupon_id,$item_id)
    {
        $coupon = CouponModel::find($coupon_id);
        if (!$coupon) {
            return ['code'=>3,'msg'=>'无此优惠券'];
        }
        switch ($coupon->is_bind_goods) {
            case 1:
                if (isset($coupon->limit_goods[$item_id])) {
                    return ['code'=>0,'msg'=>'适用'];
                }else{
                    return ['code'=>2,'msg'=>'不适用'];
                }
                break;
            case 2:
                if (isset($coupon->limit_goods[$item_id])) {
                    return ['code'=>2,'msg'=>'不适用'];
                }else{
                    return ['code'=>0,'msg'=>'适用'];
                }
                break;

            default:
                return ['code'=>0,'msg'=>'适用'];
                break;
        }
    }

    /**
     * [checkGoods 检查是否有生效优惠券绑定商品]
     * @Author mssjxzw
     * @param  [type]  $goods_id [description]
     * @return [type]            [description]
     */
    public function checkGoods($goods_id)
    {
        $date = date('Y-m-d H:i:s');
        $list = CouponModel::where('end_at','>=',$date)->get();
        foreach ($list as $key => $value) {
            $goods = Goods::find($goods_id);
            if ($goods->shop_id == $value->shop_id) {
                $check = $this->checkItem($value->id,$goods_id);
                if ($check['code'] == 0) {
                    return ['code'=>1,'msg'=>'该商品有绑定优惠券'];
                }
            }
        }
        return ['code'=>0 ,'msg'=>'该商品没有绑定优惠券'];
    }

    /**
     * [userfulList 获取可以用的优惠券列表]
     * @Author mssjxzw
     * @param  [type]  $params ['user_id','shop_id']
     * @param  [type]  $goods   [商品数组]
     * @return [type]           [description]
     */
    public function userfulList($params=0,$goods)
    {
        $info=[];
        $input = ['user_id'=>$params['user_id'],'status'=>1,'coupon_scenes'=>1];
        $coupons = $this->getUserCouponList($input,false);
        if (count($coupons) > 0) {
            foreach ($goods as $key => $value) {
                if (!isset($value['unusual']) || !$value['unusual']) {
                    if (isset($params['fullminus_act_enabled'])) {
                        $value['fullminus_act'] = true;
                    }
                    if (isset($params['discount_act_enabled'])) {
                        $value['discount_act'] = true;
                    }
                    //判断商品是否适用优惠券
                    foreach ($coupons as $k => $v) {
                        $check = $this->checkPromotion($value, $v['coupon_info']);
                        if ($check['code'] == 0) {
                            if ($params['shop_id'] == $v['coupon_info']['shop_id']) {
                                $check = $this->checkItem($v['coupon_info']['id'], $value['goods_id']);
                                if ($check['code'] == 0 && isInTime($v['coupon_info']->start_at, $v['coupon_info']->end_at)) {
                                    if (isset($value['deduct_money'])) {
                                        $value['goods_price'] -= $value['deduct_money']; //减去优惠金额
                                    }
                                    if (isset($info[$v['coupon_id']])) {
                                        $info[$v['coupon_id']]['price'] += $value['goods_price'] * $value['quantity'];
                                    } else {
                                        $info[$v['coupon_id']] = $v;
                                        $info[$v['coupon_id']]['price'] = $value['goods_price'] * $value['quantity'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this->userFullCouponListsToInfo($info);
    }


    /**
     * [checkShop 验证商家是否适用优惠券规则]
     * @Author mssjxzw
     * @param  [type]  $coupon_id [优惠券id]
     * @param  [type]  $shop_id   [商家id]
     * @return [type]             [description]
     */
    public function checkShop($coupon_id,$shop_id)
    {
        $coupon = CouponModel::find($coupon_id);
        if (!$coupon) {
            return ['code'=>3,'msg'=>'无此优惠券'];
        }
        if ($coupon->shop_id != 0) {
        	return ['code'=>4,'msg'=>'此优惠券不是平台发行的'];
        }
        $limit = explode(',', $coupon->limit_shop);
        switch ($coupon->is_bind_shop) {
            case 1:
                if (in_array($shop_id,$limit)) {
                    return ['code'=>0,'msg'=>'适用'];
                }else{
                    return ['code'=>2,'msg'=>'不适用'];
                }
                break;
            case 2:
                if (in_array($shop_id,$limit)) {
                    return ['code'=>2,'msg'=>'不适用'];
                }else{
                    return ['code'=>0,'msg'=>'适用'];
                }
                break;

            default:
                return ['code'=>0,'msg'=>'适用'];
                break;
        }
    }

    /**
     * [checkShop 验证分类是否适用优惠券规则]
     * @Author mssjxzw
     * @param  [type]  $coupon_id [优惠券id]
     * @param  [type]  $shop_id   [商家id]
     * @return [type]             [description]
     */
    public function checkClass($coupon_id,$class_id)
    {
        $coupon = CouponModel::find($coupon_id);
        if (!$coupon) {
            return ['code'=>3,'msg'=>'无此优惠券'];
        }
        if ($coupon->shop_id != 0) {
        	return ['code'=>4,'msg'=>'此优惠券不是平台发行的'];
        }
        $limit = explode(',', $coupon->limit_classes);
        switch ($coupon->is_bind_classes) {
            case 1:
                if (in_array($class_id,$limit)) {
                    return ['code'=>0,'msg'=>'适用'];
                }else{
                    return ['code'=>2,'msg'=>'不适用'];
                }
                break;
            case 2:
                if (in_array($class_id,$limit)) {
                    return ['code'=>2,'msg'=>'不适用'];
                }else{
                    return ['code'=>0,'msg'=>'适用'];
                }
                break;

            default:
                return ['code'=>0,'msg'=>'适用'];
                break;
        }
    }

    /**
     * [getUserCouponList 获取用户优惠券列表(分页)]
     * @Author mssjxzw
     * @param  [type]  $input [description]
     * @return [type]         [description]
     */
    public function getUserCouponList($input,$is_page = true)
    {
    	$model = new CouponStockOnline();
        $gmModel = new \ShopEM\Models\GmPlatform;
        $gmSelf = $gmModel::gmSelf();

    	$filterables = [
	        'operator' => ['field' => 'operator', 'operator' => '='],
	        'user_id' => ['field' => 'user_id', 'operator' => '='],
            'status' => ['field' => 'coupon_stock_onlines.status', 'operator' => '='],
	    ];
        $model = $model->groupBy('coupon_stock_onlines.coupon_id');
        if ($is_page) {
    	    $size = array_key_exists('size', $input)?$input['size']:10;
        	$data = filterModel($model,$filterables,$input)->leftJoin('coupons', 'coupon_stock_onlines.coupon_id', '=', 'coupons.id')->whereNotNull('coupons.id')->select('coupons.*','coupons.status as coupons_status', 'coupon_stock_onlines.*');
            if ($input['status'] == 1) {
                $data = $data->where('coupons.end_at','>=',nowTimeString());
            }
            if(isset($input['coupon_scenes']) && $input['coupon_scenes'] == 1){
                $data = $data->whereIn('scenes',[1,3]);
            }
            if (isset($input['gm_id'])) {
                $gm_ids = [$input['gm_id'],$gmSelf];
                $data = $data->whereIn('coupon_stock_onlines.gm_id',$gm_ids);
            }
            $data = $data->orderBy('coupons.end_at','desc')->paginate($size);
            CouponStockOnline::ProcessingExpiration($input['user_id']);

        }else{
            $data = filterModel($model,$filterables,$input);
            if(isset($input['coupon_scenes']) && $input['coupon_scenes'] == 1){
                $data = $data->whereIn('scenes',[1,3]);
            }
            if (isset($input['gm_id'])) {
                $gm_ids = [$input['gm_id'],$gmSelf];
                $data = $data->whereIn('coupon_stock_onlines.gm_id',$gm_ids);
            }
            $data = $data->get();
        }
    	foreach ($data as $key => &$value) {
            $value['coupon_info'] = CouponModel::find($value->coupon_id);
            $value['desc'] = $value['coupon_info']['desc'];
    	}
    	return $data;
    }

    /**
     * [checkShop 验证适用平台]
     * @Author mssjxzw
     * @param  [type]  $coupon_id    [活动id]
     * @param  [type]  $channel   [平台]
     * @return [type]             [description]
     */
    public function checkChannel($coupon_id,$channel)
    {
        $act = ActivityModel::find($coupon_id);
        if (!$act) {
            return ['code'=>3,'msg'=>'无此活动'];
        }
        if ($act->channel == 'all') {
            return ['code'=>0,'msg'=>'适用'];
        }else{
            $limit = explode(',', $act->channel);
            if (in_array($channel,$limit)) {
                return ['code'=>0,'msg'=>'适用'];
            }else{
                return ['code'=>2,'msg'=>'不适用'];
            }
        }
    }

    /**
     * [lists 优惠券领取列表]
     * @Author mssjxzw
     * @param  [type]  $request [description]
     * @return [type]           [description]
     */
    public function lists($request)
    {
        $date = date('Y-m-d H:i:s');
        $model = new CouponModel();
        $data = $model->where('status', '=', 'SUCCESS')
                        ->where('is_hand_push', 0)
                        ->where('is_distribute', 1)
                        ->where('get_star','<=',$date)
                        ->where('get_end','>=',$date)
                        ->whereColumn('rec_num', '<', 'issue_num');
        if (isset($request['shop_id'])) {
            $data = $data->where('shop_id',$request['shop_id']);
        }
        if (isset($request['class_id']) && $request['class_id']) {
            $data = $data->whereRaw('is_bind_classes = 1 and find_in_set(?, limit_classes)', [$request['class_id']]);
        }
        if (isset($request['all_platform'])) {
            $data = $data->where('is_bind_classes', 0)->where('is_bind_goods', 0)->where('is_bind_shop', 0)->where('shop_id', 0);
        }

        if ( !isset($request['coupon_scenes'])){
           $data = $data->whereIn('scenes',[1,3]);
        }else{
            if ($request['coupon_scenes'] == 2) {
                $data = $data->whereIn('scenes',[2,3]);
            }
        }
        if (isset($request['user_id'])) {
            $data = $data->whereNotIn('id', function ($query) use ($request) {
                            $query->select('coupon_id as id')
                                    ->from('coupon_stock_onlines')
                                  ->where('user_id', '=', $request['user_id']);
                        });
        }
        if (isset($request['channel'])) {
            $data = $data->where(function ($query) use ($request)  {
                $query->where('channel', '=', 'all')
                      ->orWhere('channel', '=', $request['channel']);
            });
        }
        if (isset($request['goods_id'])) {
            $goods = Goods::where('id',$request['goods_id'])->select('gc_id_3','shop_id','gm_id')->first();
            $request['gm_id'] = $goods->gm_id;

            $data = $data->where(function ($query) use ($request,$goods)  {
//                $query->where('is_bind_goods', '=', '0')
//                      ->whereRaw('shop_id = 0 or shop_id = ?',[$goods->shop_id])
                     $query->whereRaw('(is_bind_goods = 0 and (shop_id = 0 or shop_id = ?))',[$goods->shop_id])
                      ->orWhereRaw('(is_bind_goods = 1 and find_in_set(?, limit_goods))', [$request['goods_id']]);
            });

            $data = $data->where(function ($query) use ($request,$goods)  {
                $query->where('is_bind_shop', '=', '0')
                      ->orWhereRaw('(is_bind_shop = 1 and find_in_set(?, limit_shop))', [$goods->shop_id]);
            });
            $data = $data->where(function ($query) use ($request,$goods)  {
                $query->where('is_bind_classes', '=', '0')
                      ->orWhereRaw('(is_bind_classes = 1 and find_in_set(?, limit_classes))', [$goods->gc_id_3]);
            });
        }
        if (isset($request['size']) && $request['size'] < 30 && $request['size'] > 0) {
            $size = $request['size'];
        }else{
            $size = 10;
        }
        if (isset($request['gm_id'])) {
            $gmModel = new \ShopEM\Models\GmPlatform;
            $gmSelf = $gmModel::gmSelf();
            $gm_ids = [$request['gm_id'],$gmSelf];
            $data = $data->whereIn('gm_id',$gm_ids);
        }
        $data = $data->orderBy('denominations','desc')->orderBy('discount','desc')->paginate($size);
        return $data;
    }

    /**
     * 扣减掉店家优惠卷的优惠金额
     * @Author djw
     * @param $coupon_ids
     * @param $lists
     * @return mixed
     */
    public function updateCartGoodsToCoupon($coupon_ids, $lists)
    {
        if (!$lists) {
            return false;
        }
        $coupon_lists = CouponModel::whereIn('id', $coupon_ids)->get();
        //扣减掉店家优惠卷的优惠金额
        if ($coupon_lists) {
            foreach ($coupon_lists as $k => $coupon) {
                $shop_id = $coupon['shop_id'];
                if (isset($lists[$shop_id])) {
                    $denominations = $coupon['denominations'];
                    $total_fee = 0;
                    $is_coupon_goods = [];
                    foreach ($lists[$shop_id] as $key => $goods) {
                        //判断商品是否适用优惠券
                        $check = $this->checkPromotion($goods, $coupon);
                        if ($check['code'] == 0) {
                            $check = $this->checkItem($coupon['id'], $goods['goods_id']);
                            if ($check['code'] == 0 && isInTime($coupon['start_at'], $coupon['end_at'])) {
                                $total_fee += $goods['goods_price'] * $goods['quantity'];
                                $is_coupon_goods[] = $goods['goods_id'];
                            }
                        }
                    }
                    if ($total_fee >= $coupon['origin_condition']) {
                        foreach ($lists[$shop_id] as $key => $goods) {
                            if (in_array($goods['goods_id'], $is_coupon_goods)) {
                                $deduct_money = round(($lists[$shop_id][$key]['goods_price'] / $total_fee) * $denominations, 2);
                                $lists[$shop_id][$key]['deduct_money'] = $deduct_money;
                            }
                        }
                    }
                }
            }
        }
        return $lists;
    }

    /**
     * 获取用户可用的平台优惠券
     * @Author djw
     * @param $user_id
     * @param $lists
     * @return array|bool
     */
    public function getFullPlatformCoupon($user_id, $lists, $gm_id=0) {
        if (!$lists) {
            return [];
        }

        $input = ['user_id'=>$user_id,'status'=>1,'coupon_scenes'=>1];
        if ($gm_id>0) {
            $input['gm_id'] = $gm_id;
        }
        $coupons = $this->getUserCouponList($input, false);
        if (!$coupons) {
            return [];
        }
        $coupons = $coupons->toArray();
        $info = [];
        foreach ($lists as $k => $shop) {
            foreach ($shop as $key => $goods) {
                //判断商品是否适用优惠券
                foreach ($coupons as $k => $coupon) {
                    $check = $this->checkPromotion($goods, $coupon['coupon_info']);
                    if ($check['code'] == 0) {
                        $gc_id = is_object($goods['goods_info']) ? $goods['goods_info']->gc_id : $goods['goods_info']['gc_id'];
                        $check = $this->isFullPlatformCouponGoods($coupon['coupon_info']['id'], ['shop_id' => $goods['shop_id'], 'gc_id' => $gc_id, 'goods_id' => $goods['goods_id']]);
                        //计算购物车里适用与该优惠券的商品的总金额
                        if ($check) {
                            $goods_price = $goods['goods_price'];
                            if (isset($goods['deduct_money'])) {
                                $goods_price -= $goods['deduct_money']; //减去优惠金额
                            }
                            if (isset($info[$coupon['coupon_id']])) {
                                $info[$coupon['coupon_id']]['price'] += $goods_price * $goods['quantity'];
                            } else {
                                $info[$coupon['coupon_id']] = $coupon;
                                $info[$coupon['coupon_id']]['price'] = $goods_price * $goods['quantity'];
                            }
                        }
                    }
                }
            }
        }
        return $this->userFullCouponListsToInfo($info);
    }


    /**
     * 返回用户可用的优惠券
     *
     * @Author djw
     * @param $info
     * @return array
     */
    public function userFullCouponListsToInfo($info){
        $coupon_list = [];
        if (!$info) {
            return $coupon_list;
        }
        foreach ($info as $key => $value) {
            if ($value['coupon_info']['origin_condition'] <= $value['price'] && $value['coupon_info']['denominations'] <= $value['price']) {
                $info[$key]['is_use'] = true;
                if ($value['coupon_info']['type'] == 2) {
                    $info[$key]['discount_fee'] = $value['coupon_info']['discount']/100*$value['price'];
                }else{
                    $info[$key]['discount_fee'] = $value['coupon_info']['denominations'];
                }
                $info[$key]['reason'] = '';
                $coupon_list[] = $info[$key];
            }
        }
        $count = count($coupon_list)-1;
        for ($i=0; $i < $count; $i++) {
            $c = $count-$i;
            for ($j=0; $j < $c; $j++) {
                if ($coupon_list[$j]['discount_fee'] < $coupon_list[$j+1]['discount_fee']) {
                    $temp = $coupon_list[$j];
                    $coupon_list[$j] = $coupon_list[$j+1];
                    $coupon_list[$j+1] = $temp;
                }
            }
        }
        return $coupon_list;
    }

    /**
     * 判断商品是否适用于该平台券
     * @Author djw
     * @param $coupon_id
     * @param $goods
     * @return bool
     */
    public function isFullPlatformCouponGoods($coupon_id, $goods) {
        $coupon = CouponModel::find($coupon_id);
        if ($coupon && isset($goods['shop_id']) && isset($goods['gc_id']) && isset($goods['goods_id'])) {
            $check_shop = $this->checkShop($coupon_id, $goods['shop_id']);
            if ($check_shop['code'] == 0 && isInTime($coupon['start_at'],$coupon['end_at'])) {
                $check_class = $this->checkClass($coupon_id,$goods['gc_id']);
                if ($check_class['code'] == 0) {
                    $check = $this->checkItem($coupon_id,$goods['goods_id']);
                    if ($check['code'] == 0) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * 获取优惠券分类tab
     * @Author djw
     * @param $request
     * @return mixed
     */
    public function getClassTab($gm_id)
    {
        $gmModel = new \ShopEM\Models\GmPlatform;
        $gmSelf = $gmModel::gmSelf();
        $gm_ids = [$gm_id,$gmSelf];

        $date = date('Y-m-d H:i:s');
        $model = new CouponModel();
        $lists = $model->where('is_bind_classes','=',1)
            ->where('get_star','<=',$date)
            ->where('get_end','>=',$date)
            ->whereColumn('rec_num', '<', 'issue_num')
            ->whereIn('gm_id',$gm_ids)
            ->select('limit_classes')
            // ->where('scenes',1)
            ->get();
        $class_tab = [
            'all' => [
                'name' => '全部',
                'class_id' => 0,
                'all_platform' => false,
                'coupon_scenes' => 0,
            ],
            'all_platform' => [
                'name' => '平台通用',
                'class_id' => 0,
                'coupon_scenes' => 0,
                'all_platform' => true,
            ],
            'all_off' => [
                'name' => '线下通用',
                'class_id' => 0,
                'coupon_scenes' => 2,
                'all_platform' => false,
            ],
        ];
        if ($lists) {
            foreach ($lists as $k => $coupon) {
                $limit = explode(',', $coupon['limit_classes']);
                foreach ($limit as $class_id) {
                    //把分类存储到分类tab数组里
                    if (!isset($class_tab[$class_id])) {
                        $class = GoodsClass::select('gc_name')->find($class_id);
                        if ($class) {
                            $class_tab[$class_id] = [
                                'name' => $class['gc_name'],
                                'class_id' => $class_id,
                                'coupon_scenes' => 0,
                                'all_platform' => false,
                            ];
                        }
                    }
                }
            }
        }
        $class_tab = array_values($class_tab);
        return $class_tab;
    }

    /**
     * [checkShop 验证适用活动]
     * @Author mssjxzw
     * @param  [type]  $coupon_id    [活动id]
     * @param  [type]  $channel   [平台]
     * @return [type]             [description]
     */
    public function checkPromotion($goods, $coupon)
    {
        $respon = ['code'=>0,'msg'=>'适用'];
        $activityService = new Activity();
        $check = $activityService->checkPromotion($goods['goods_id']);
        if ($check['code'] == 1) {
            if (strpos($check['msg'],'积分')) {
                $respon = ['code'=>2,'msg'=>'不适用'];
            } elseif (strpos($check['msg'],'秒杀')) {
                if ($coupon['seckill_act_enabled'] == 0) {
                    $respon = ['code'=>2,'msg'=>'不适用'];
                }
            } elseif (strpos($check['msg'],'团购')) {
                if ($coupon['group_act_enabled'] == 0) {
                    $respon = ['code'=>2,'msg'=>'不适用'];
                }
            }
        }
        $date = date('Y-m-d H:i:s');
        $res = $activityService->GoodsActing($goods['goods_id']);
        if ($res) {
            foreach ($res as $act) {
                if ($act['type'] == 1) {
                    if ($coupon['fullminus_act_enabled'] == 0) {
                        $respon = ['code'=>2,'msg'=>'不适用'];
                    }
                } elseif ($act['type'] == 2) {
                    if ($coupon['discount_act_enabled'] == 0) {
                        $respon = ['code'=>2,'msg'=>'不适用'];
                    }
                }
            }
        }
        return $respon;
    }



    /**
     * 发券
     *
     * @Author Huiho
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function send($param)
    {
        DB::beginTransaction();
        try {
            $coupon = CouponModel::where('id', $param['coupon_id'])->where('is_hand_push', 1)->first();
            if (!$coupon)
            {
               throw new \LogicException('无效优惠券/手推优惠卷');
            }
            if ($coupon->issue_num <= $coupon->rec_num)
            {
                throw new \LogicException('优惠券库存已空');
            }
            if ($coupon->is_distribute <= 0)
            {
                throw new \LogicException('优惠券已下架');
            }
            if (!isInTime($coupon->get_star,$coupon->get_end))
            {
                throw new \LogicException('不在领取时间');
            }
//            $userCoupon = \ShopEM\Models\CouponStockOnline::where($param)->get();
//            if (count($userCoupon) >= $coupon->user_num)
//            {
//                throw new \LogicException('每个会员只能领取'.$coupon->user_num.'张');
//            }
            if ($coupon->issue_num > 0)
            {
                $coupon->rec_num = $coupon->rec_num + 1;
                $coupon->save();
            }
            $param['coupon_code'] = $this->getCode($param['user_id']);
            $param['coupon_fee'] = $coupon->denominations;
            $param['scenes'] = $coupon->scenes;
            $res = \ShopEM\Models\CouponStockOnline::create($param);;
            InvalidateCoupon::dispatch($res->id)->delay(now()->parse($coupon->end_at));

            //改版，线上线下可通用 nlx
            if (in_array($param['scenes'], [2,3])) {
                $head = getRandStr(4);
                $store['bn'] = $this->getBn($head,$coupon->shop_id,$coupon->gm_id);
                $store['coupon_id'] = $coupon->id;
                $store['coupon_code'] = $param['coupon_code'];
                $store['status'] = 1;
                \ShopEM\Models\CouponStock::create($store);
            }
            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            throw new \LogicException('领取失败');
        }

        return true;
    }



    /**
     * [getCode 获取优惠券唯一码]
     * @Author mssjxzw
     * @param  [type]  $user_id [description]
     * @return [type]           [description]
     */
    private function getCode($user_id)
    {
        $u = 'U'.$user_id;
        $length = strlen($u);
        $limit = 5;
        if ($length < $limit) {
            $u .= getRandStr($limit-$length);
            $length = $limit;
        }
        $res[] = getRandStr($length);
        $res[] = $u;
        $res[] = getRandStr($length-4).date('is');
        $res[] = getRandStr($length);
        return implode('-',$res);
    }


    /**
     * [getBn 获取线下优惠券唯一码]
     * @Author mssjxzw
     * @param  [type]  $head [description]
     * @return [type]        [description]
     */
    public function getBn($head,$shop_id,$gm_id)
    {
        $date = date('Ymd');
        $cache_key = 'coupon_num_'.$shop_id.$gm_id.'_'.$date;
        $cache_day = Carbon::now()->addDay(1);
        $num = Cache::remember($cache_key, $cache_day, function () {
            return 0;
        });
        $num = $num+1;
        Cache::put($cache_key, $num, $cache_day);

        $str = $head.date('Y').$shop_id.$gm_id.$num.date('md');
        return strtoupper($str);
    }

}
