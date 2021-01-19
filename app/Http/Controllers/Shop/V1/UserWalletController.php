<?php

/**
 * 储值卡-会员钱包功能模块
 * UserWalletController.php
 * @Author: nlx
 * @Date:   2020-07-23 16:56:16
 */
namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Requests\Shop\SendMonileCodeRequest;
use ShopEM\Http\Requests\Shop\CheckMonileCodeRequest;
use ShopEM\Http\Requests\Shop\SetPayPasswordRequest;
use Illuminate\Support\Facades\Hash;
use ShopEM\Models\UserPassword;
use Carbon\Carbon;
use ShopEM\Services\QrCode;

class UserWalletController extends BaseController
{
	//短信标识
	protected $send_key = 'user_wallet';

	public function sendCode(SendMonileCodeRequest $request)
	{
		// return $this->resFailed(600, '钱包功能待开放');
		$user_id = $this->user->id;

		$cache_key = 'user_wallet_send_code_id_'.$user_id;
		$expiresAt = Carbon::tomorrow();
		$cache_value = Cache::get( $cache_key , 0);
		if ($cache_value >= 3) {
			return $this->resFailed(600, '每天只有3次获取验证机会');
		}

		$mobile = $request->mobile;
		$params = [
            'mobile' => $mobile,
            'domain' => $this->send_key,
        ];
        $send = sendCode('mobile', $params);
        if ($send['code']) {
            return $this->resFailed(600, $send['msg']);
        }

        if (Cache::has($cache_key)) {
			Cache::increment($cache_key);
		}else{
			Cache::put($cache_key,1,$expiresAt);
		}
        return $this->resSuccess($send['msg']);
	}

	//检验验证码
	public function checkCode(CheckMonileCodeRequest $request)
	{
		$input = $request->only('use_type','mobile','code');
		if (!isset($input['use_type']) || empty($input['use_type'])) {
            return $this->resFailed(406,'检验类型必填');
		}

		if (env('APP_DEV') === true  ) 
    	{
    		if ($input['code'] != '123456') {
    			$check = checkCode($this->send_key, $input['mobile'], $input['code']);
		        if ($check['code']) {
		            return $this->resFailed(600, $check['msg']);
		        }
    		}
    	}else{

			$check = checkCode($this->send_key, $input['mobile'], $input['code']);
	        if ($check['code']) {
	            return $this->resFailed(600, $check['msg']);
	        }
    	}
        //设置支付密码验证时效
        $this->_setPayEnable($input['use_type']);

        return $this->resSuccess([],'验证成功');
	}

	//是否设置支付密码
	public function hasPayPassword()
	{
		$user_id = $this->user->id;
		//没设置
		if (!UserPassword::hasPayPass($user_id)) {
			return $this->resSuccess(['status'=>0]);
		}
		//已冻结
		if (!UserPassword::usability($user_id)) {
			$time = UserPassword::THAW_TIME;
			return $this->resSuccess(['status'=>2,'time'=> $time]);
		}
		return $this->resSuccess(['status'=>1]);
	}

	//设置支付密码
	public function setPayPassword(SetPayPasswordRequest $request)
	{
		$input = $request->only('password');
		$user_id = $this->user->id;
		if (!$this->_isPayEnable('set')) {
			return $this->resFailed(800); 
		}
		DB::beginTransaction();
		try{

			$data['pay_password'] = bcrypt($input['password']);
			UserPassword::where('user_id',$user_id)->update($data);

            DB::commit();
		}catch(\Exception $e){
			DB::rollBack();
            return ['code' => 702, 'msg' => $e->getMessage()];
		}
		return $this->resSuccess([],'设置成功');
	}

	//检验支付密码
	public function checkPayPassword(Request $request)
	{
		$user_id = $this->user->id;
		$input = $request->only('use_type','password');
		if (!isset($input['use_type']) || empty($input['use_type'])) {
            return $this->resFailed(406,'检验类型必填');
		}
		$user = UserPassword::where('user_id',$user_id)->first();
		if (!Hash::check($input['password'], $user->pay_password)) {
			$num = UserPassword::payPassError($user_id);
            return $this->resFailed(406,'密码错误',['num'=>$num]);
        }
        //设置支付密码验证时效
        $this->_setPayEnable($input['use_type']);

        return $this->resSuccess([],'密码正确');
	}

	//设置支付密码验证时效
	private function _setPayEnable($use_type)
	{
		$user_id = $this->user->id;
		// 设置：set  打开钱包：open 
		$cache_key = 'paypassword_type_'. $use_type .'userid_'. $user_id;
        Cache::put($cache_key, 1, now()->addMinutes(10));
	}

	//检验支付密码验证时效
	private function _isPayEnable($use_type)
	{
		$user_id = $this->user->id;
		$cache_key = 'paypassword_type_'. $use_type .'userid_'. $user_id;
		$value = Cache::get($cache_key, 0);
		if ($value > 0 ) return true;
		return false;
	}

	//打开钱包-显示二维码
	public function openPayCode(Request $request)
	{
		$user_id = $this->user->id;
		if (!$this->_isPayEnable('open')) {
			return $this->resFailed(800);
		}

		try{
			//待接口完成,后期优化- 传通联的会员id
			$url = (new QrCode)->payCode($user_id);
		}catch(\Exception $e){
            return ['code' => 702, 'msg' => $e->getMessage()];
		}
		
		$image_info = getimagesize($url);
        $image_data = file_get_contents($url);
        $base64 = 'data:'.$image_info['mime'].';base64,'.chunk_split(base64_encode($image_data));
        
		return $this->resSuccess(['url'=>$url,'base64'=>$base64]);
	}

	//支付码支付状态-使用第三方回调
	public function notifyStatus()
	{
		$user_id = $this->user->id;
		$rand = rand(1,5);

		$result = ['status'=>0,'msg'=>'支付中'];
		if ($rand == 1) {
			$result = ['status'=>1,'msg'=>'支付成功'];
		}
		if ($rand == 2) {
			$result = ['status'=>2,'msg'=>'支付失败'];
		}
		return $this->resSuccess($result);

	}

	//第三方支付回调
	public function payNotify(Request $request)
	{
		$data = $request->all();
		workLog($data,'user-wallet','pay-notify');
		return 'success';
	}
}