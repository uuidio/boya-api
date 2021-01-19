<?php

/**
 * MemberBenefitsController.php
 * 会员权益
 * @Author: nlx
 * @Date:   2020-05-19 17:42:18
 */
namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Services\MemberBenefitService;
use ShopEM\Models\MemberBenefitConfig;


class MemberBenefitsController extends BaseController
{
	
	public function detail(Request $request,MemberBenefitService $service)
	{
		$input_data = $request->only('page','group');
		$detail = MemberBenefitConfig::where('gm_id',$this->GMID)->where($input_data)->first();
		if (empty($detail)) {
			return $this->resSuccess($service->formatValue($input_data));
		}
		return $this->resSuccess($detail->value);
	}

	//新会员赠送积分
	public function registerPoint(Request $request,MemberBenefitService $service)
	{
		$input_data = $request->only('open_status','point');
		$input_data['open_status'] = $input_data['open_status']??0;
		if ($input_data['open_status'] > 1 && intval($input_data['point']) <= 0) {
			return $this->resFailed(414,'赠送积分错误');
		}
		$admin_log = '新会员赠送积分';
		try {
			if ($input_data['open_status'] > 1) {
				$admin_log .= ':状态开启,积分为（ '.intval($input_data['point']).' )。';
			}else{
				$admin_log .= ':状态关闭。';
			}

			$input_data['page'] = 'register';
			$input_data['group'] = 'point';
			$input_data['gm_id'] = $this->GMID;
			$service->save($input_data);

		} catch (\Exception $e) {
			//日志
            $this->adminlog($admin_log, 0);
			return $this->resFailed(414,$e->getMessage());
		}

		//日志
        $this->adminlog($admin_log, 1);
        return $this->resSuccess();
	}

}