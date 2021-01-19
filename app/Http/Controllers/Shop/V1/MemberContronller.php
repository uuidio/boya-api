<?php

/**
 * MemberContronller.php
 * @Author: nlx
 * @Date:   2020-03-26 10:53:50
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-06-23 16:57:06
 */
namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\ExchangeSelfPointRequest;
use ShopEM\Models\UserCowCoinLog;
use ShopEM\Models\UserPointLog;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\UserRelYitianInfo;
use ShopEM\Repositories\UserPointLogsRepository;
use ShopEM\Services\YitianGroupServices;
use ShopEM\Jobs\UpdateCrmUserInfo;


class MemberContronller extends BaseController
{
    /**
     * [refreshPoint 刷新当前项目积分]
     * @param Request $request [description]
     */
    public function refreshPoint(Request $request)
    {
        $mobile = $this->user->mobile;
        if (!isset($request->gm_id)) 
        {
            return $this->resFailed(414,'参数错误');
        }
        $gm_id = $request->gm_id;
        $service = new \ShopEM\Services\YitianGroupServices($gm_id);
        $gmData = $service->getPlatform($gm_id);
        if (empty($gmData)) {
            return $this->resFailed(414,'该项目未开启积分功能');
        }
        $yiitan = UserRelYitianInfo::where('mobile',$mobile)->where('gm_id',$gm_id)->select('yitian_card_id','user_id')->first();
        if ($yiitan && empty($yiitan->yitian_card_id)) {
            $service->updateCardTypeCode($yiitan->user_id,$mobile);
        }

        $cache_key = 'YITIAN_POINT_USER_MOBILE_'.$mobile.'_GMID_'.$gm_id;
        Cache::forget($cache_key);
        $point = $service->updateUserRewardTotal($mobile);

        $data['point'] = intval($point);
        return $this->resSuccess($data);
    }
    /**
     * [refreshSelfPoint 刷新牛币]
     * @return [type] [description]
     */
    public function refreshSelfPoint()
    {
        $mobile = $this->user->mobile;

        $gm_id = GmPlatform::gmSelf();
        $service = new \ShopEM\Services\YitianGroupServices($gm_id);
        $gmData = $service->getPlatform($gm_id);
        if (empty($gmData)) {
            return $this->resFailed(414,'牛币功能建设中');
        }

        $point = 0; 
        $yiitan = UserRelYitianInfo::where('mobile',$mobile)->where('gm_id',$gm_id)->select('yitian_card_id','user_id')->first();
        if ($yiitan && !empty($yiitan->yitian_card_id)) 
        {
            $cache_key = 'YITIAN_POINT_USER_MOBILE_'.$mobile.'_GMID_'.$gm_id;
            Cache::forget($cache_key);
            $point = $service->updateUserRewardTotal($mobile);
        }
        else
        {
            $point = 0;
            try {
                
                $create = $service->createRelYitian($mobile,$this->user->id,true);
                $service->updateCardTypeCode($this->user->id,$mobile);

            } catch (Exception $e) {
                return $this->resFailed(414,'获取牛币值失败，请刷新');
            }
        }

        $data['point'] = intval($point);
        return $this->resSuccess($data);
    }

    /**
     * [exchangeSelfPoint 兑换牛币]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function exchangeSelfPoint(ExchangeSelfPointRequest $request)
    {
        $data = $request->only('source_point','add_point','current_gm_id');
        $user_id = $this->user->id;
        $cache_key = 'exchangeselfpoint_request_user_id_'.$user_id;
        if (Cache::has($cache_key)) 
        {
            return $this->resFailed(701,'请勿频繁请求');
        }
        Cache::forever($cache_key,1);

        $current_gm_id = $data['current_gm_id'];
        $self_gm_id = GmPlatform::gmSelf();
        if (empty($self_gm_id)) {
            return $this->resFailed(414,'牛币功能建设中');
        }

        $current_gm = GmPlatform::where('gm_id',$current_gm_id)->where('open_point_exchange',1)->first();
        if (!$current_gm) {
            return $this->resFailed(414,'该项目未开启积分功能');
        }
        $yitiangroup_service = new \ShopEM\Services\YitianGroupServices($current_gm_id);
        $current_point = $yitiangroup_service->updateUserRewardTotal($this->user->mobile);

        if ($current_point < $data['source_point']) {
            return $this->resFailed(414,'当前可使用'.$current_point.'积分');
        }

        $scale = $current_gm->use_obtain_point['scale'];
        $use_point = $data['source_point']*$scale;
        if ($use_point != $data['add_point']) {
            return $this->resFailed(414,'兑换比例错误');
        }

        $cardIds = UserRelYitianInfo::whereIn('gm_id',[$current_gm_id,$self_gm_id])->where('user_id',$user_id)->get()->toArray();
        $userCardIds = resetKey($cardIds, 'gm_id');
        
        if (!isset($userCardIds[$self_gm_id])) {
            try {
                
                $create = $yitiangroup_service->createRelYitian($this->user->mobile,$this->user->id);
                self::updateCrmUser($this->user,$self_gm_id);

            } catch (Exception $e) {
                return $this->resFailed(414,'网络出错，请重试');
            }
            return $this->resFailed(701,'请重新获取积分');
        }
        $sourceCardId = $userCardIds[$current_gm_id]['yitian_card_id'];
        $targetCardId = $userCardIds[$self_gm_id]['yitian_card_id'];
        $data['remarks'] = $remarks = $current_gm->platform_name.'项目转移'.$data['source_point'].'积分至广场项目，转换比率为'.$scale;
        try {
            $log_id = $this->takeExchangeLog($data,$user_id);
            $CowCoinLog_id = $this->pointToCowCoinLog($data,$scale,$current_point);
            $postData = [
                'sourceCardId' => $sourceCardId,
                'targetCardId' => $targetCardId,
                'points' => $data['source_point'],
                'scale' => $current_gm->use_obtain_point['scale'],
                'remarks' => $remarks,
            ];
            $result = $yitiangroup_service->pointsTransfer($postData);
            if (!$result) {
                throw new \Exception('兑换接口网络出错');
            }
            //新增牛币兑换记录
            $this->addExchangeLog($data,$user_id);

            UserPointLog::where('id',$log_id)->update(['push_crm'=> 2 ,'log_type' => 'exchange']);
            UserCowCoinLog::where('id',$CowCoinLog_id)->update(['push_crm' => 2]);
            Cache::forget($cache_key);

        } catch (\Exception $e) {

            UserPointLog::where('id',$log_id)->update(['push_crm' => 3,'log_type' => 'exchange','crm_msg' => $e->getMessage()]);
            UserCowCoinLog::where('id',$CowCoinLog_id)->update(['push_crm' => 3 ,'crm_msg' => $e->getMessage()]);
            Cache::forget($cache_key);
            
            return $this->resFailed(600);
        }
        return $this->resSuccess();
    }
    /**
     * [takeExchangeLog 记录兑换牛币记录]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function takeExchangeLog($data,$user_id)
    {
        $gm_id = $data['current_gm_id'];
        $yitiangroup_service = new \ShopEM\Services\YitianGroupServices($gm_id);

        $modify_point = 0 - $data['source_point'];
        $paramsPoint = array(
            'user_id'   => $user_id,
            'modify_point' => $modify_point, 
            'modify_remark'    => $data['remarks'],
            // 'behavior'  => '兑换牛币数：'.$data['add_point'],
            'behavior'  => '兑换牛币消耗积分',   
        );
        $log_id = $yitiangroup_service->tradeChangePoint($paramsPoint);
        return $log_id;
    }
    /**
     * [takeExchangeLog 记录积分兑换牛币记录]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function pointToCowCoinLog($data,$scale,$current_point)
    {
        $CowData=[];
        $self_gm_id = GmPlatform::gmSelf();
        $Cowyitiangroup_service = new \ShopEM\Services\YitianGroupServices($self_gm_id);
        $cowcoin = $Cowyitiangroup_service->updateUserRewardTotal($this->user->mobile);

        $CowData['user_id'] = $this->user->id;
        $CowData['before_gm_id'] = $data['current_gm_id'];
        $CowData['after_gm_id'] = $self_gm_id;
        $CowData['before_cowcoin'] = $cowcoin;
        $CowData['before_point'] = $current_point;
        $CowData['after_point'] = $current_point-$data['source_point'];
        $CowData['after_cowcoin'] = $cowcoin+($data['source_point']*$scale);
        $CowData['point'] = $data['source_point'];
        $CowData['cowcoin'] = $data['source_point']*$scale;
        $CowData['parities'] = $scale;
        $log = UserCowCoinLog::create($CowData);

        return $log->id;
    }

    /**
     * [takeExchangeLog 兑换积分获得牛币记录]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function addExchangeLog($data,$user_id)
    {
        $self_gm_id = GmPlatform::gmSelf();
        $gm_id = $self_gm_id;
        $yitiangroup_service = new \ShopEM\Services\YitianGroupServices($gm_id);

        $add_point = $data['add_point'];
        $paramsPoint = array(
            'user_id'   => $user_id,
            'modify_point' => $add_point, 
            'modify_remark'    => $data['remarks'],
            'behavior'  => '兑换积分获得牛币',   
        );
        $log_id = $yitiangroup_service->tradeChangePoint($paramsPoint);
        UserPointLog::where('id',$log_id)->update(['push_crm'=>2,'log_type'=>'exchange']);
    }


	/**
	 * [pointLog 积分兑换记录]
	 * @param string $value [description]
	 */
	public function pointExchangeLog(Request $request,UserPointLogsRepository $repository)
	{
		$input = $request->all();
		$input['user_id'] = $this->user->id;
        $input['log_type'] = 'exchange';
		$input['push_crm'] = '2';
		$lists = $repository->search($input);
		$lists = $lists->toArray();
		
		foreach ($lists['data'] as $key => &$value) 
		{
            if ($value['log_obj']) 
            {
			     $value['tradeInfo'] = TradeOrder::where('tid',$value['log_obj'])->select('goods_name','goods_image','avg_points_fee','amount','gm_id')->first();
            }
		}
		return $this->resSuccess($lists);
	}

    /**
     * [setDefault 设置默认项目]
     * @param Request $request [description]
     */
    public function setPaltform(Request $request)
    {
        $gm_id = $request->gm_id;
        $user_id = $this->user->id;
        DB::beginTransaction();
        try {
            $service = new YitianGroupServices($gm_id);
            $service->createRelYitian($this->user->mobile,$user_id);
            
            UserRelYitianInfo::where('user_id',$user_id)->update(['default'=>0]);
            UserRelYitianInfo::where(['user_id'=>$user_id,'gm_id'=>$gm_id])->update(['default'=>1]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return $this->resFailed(600);
        }
        return $this->resSuccess();
    }

    /**
     * [defaultPaltform 默认项目]
     * @return [type] [description]
     */
    public function defaultPaltform()
    {
        $user_id = $this->user->id;
        $default = UserRelYitianInfo::where(['user_id'=>$user_id,'default'=>1])->value('gm_id');
        $user_default = empty($default) ? 0 : $default;
        return $this->resSuccess($user_default);
    }



    public static function updateCrmUser($user,$gm_id)
    {
        $data['user_id']= $user['id'];
        $data['mobile'] = $user['mobile'];
        $data['gm_id']  = $gm_id;
        $data['updateCardTypeCode'] = true;
        UpdateCrmUserInfo::dispatch($data);
    }

    /**
     * [积分明细]
     * @Author swl
     * @param string $value [description]
     */
    public function getPointLog(Request $request,UserPointLogsRepository $repository)
    {
        $input = $request->all();
        $input['user_id'] = $this->user->id;
        $input['push_crm'] = '2';

        if (!$request->has('gm_id')) {
            $input['gm_id'] = $this->GMID;
        }
        $lists = $repository->search($input);
        $lists = $lists->toArray();

        return $this->resSuccess($lists);
    }

     /**
     * [积分规则]
     * @Author swl
     * @param string $value [description]
     */
    public function pointRule(Request $request){
       $data = [
            'is_show'=>1,
            'type'=>0
       ];
       if ($request->has('gm_id')) {
            $data['gm_id'] = $request->gm_id;
       }
       $ruleModel = new \ShopEM\Models\PlatformRule;
       $lists = $ruleModel->where($data)->orderBy('listorder','asc')->first();
       return $this->resSuccess($lists);
    }

    /**
     * [获取规则]
     * @Author swl
     * @param string $type [0:积分 1：分销]
     * 
     */
    public function getRule(Request $request){
        $type = $request->type??0;
        $data = [
            'is_show'=>1,
            'type'=>$type
       ];
       if ($request->has('gm_id')) {
            $data['gm_id'] = $request->gm_id;
       }
       $ruleModel = new \ShopEM\Models\PlatformRule;
       $lists = $ruleModel->where($data)->orderBy('listorder','asc')->first();
       return $this->resSuccess($lists);
    }

     /**
     * [积分详情]
     * @Author swl
     * @param string $value [description]
     */
    public function pointDetail(Request $request){
        $id = $request->id;
        if(empty($id)){
            return $this->resFailed(701,'缺少id参数');
        }

       $detail = UserPointLog::find($id);
       if(empty($detail)){
            return $this->resFailed(701,'数据为空');
        }
        $detail = $detail->toArray();
        if($detail['log_obj']){
            if($detail['log_type']=='selfIncr'){
                // 自助积分
                $model = new \ShopEM\Models\IntegralBySelf;
                $shop = $model->select('crm_master_stores.storeName','integral_by_selves.fee')->leftJoin('crm_master_stores','crm_master_stores.id','integral_by_selves.shop_id')->where('integral_by_selves.id',$detail['log_obj'])->first();
                $amount =  $shop['fee']??'';
                $shopName = $shop['storeName']??'';
            }else{
                // 订单
                $model = new \ShopEM\Models\Shop;
                $shop = $model->select('shops.shop_name','trades.amount')->leftJoin('trades','shops.id','trades.shop_id')->where('trades.tid',$detail['log_obj'])->first();
                $amount =  $shop['amount']??'';
                $shopName = $shop['shop_name']??'';         
            }
        }
        $detail['amount'] = $amount??'';//消费金额
        $detail['shop_name'] = $shopName??'';//消费店铺
       return $this->resSuccess($detail);
    }


}