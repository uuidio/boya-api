<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-03-20 09:36:17
 * @version 	V1.0
 */

namespace ShopEM\Services\Marketing;
use ShopEM\Models\Activity AS ActivityModel;
use ShopEM\Models\ActivityGoods;
use ShopEM\Models\PointActivityGoods;
use ShopEM\Services\GroupService;
use ShopEM\Services\SecKillService;

class Activity
{
    /**
     * [checkItem 验证商品是否适用活动规则]
     * @Author mssjxzw
     * @param  [type]  $act_id	  [活动id]
     * @param  [type]  $item_id   [商品id]
     * @return [type]             [description]
     */
    public function checkItem($act_id,$item_id)
    {
        $act = ActivityModel::find($act_id);
        if (!$act) {
            return ['code'=>3,'msg'=>'无此活动'];
        }
        $items = ActivityGoods::where('act_id',$act_id)->get()->keyBy('goods_id')->toArray();
        switch ($act['is_bind_goods']) {
        	case 1:
        		if (array_key_exists($item_id,$items)) {
                    return ['code'=>0,'msg'=>'适用'];
                }else{
                    return ['code'=>2,'msg'=>'不适用'];
                }
        		break;
        	case 2:
        		if (array_key_exists($item_id,$items)) {
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
     * [checkGoods 检查是否有生效活动绑定商品]
     * @Author mssjxzw
     * @param  [type]  $goods_id [description]
     * @return [type]            [description]
     */
    public function checkGoods($goods_id, $is_act=false)
    {
        $check = $this->checkPromotion($goods_id);
        if ($check['code'] != 0) {
            return $check;
        }
        if ($is_act == false) {
            $date = date('Y-m-d H:i:s');
            $list = ActivityModel::where('end_time','>=',$date)->whereIn('status', [0, 1, 2])->get();
            if ($list) {
                foreach ($list as $key => $value) {
                    $check = $this->checkItem($value->id,$goods_id);
                    if ($check['code'] == 0) {
                        return ['code'=>1,'msg'=>'该商品已参与店铺满减满折活动'];
                    }
                }
            }
        }
        return ['code'=>0 ,'msg'=>'该商品没有绑定活动'];
    }

    /**
     * [checkShop 验证商家是否适用活动规则]
     * @Author mssjxzw
     * @param  [type]  $act_id    [活动id]
     * @param  [type]  $shop_id   [商家id]
     * @return [type]             [description]
     */
    public function checkShop($act_id,$shop_id)
    {
        $act = ActivityModel::find($act_id);
        if (!$act) {
            return ['code'=>3,'msg'=>'无此活动'];
        }
        if ($act->shop_id != 0) {
        	return ['code'=>4,'msg'=>'此优惠券不是平台发行的'];
        }
        $limit = explode(',', $act->limit_shop);
        switch ($act->is_bind_shop) {
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
     * [checkShop 验证会员资格]
     * @Author mssjxzw
     * @param  [type]  $act_id    [活动id]
     * @param  [type]  $level     [会员等级]
     * @return [type]             [description]
     */
    public function checkUser($act_id,$level)
    {
        $act = ActivityModel::find($act_id);
        if (!$act) {
            return ['code'=>3,'msg'=>'无此活动'];
        }
        if ($act->user_type == 'all') {
        	return ['code'=>0,'msg'=>'适用'];
        }else{
	        $limit = explode(',', $act->user_type);
	        if (in_array($level,$limit)) {
	            return ['code'=>0,'msg'=>'适用'];
	        }else{
	            return ['code'=>2,'msg'=>'不适用'];
	        }
        }
    }

    /**
     * [checkShop 验证适用平台]
     * @Author mssjxzw
     * @param  [type]  $act_id    [活动id]
     * @param  [type]  $channel   [平台]
     * @return [type]             [description]
     */
    public function checkChannel($act_id,$channel)
    {
        $act = ActivityModel::find($act_id);
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
     * [checkActGoods 验证活动商品]
     * @Author mssjxzw
     * @param  [type]  $shop_id  [商家id]
     * @param  [type]  $goods_id [商品id]
     * @return [type]            [description]
     */
    public function checkActGoods($shop_id,$goods_id)
    {
        $ret = [
            'act_id'=>0,
            'goods'=>null,
        ];
        $where = [
            'shop_id'=>$shop_id,
            'status'=>2,
        ];
        $act = ActivityModel::where($where)->get();
        foreach ($act as $key => $value) {
            $check = $this->checkItem($value['id'],$goods_id);
            if ($check['code'] == 0) {
                $ret['act_id'] = $value['id'];
                $ret['goods'] = ActivityGoods::where(['act_id'=>$value['id'],'goods_id'=>$goods_id])->first();
                break;
            }
        }
        return $ret;
    }

    /**
     * [userfulList 获取可以用的活动列表]
     * @Author mssjxzw
     * @param  [type]  $params ['user_id','shop_id']
     * @param  [type]  $goods   [商品数组]
     * @return [type]           [description]
     */
    public function userfulList($params=0,$goods)
    {
        $info=[];
        $date = date('Y-m-d H:i:s');
        $model = ActivityModel::where('star_time','<=',$date)->where('end_time','>=',$date)->whereIn('status', [1, 2]);
        if (isset($params['shop_id'])) {
            $model->where('shop_id', $params['shop_id']);
        }
        if (isset($params['act_ids'])) {
            $model->whereIn('id', $params['act_ids']);
        }
        $list = $model->get();
        if ($list) {
            $list = $list->toArray();
            foreach ($goods as $key => $value) {
                if (!isset($value['unusual']) || !$value['unusual']) {
                    foreach ($list as $k => $activity) {
                        $check = $this->checkItem($activity['id'], $value['goods_id']);
                        if ($check['code'] == 0) {
                            if (!isset($info[$activity['id']])) {
                                $info[$activity['id']] = $activity;
                            }
                            $info[$activity['id']]['goods_ids'][] = $value['goods_id'];
                            if (isset($info[$activity['id']]['price'])) {
                                $info[$activity['id']]['price'] += $value['goods_price'] * $value['quantity'];
                            } else {
                                $info[$activity['id']]['price'] = $value['goods_price'] * $value['quantity'];
                            }
                        }
                    }
                }
            }
        }
        return $this->userFullActivityListsToInfo($info);
    }

    /**
     * 返回用户可用的活动
     *
     * @Author djw
     * @param $info
     * @return array
     */
    public function userFullActivityListsToInfo($info){
        $list = [];
        if (!$info) {
            return $list;
        }
        foreach ($info as $key => $activity) {
            $info[$key]['discount_fee'] = $discount_fee = $this->getActDiscountPrice($activity['price'], $activity['id']);
            if ($discount_fee > 0) {
                $list[] = $info[$key];
            }
        }
        $count = count($list)-1;
        for ($i=0; $i < $count; $i++) {
            $c = $count-$i;
            for ($j=0; $j < $c; $j++) {
                if ($list[$j]['discount_fee'] < $list[$j+1]['discount_fee']) {
                    $temp = $list[$j];
                    $list[$j] = $list[$j+1];
                    $list[$j+1] = $temp;
                }
            }
        }
        return $list;
    }

    /**
     *  获得活动的减免金额
     *
     * @Author djw
     * @param $total_price
     * @param $act_id
     * @return int
     */
    public function getActDiscountPrice($total_price, $act_id) {
        $act = ActivityModel::find($act_id);
        $discount_fee = 0;
        if ($act) {
            if ($act['type'] == 1) {
                $discount_fee = $this->getFullminusPrice($total_price, $act);
            } elseif ($act['type'] == 2) {
                $discount_fee = $this->getDiscountPrice($total_price, $act);
            }
        }
        return $discount_fee;
    }

    /**
     *  获得满折活动的减免金额
     *
     * @Author djw
     * @param $total_price
     * @param $act
     * @return int
     */
    public function getDiscountPrice($total_price, $act) {
        $ruleArray = $act['rule'];
        $ruleLength = count($ruleArray);
        $discount_price = 0;

        if( $total_price >=$ruleArray[$ruleLength-1]['condition'] )
        {
            $rulePercent = max(0, $ruleArray[$ruleLength-1]['num']);
            $rulePercent = min($rulePercent, 100);
            $discount_price = round($total_price * (1-$rulePercent/100), 2);
        }
        elseif( $total_price < $ruleArray[0]['condition'] )
        {
            $discount_price = 0;
        }
        else
        {
            for($i=0; $i<$ruleLength-1; $i++)
            {
                if( $total_price>=$ruleArray[$i]['condition'] && $total_price<$ruleArray[$i+1]['condition'] )
                {
                    $rulePercent = max(0, $ruleArray[$i]['num']);
                    $rulePercent = min($rulePercent, 100);
                    $discount_price = round($total_price * (1-$rulePercent/100), 2);
                    break;
                }
            }
        }
        if($discount_price<0)
        {
            $discount_price = 0;
        }
        return $discount_price;
    }

    /**
     *  获得满减活动的减免金额
     *
     * @Author djw
     * @param $total_price
     * @param $act
     * @return int
     */
    public function getFullminusPrice($total_price, $act) {
        $ruleArray = $act['rule'];
        $ruleLength = count($ruleArray);
        $discount_price = 0;

        if( $total_price >=$ruleArray[$ruleLength-1]['condition'] )
        {
            $discount_price = $ruleArray[$ruleLength-1]['num'];
        }
        elseif( $total_price < $ruleArray[0]['condition'] )
        {
            $discount_price = 0;
        }
        else
        {
            for($i=0; $i<$ruleLength-1; $i++)
            {
                if( $total_price>=$ruleArray[$i]['condition'] && $total_price<$ruleArray[$i+1]['condition'] )
                {
                    $discount_price = $ruleArray[$i]['num'];
                }
            }
        }
        if($discount_price<0)
        {
            $discount_price = 0;
        }
        return $discount_price;
    }

    /**
     * 扣减掉活动的优惠金额
     * @Author djw
     * @param $lists
     * @return mixed
     */
    public function updateCartGoodsToAct($lists, $act_ids = []) {
        if (!$lists) {
            return [];
        }
        if ($act_ids) {
            $params['act_ids'] = $act_ids;
        }
        foreach ($lists as $shop_id => $shop) {
            $params['shop_id'] = $shop_id;
            $act_lists = $this->userfulList($params, $shop);
            foreach ($shop as $key => $goods) {
                foreach ($act_lists as $act) {
                    if (in_array($goods['goods_id'], $act['goods_ids'])) {
                        $deduct_money = round(($lists[$shop_id][$key]['goods_price'] / $act['price']) * $act['discount_fee'], 2);
                        $lists[$shop_id][$key]['deduct_money'] = $deduct_money;
                    }
                }
            }
        }
        return $lists;
    }

    /**
     * 判断商品是否已有绑定促销活动
     *
     * @Author djw
     * @param $goods
     * @return array
     */
    public function checkPromotion($item_id) {
        $point_goods = PointActivityGoods::where('goods_id', $item_id)->first();
        if ($point_goods) {
            return ['code'=>1,'msg'=>'该商品已在积分专区中'];
        }

        $secKillService = new SecKillService();
        if ($secKillService->actingSecKill($item_id)) {
            return ['code'=>1,'msg'=>'该商品参加了秒杀活动'];
        }
        $groupService = new GroupService();
        if ($groupService->actingGroup($item_id)) {
            return ['code'=>1,'msg'=>'该商品参加了团购活动'];
        }
        return ['code'=>0,'msg'=>'适用'];
    }


    /**
     * 判断当前商品是否正在参加活动
     *
     * @Author djw
     * @param $goods_id
     * @return mixed
     */
    public function actingAct($goods_id) {
        $date = date('Y-m-d H:i:s');
        $res = ActivityModel::leftJoin('activity_goods', 'activity_goods.act_id', '=', 'activities.id')
            ->where('end_time','>=',$date)
            ->whereIn('status', [0, 1, 2])
            ->where('goods_id', $goods_id)
            ->count();
        return $res;
    }

    /**
     * 获取当前商品是否正在参加的活动
     *
     * @Author djw
     * @param $goods_id
     * @return array
     */
    public function GoodsActing($goods_id) {
        $date = date('Y-m-d H:i:s');
        $res = ActivityModel::select('activities.*')
            ->leftJoin('activity_goods', 'activity_goods.act_id', '=', 'activities.id')
            ->where('star_time','<=',$date)
            ->where('end_time','>=',$date)
            ->whereIn('status', [1, 2])
            ->where('goods_id', $goods_id)
            ->get();
        return $res;
    }


    /**
     * 查询商品的参与的活动和促销信息
     * @Author swl
     * @param  [type]  $goods_id [description]
     * @return [type]            [description]
     */
    public function getGoodInfo($goods_id)
    {
        // 拼团、秒杀
        $activity = $this->getActivityInfo($goods_id);
        
        $date = date('Y-m-d H:i:s');
        $list = ActivityModel::select('id')->where('end_time','>=',$date)->where('status', 2)->whereIn('type',[1,2])->get();

        // 满减满折
        $promotion = [];
        $mjmz = $this->GoodsActing($goods_id);
        if(!empty($mjmz)){
            $mjmz = $mjmz->toArray();
            foreach ($mjmz as $key => $value) {
                $promotion[] = $this->mjmzFormat($value); 
            }
        }
        // 积分抵扣
        $point_goods = PointActivityGoods::where('goods_id', $goods_id)->exists();
        if ($point_goods) {
            $promotion[] = '积分抵扣';
        }

        $info['activity'] = $activity;//活动(拼团、秒杀)
        $info['promotion'] = $promotion;//促销(满减满折、积分抵扣)
        return $info;
    }


     /**
     * 获取当前商品正在参加的活动，返回对应的信息
     *
     * @Author swl
     * @param $goods_id
     * @return array
     */
      public function getActivityInfo($good_id) {
        $activity_info = [];

        $secKillService = new SecKillService();
        if ($secKillService->actingSecKill($good_id)) {
            $activity_info[] = '秒杀';
        }
        $groupService = new GroupService();
        if ($groupService->actingGroup($good_id)) {
            $activity_info[] = '拼团';

        }
        return $activity_info;
    }


     /**
     * [获取商品使用的促销规则]
     * @Author swl
     * @param  [type]  $act_id    [活动id]
     * @param  [type]  $item_id   [商品id]
     * @return [type]             [description]
     */
    public function getPromotionInfo($act_id,$item_id)
    {

        $act = ActivityModel::find($act_id)->toArray();
        $items = ActivityGoods::where('act_id',$act_id)->get()->keyBy('goods_id')->toArray();
        $promotion = false;
        // 具体活动规则
        if($act['type'] == 1){
            $rule = '满'.$act['rule'][0]['condition'].'减'.$act['rule'][0]['num'];
        }else{
            $rule = '满'.$act['rule'][0]['condition'].'打'.$act['rule'][0]['num'].'折';
        }
        switch ($act['is_bind_goods']) {
            case 1:
                if (array_key_exists($item_id,$items)) {
                    // $promotion = $act['type_text'] ; 
                    $promotion =  $rule;
                }
                break;
            case 2:
                if (!array_key_exists($item_id,$items)) {
                    $promotion =  $rule;
                }
                break;
            
            default:
                $promotion =  $rule;
                break;
        }
        return $promotion;
    }

    public function mjmzFormat($data){
        if($data['type'] == 1){
             $rule = '满减';
             // $rule = '满'.$data['rule'][0]['condition'].'减'.$data['rule'][0]['num'];
        }else{
            $rule = '满折';
            // $rule = '满'.$data['rule'][0]['condition'].'打'.$data['rule'][0]['num'].'折';
        }
        return $rule;
    }

}