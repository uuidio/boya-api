<?php

/**
 * 密钥管理
 * @Author: nlx
 * @Date:   2020-07-22 18:27:19
 * KeyManageController.php
 */
namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Models\Config;
use ShopEM\Http\Requests\Shop\SendMonileCodeRequest;
use ShopEM\Http\Requests\Group\CheckMobileCodeRequest;

class KeyManageController extends BaseController
{
	protected $config_page = 'key_manage';

	protected $config_group = 'public_key';

	protected $group_mobile = 'mobile';


	public function detail()
	{
		$set_mobile = $this->_keyConfig($this->group_mobile);
		$set_public_key = $this->_keyConfig($this->config_group);
		
		$data['set_mobile'] = empty($set_mobile) ? false : $set_mobile ;
		$data['set_public_key'] = empty($set_public_key) ? false : true ;

		return $this->resSuccess($data);
	}

	//查看密钥
	public function showKey(CheckMobileCodeRequest $request)
	{
		$check = checkCode('key_show', $request->mobile, $request->code);
        if ($check['code']) {
            return $this->resFailed(600, $check['msg']);
        }
        $key = $this->_keyConfig($this->config_group);

        return $this->resSuccess($key);
	}

	//保存密钥
	public function saveKey(CheckMobileCodeRequest $request)
	{
		if (!isset($request->public_key) || empty($request->public_key)) {
			return $this->resFailed(406, '请填写公钥');
		}
		$check = checkCode('key_save', $request->mobile, $request->code);
        if ($check['code']) {
            return $this->resFailed(600, $check['msg']);
        }
        $this->_keyConfig($this->config_group, true, trim($request->public_key));
        return $this->resSuccess([],'保存密钥成功');
	}

	//绑定手机号
	public function bindMobile(CheckMobileCodeRequest $request)
	{
		$mobile = $request->mobile;
		$check = checkCode('key_bind', $request->mobile, $request->code);
        if ($check['code']) {
            return $this->resFailed(600, $check['msg']);
        }
        $this->_keyConfig( $this->group_mobile, true, $mobile);
		return $this->resSuccess([],'绑定成功');
	}

	//解绑手机号
	public function unBindMobile(CheckMobileCodeRequest $request)
	{
		$check = checkCode('key_unbind', $request->mobile, $request->code);
        if ($check['code']) {
            return $this->resFailed(600, $check['msg']);
        }
        $this->_keyConfig( $this->group_mobile, true);
        return $this->resSuccess([],'解绑成功');
	}

	//发送验证码
	public function sendCode(SendMonileCodeRequest $request)
	{
		$mobile = $request->mobile;
		$type = $request->type??'';
		$types = ['save','bind','unbind','show'];
		if (!in_array($type, $types)) {
			return $this->resFailed(406, '请求类型错误');
		}
		try {
			$this->_checkManage($type,$mobile);
		} catch (\Exception $e) {
			return $this->resFailed(406, $e->getMessage());
		}
		$params = [
            'mobile' => $mobile,
            'domain' => 'key_'.$type,
        ];
        $send = sendCode('mobile', $params);
        if ($send['code']) {
            return $this->resFailed(600, $send['msg']);
        }
        return $this->resSuccess($send['msg']);
	}

	/**
	 * [_checkManage 检查]
	 * @param  [type] $type [类型]
	 * @param  [type] $mobile [请求手机号]
	 * @return [type]       [description]
	 */
	private function _checkManage($type,$mobile)
	{
		switch ($type) 
		{
			case 'bind':
				$now_mobile = $this->_keyConfig($this->group_mobile);
				if ($now_mobile == $mobile) {
					throw new \Exception('已绑定该手机号');
				}
				if ($now_mobile) {
					throw new \Exception('先解绑手机号');
				}
				break;
			case 'unbind':
				$old_mobile = $this->_keyConfig($this->group_mobile);
				if ($old_mobile && $old_mobile != $mobile) {
					throw new \Exception('请使用管理者手机号');
				}
				break;
			default:
				$now_mobile = $this->_keyConfig($this->group_mobile);
				if (!$now_mobile) {
					throw new \Exception('请先绑定手机号');
				}
				if ($now_mobile != $mobile) {
					throw new \Exception('请使用管理者手机号!');
				}
				break;
		}
		return true;
	}

	/**
	 * 获取配置信息
	 * @param  [type]  $group  [配置分组]
	 * @param  boolean $update [是否更新]
	 * @param  string  $value  [更新的值]
	 * @return [type]          [description]
	 */
	private function _keyConfig($group, $update=false, $value='')
	{
		$data = ['page'=>$this->config_page,'group'=>$group];
		$config = Config::where($data)->first();
		if (empty($config)) 
		{
			$data['gm_id'] = 0;
			$data['value'] = $value;
			Config::create($data);
			return $data['value'];
		}
		if ($update) 
		{
			Config::where($data)->update(['value'=>$value]);
			return $value;
		}
		return $config->value;
	}

}