<?php

/**
 * MemberBenefitsController.php
 * 会员权益
 * @Author: nlx
 * @Date:   2020-05-19 17:42:18
 */
namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\MemberBenefitConfig;
use ShopEM\Models\MemberBenefitLog;
use ShopEM\Models\UserRelYitianInfo;
use ShopEM\Services\Yitian\PointService;

class MemberBenefitsController extends BaseController
{
	
	/**
	 * [newPointStatus 新会员赠送积分]
	 * @param  string $value [description]
	 * @return [type]        [description]
	 */
	public function newPointStatus(Request $request)
	{
		if(!isset($request->gm_id) || empty($request->gm_id)){
			return $this->resFailed(406,'项目参数错误');
		}
		$detail = [];
		$point = $open_status = 0;
		$user_id = $this->user->id;
		$input_data['page'] = 'register';
		$input_data['group'] = 'point';
		$input_data['gm_id'] = $request->gm_id;

		$logSucc = MemberBenefitLog::where($input_data)->where('user_id',$user_id)->where('log_text','succ')->exists();
		if (!$logSucc) {
			$detail = MemberBenefitConfig::where($input_data)->first();
		}
		if (!empty($detail)) {
			$open_status = $detail->value['open_status'];
			$point = $detail->value['point'];
			$open_time = $detail->value['open_time'];
			if ($open_status>0) 
			{
				$yitian_user = UserRelYitianInfo::where(['user_id'=>$user_id,'gm_id'=>$input_data['gm_id']])->select('created_at','yitian_id')->first();
				if (empty($yitian_user) || empty($yitian_user->yitian_id)) {
					$open_status = 0;
				}
				$created_at = $yitian_user->created_at;
				if (!empty($created_at) && strtotime($created_at) >= $open_time) {
					$open_status = 1;
				}else{
					$open_status = 0;
				}
			}
		}

		$data['point'] = $point;
		$data['open_status'] = $open_status>0?true:false;
		return $this->resSuccess($data);
	}

	/**
	 * [getPoint 领取赠送积分]
	 * @return [type] [description]
	 */
	public function getPoint(Request $request,PointService $service)
	{
		if(!isset($request->gm_id) || empty($request->gm_id)){
			return $this->resFailed(406,'项目参数错误');
		}
		$user_id = $this->user->id;
		$input_data['page'] = 'register';
		$input_data['group'] = 'point';
		$input_data['gm_id'] = $request->gm_id;
		$logSucc = MemberBenefitLog::where($input_data)->where('user_id',$user_id)->where('log_text','succ')->exists();

		if ($logSucc) {
			return $this->resFailed(406,'已领取过了');
		}
		$config = MemberBenefitConfig::where($input_data)->first();
		if (empty($config) || $config->value['open_status'] == 0) {
			return $this->resFailed(406,'活动已结束');
		}

		$point = $config->value['point'];
		if ($point <= 0) {
			return $this->resFailed(406,'活动已结束');
		}
		try {
			$params = $input_data;
			$params['user_id'] = $user_id;
			$params['point'] = $point;
			$result = $service->register($params);
			
			if ($result !== false) {
				$logData = $input_data;
				$logData['user_id'] = $user_id;
				$logData['log_text'] = 'succ';
				MemberBenefitLog::create($logData);
			}
		} catch (\Exception $e) {
			pointErrorLog($user_id . '-新会员注册赠送积分失败：'.$e->getMessage());
			return $this->resFailed(406,'领取失败');
		}
		return $this->resSuccess([],'领取成功');
	}


}