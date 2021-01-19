<?php

/**
 * 微信服务通知
 * WechatMessage.php
 * @Author: nlx
 * @Date:   2020-05-29 09:28:16
 */
namespace ShopEM\Models;
use ShopEM\Services\WeChatMini\WXMessage;

class WechatMessage
{
	
	//发货通知
	public function shipMessage($tid)
	{
		$delivery = LogisticsDelivery::where('tid', $tid)->select('user_id','tid','logi_no','logi_name','delivery_id')->first();
        $details = LogisticsDeliveryDetail::where('delivery_id',$delivery->delivery_id)->select('sku_title')->get()->toArray();
        $user = UserAccount::where('id',$delivery->user_id)->select('openid')->first();
        $pay_time = Trade::where('tid',$tid)->value('pay_time');
        $goods_name = str_replace(" ",'',implode(',', array_column($details,'sku_title')));
        if(mb_strlen($goods_name, 'utf8') > 10) {
            $goods_name =  mb_substr($goods_name, 0,10,'utf8').'...';
        }
        if (!$user || empty($user->openid)) {
            return false;
        }
        (new WXMessage())->shipMessage([
            'goods_name'    => $goods_name,
            'tid'           => $delivery->tid,
            'logistics_company' => $delivery->logi_name,
            'logistics_id'  => $delivery->logi_no,
            'time'  => !empty($pay_time) ? date('Y-m-d H:i:s') : $pay_time,
        ],$user->openid);
	}


    /**
     * [pointChangeMessage 积分变化通知]
     * @param  [type] $mobile [description]
     * @param  [type] $change [description]
     * @param  [type] $point  [description]
     * @param  [type] $time  [description]
     * @param  [type] $reason [description]
     * @return [type]         [description]
     */
    public function pointChangeMessage($data)
    {
        extract($data);
        $user = UserAccount::where('mobile',$mobile)->select('openid')->first();
        if (!$user || empty($user->openid)) {
            return false;
        }
        $reason = str_replace(" ",'',$reason);
        if(mb_strlen($reason, 'utf8') > 10) {
            $reason =  mb_substr($reason, 0,10,'utf8').'...';
        }
        (new WXMessage())->pointChangeMessage([
            'username'  => $gm_name??$mobile,
            'change'    => $change,
            'point'     => $point,
            'time'      => $time,
            'reason'    => $reason,
        ],$user->openid);
    }
}