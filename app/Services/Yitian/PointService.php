<?php

/**
 * PointService.php
 * @Author: nlx
 * @Date:   2020-05-21 17:57:07
 */
namespace ShopEM\Services\Yitian;
use ShopEM\Services\YitianGroupServices;

class PointService 
{
	
	public function register($data)
	{
		$yitiangroup_service = new YitianGroupServices($data['gm_id']);
		$pointdata = array(
            'user_id'  => $data['user_id'],
            'type'     => 'obtain',
            'num'      => $data['point'],
            'behavior' => "新会员注册赠送积分",
            'remark'   => '新会员注册赠送积分',
            'log_type' => 'register',
        );
        $result = $yitiangroup_service->updateUserYitianPoint($pointdata);
        return $result;
	}
}