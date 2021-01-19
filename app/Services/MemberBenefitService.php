<?php

/**
 * MemberBenefitService.php
 * 会员权益服务
 * @Author: nlx
 * @Date:   2020-05-21 10:45:46
 */
namespace ShopEM\Services;
use ShopEM\Models\MemberBenefitConfig;

class MemberBenefitService
{
	
	public function save($data)
	{
		$value = self::formatValue($data);

		$params['page'] = $data['page'];
		$params['group'] = $data['group'];
		$params['gm_id'] = $data['gm_id'];
		$id = MemberBenefitConfig::where($params)->value('id');

		$params['value'] = json_encode($value);
		if (empty($id) || !$id) {
			MemberBenefitConfig::create($params);
		}else{
			MemberBenefitConfig::where('id',$id)->update(['value'=>$params['value']]);
		}
	}

	//格式化保存数据
	public static function formatValue($data)
	{
		if ($data['page'] == 'register' && $data['group'] == 'point') 
		{
			//新会员赠送积分
			$params = [
				'open_status' => $data['open_status']??0,
				'point' => $data['point']??0,
				'open_time' => time(),
			];
			return $params;
		}
	}

}