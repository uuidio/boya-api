<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-08-01 11:07:43
 * @version 	V1.0
 */
namespace ShopEM\Services;

use ShopEM\Models\TradeOrder;
use ShopEM\Models\BuyLimit;
use ShopEM\Models\UserBuyLimitLog;
use ShopEM\Services\Marketing\Coupon;
class LimitBuyService
{
    /**
     * [checkOrderLimitBuy 检查子订单商品限购]
     * @Author mssjxzw
     * @param  [type]  $tid [description]
     * @return [type]       [description]
     */
    public function checkOrderLimitBuy($tid)
    {
        $orderList = TradeOrder::where('tid',$tid)->get();
        if (count($orderList) == 0) {
        	return ['code'=>1,'无效订单参数'];
        }
        foreach ($orderList as $key => $value) {
        	$res = $this->checkGoodsLimitBuy($value->sku_id,$value->user_id,$value->quantity);
        	if ($res['code']) {
        		$res['msg'] = '活动商品限购'.$res['limit'].'件/'.$res['type'];
                $res['goods_name'] = $value->goods_name;
        		return $res;
        	}
        }
        return ['code'=>0,'msg'=>''];
    }

    /**
     * [checkGoodsLimitBuy 检查商品限购]
     * @Author mssjxzw
     * @param  [type]  $skuid   [description]
     * @param  [type]  $user_id [description]
     * @return [type]           [description]
     */
    public function checkGoodsLimitBuy($skuid,$user_id,$num)
    {
    	$log = UserBuyLimitLog::where([['sku_id','=',$skuid],['user_id','=',$user_id]])->first();
    	if (!$log) {
            $limit = BuyLimit::where('sku_id',$skuid)->first();
            if (!$limit) {
                return ['code'=>0,'msg'=>''];
            }
            if ($num <= $limit->limit) {
                return ['code'=>0,'msg'=>''];
            }else{
                switch ($limit->cycle_type) {
                    case 1:
                        $type = '天';
                        break;
                    case 2:
                        $type = '周';
                        break;
                    case 3:
                        $type = '月';
                        break;
                    default:
                        $type = '天';
                        break;
                }
                return ['code'=>1,'type'=>$type,'limit'=>$limit->limit];
            }
    	}
    	if ($log->number + $num <= $log->limit) {
    		return ['code'=>0,'type'=>'','limit'=>0];
    	}else{
    		switch ($log->limit_info->cycle_type) {
    			case 1:
    				$type = '天';
    				break;
    			case 2:
    				$type = '周';
    				break;
    			case 3:
    				$type = '月';
    				break;
    			default:
    				$type = '天';
    				break;
    		}
    		return ['code'=>1,'type'=>$type,'limit'=>$log->limit];
    	}
    }

    /**
     * [saveLog 保存用户限购数据]
     * @Author mssjxzw
     * @param  [type]  $skuid   [description]
     * @param  [type]  $user_id [description]
     * @param  [type]  $num     [description]
     * @return [type]           [description]
     */
    public function saveLog($skuid,$user_id,$num)
    {
    	$log = UserBuyLimitLog::where([['sku_id','=',$skuid],['user_id','=',$user_id]])->first();
    	try {
	    	if ($log) {
	    		$log->number = $log->number + (int)$num;
	    		$log->save();
	    	}else{
	    		$limit = BuyLimit::where('sku_id',$skuid)->first();
	    		if ($limit) {
		    		UserBuyLimitLog::create([
		    			'user_id'=>$user_id,
		    			'sku_id'=>$skuid,
		    			'limit_id'=>$limit->id,
                        'number'=>1,
		    			'limit'=>$limit->limit,
		    		]);
	    		}
	    	}
    	} catch (Exception $e) {
    		return ['code'=>1,'msg'=>'保存失败'];
    	}
    	return ['code'=>0,'msg'=>'保存成功'];
    }

    /**
     * [reset 限购重置]
     * @Author mssjxzw
     * @param  [type]  $cycle_type [description]
     * @return [type]              [description]
     */
    public function reset($cycle_type)
    {
        $list = BuyLimit::where('cycle_type',$cycle_type)->get();
        foreach ($list as $key => $value) {
            $log = UserBuyLimitLog::where('limit_id',$value->id)->count();
            if ($log) {
                UserBuyLimitLog::where('limit_id',$value->id)->update(['number'=>0]);
            }
        }
    }
}