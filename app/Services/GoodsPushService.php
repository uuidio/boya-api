<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-07-20 15:42:56
 * @version 	V1.0
 */
namespace ShopEM\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ShopEM\Models\TradeRelation;
use ShopEM\Models\SourceConfig;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\Trade;

class GoodsPushService
{
	public function __get($name)
	{
		$obj = '\\ShopEM\\Services\\'.$name;
		return new $obj();
	}

	public function putFilter($trade)
	{
		foreach ($trade as $key => $value) {
			$order = TradeOrder::where('tid',$value->tid)->get();
			$order_3rd = [];
			foreach ($order as $k => $v) {
				if ($v->source != 'self') {
					$order_3rd[$v->source][] = $v;
				}
			}
			if (count($order_3rd) > 0) {
				$sign = 0;
				foreach ($order_3rd as $k => $v) {
					$config = SourceConfig::where('source',$k)->first();
					if (!$config) {
						Log::error($k.'：该来源没有配置(第三方推单)|\\ShopEM\\Services\\GoodsPushService:42');
						return false;
					}
					$ser = $config->service;
					$act = $config->act;
					$this->$ser->$act($value,$k,$v);
					if ($k !== 'beeassistant' && $k !== 'cms') {
						$sign = 1;
					}
				}
				if ($sign == 1) {
					$status = 'WAIT_BUYER_CONFIRM_GOODS';
				}else{
					$status = 'TRADE_FINISHED';
				}
				$relation = TradeRelation::where('tid',$value->tid)->get();
				$s = 1;
				$err = [];
				foreach ($relation as $k => $v) {
					if ($v->status == 2) {
						$s = 0;
						$err[] = $v->rid.':'.$v->error;
					}
				}
				if ($s) {
					Trade::where('tid',$value->tid)->update(['status'=>$status]);
					TradeOrder::where('tid',$value->tid)->update(['status'=>$status]);
				}else{
					Trade::where('tid',$value->tid)->update(['shop_memo'=>implode(',', $err)]);
				}
			}else{
				//特殊id集合，推送CMS的商品id
	            $beeassistant_arr = [370123];
				if (in_array($order[0]->goods_id, $beeassistant_arr)) {
					$this->CmsPushService->currencyPay($value,$order[0]->source,$order);
					$this->LhyPushService->pushBeeassistantTrade($value,$order[0]->source,$order);
					$relation = TradeRelation::where('tid',$value->tid)->get();
					$s = 1;
					foreach ($relation as $k => $v) {
						if ($v->status == 2) {
							$s = 0;
							$err[] = $v->rid.':'.$v->error;
						}
					}
					if ($s) {
						Trade::where('tid',$value->tid)->update(['status'=>'TRADE_FINISHED']);
						TradeOrder::where('tid',$value->tid)->update(['status'=>'TRADE_FINISHED']);
					}else{
						Trade::where('tid',$value->tid)->update(['shop_memo'=>implode(',', $err)]);
					}
				}
			}
		}
	}
}