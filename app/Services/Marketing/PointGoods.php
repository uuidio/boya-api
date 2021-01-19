<?php

/**
 * @Author: nlx
 * @Date:   2020-04-21 15:01:35
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-05-16 12:31:04
 */
namespace ShopEM\Services\Marketing;

use ShopEM\Models\TradeOrder;
use ShopEM\Models\PointActivityGoods;
use ShopEM\Models\UserRelYitianInfo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PointGoods
{
	
	/**
	 * [check 积分商品检验]
	 * @param  string $user_id [description]
	 * @param  string $active_id [description]
	 * @param  string $quantity [购买的数量]
	 * @return [type]        [description]
	 */
	public function buyCheck($user_id, $activity, $quantity)
	{
		$activity_sign = $activity->id;
		$active = PointActivityGoods::getActiveStatus($activity);
		if (isset($active['status']) && $active['status'] !== 1) {
			return ['code'=>1,'msg'=> '未在活动时间内兑换'];
		}
		// // 本次活动兑换次数已上限
		// if (!empty($activity->active_start) && !empty($activity->active_end)) 
		// {
		// 	$active_start = $activity->active_start;
		// 	$active_end = $activity->active_end;
		// 	$activity_buy_count =  TradeOrder::countActivityPointGoodsBuy($user_id,$activity_sign,$active_start,$active_end);
		// 	if ( $activity_buy_count + $quantity > $activity->activity_buy_max ) {
	            // return ['code'=>1,'msg'=> '活动兑换次数已上限'];
	        // }
		// }
		
		//查询会员等级限制
		if ($activity->is_grade_limit > 0) 
		{
	        $card_code = UserRelYitianInfo::where('user_id' , $user_id)->where('gm_id' , $activity->gm_id)->value('card_type_code');
	        //无会员等级会员无法参与
	        if(!$card_code)
	        {
	        	return ['code'=>1,'msg'=> '您会员等级无兑换资格'];
	        }
	        if ( !empty($activity->grade_limit) && is_array($activity->grade_limit) && !in_array($card_code , $activity->grade_limit) ) 
	        {
	        	return ['code'=>1,'msg'=> '您会员等级无兑换资格!'];
	        }
		}

		$day_buy_count =  TradeOrder::countDayPointGoodsBuy($user_id,$activity_sign);
		if ( $day_buy_count + $quantity > $activity->day_buy_max ) {
            return ['code'=>1,'msg'=> '今日份额已抢完'];
        }

		$buy_point_count = TradeOrder::countUserPointGoodsBuy($user_id,$activity_sign);
		if ( $buy_point_count + $quantity > $activity->buy_max ) {
            return ['code'=>1,'msg'=> '该活动商品每人限购'.$activity->buy_max. '件'];
        }

        return ['code'=>0,'msg'=>'可购买'];
	}


	//避免超卖  暂时弃用
	public function avoidOversell($activity,$quantity)
	{
		$id = $activity->id;
		$tomorrow = Carbon::tomorrow();
		$seconds = Carbon::addSeconds(20);

		$cache_point_day = 'point_goods_day_max_id_'.$id;
		$cache_point_day_buy = 'point_goods_day_buy_max_id_'.$id;
		if (Cache::has($cache_point_day)) {
			$day_buy_max = Cache::get($cache_point_day);
		}else{
			$day_buy_max = $activity->day_buy_max;
			Cache::put($cache_point_day,$day_buy_max,$tomorrow);
		}
		if (Cache::has($cache_point_day_buy)) {
			$buy_num = Cache::get($cache_point_day_buy) + $quantity;
			Cache::increment($cache_point_day_buy, $quantity);
		}else{
			Cache::put($cache_point_day_buy,$quantity,$seconds);
		}
		

	}
}