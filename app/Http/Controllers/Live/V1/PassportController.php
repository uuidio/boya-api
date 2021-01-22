<?php
/**
 * @Filename        PassportController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Http\Controllers\Live\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use ShopEM\Services\Live\ImService;
use Illuminate\Support\Facades\Auth;
use ShopEM\Http\Controllers\Live\BaseController;
use ShopEM\Http\Requests\Live\LoginRequest;
use ShopEM\Models\LiveUsers;
use ShopEM\Models\Lives;
use ShopEM\Traits\ProxyOauth;
use ShopEM\Http\Requests\Live\LiveUserRequest;
use ShopEM\Models\OauthAccessTokens;
use ShopEM\Models\OauthAccessTokenProviders;
use ShopEM\Models\OauthRefreshTokens;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\AppVersions;

class PassportController extends BaseController
{
    use ProxyOauth;

    /**
     * @Author linzhe
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(loginRequest $request)
    {

        $hasUser = LiveUsers::where('login_account', $request->username)->first();

        if (empty($hasUser)) {
            return $this->resFailed(402);
        }
        $token = $this->authenticate('live_users');

        if (!$token) {
            return $this->resFailed(402);
        }
        $expiration = date('Y-m-d H:i:s',strtotime('+1year', strtotime($hasUser['created_at'])));//
        $token['expiration'] = $expiration;
        $token['username'] = $hasUser['username'];
        return $this->resSuccess($token);
    }

    /**
     * @Author linzhe
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginOauth(Request $request)
    {
        $token = Auth::guard('live_users')->user()->token()->toArray();
        $live_id = $this->user->live_id;
        $token_id = LiveUsers::where('id',$live_id)->select('oauth_access_token_id')->first();
        if($token_id['oauth_access_token_id']){
            OauthAccessTokens::where('id', $token_id['oauth_access_token_id'])->delete();
            OauthAccessTokenProviders::where('oauth_access_token_id',$token_id['oauth_access_token_id'])->delete();
            OauthRefreshTokens::where('access_token_id', $token_id['oauth_access_token_id'])->delete();
        }
        LiveUsers::where('id',$live_id)->update(['oauth_access_token_id'=>$token['id']]);
        return $this->resSuccess();
    }

    /**
     * 退出
     *
     * @Author linzhe
     * @return string
     */
    public function logout()
    {

        if (Auth::guard('live_users')->check()) {
            Auth::guard('live_users')->user()->token()->delete();
        }

        return $this->resSuccess();
    }

    /**
     * @Author linzhe
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(LiveUserRequest $request)
    {
        $input_data = $request->only('login_account', 'mobile','password','company','code','username');
        $check = checkCode('login', $input_data['mobile'], $input_data['code']);
        if ($check['code']) {
            return $this->resFailed(600, $check['msg']);
        }
        $chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$|19[0-9]{1}[0-9]{8}$|16[0-9]{1}[0-9]{8}$/";

        if(!preg_match($chars, $input_data['mobile']))
        {
            return $this->resFailed(600, '手机号格式错误');
        }
        $shop_id = '0';
        $hasPassword = preg_match('/^(\w*(?=\w*\d)(?=\w*[A-Za-z])\w*){8,16}$/', $input_data['password']);
        if(!$hasPassword) {
            return $this->resFailed(702,'8-16位字符（英文/数字/符号）至少两种或下划线组合');
        }
        $data['password'] = bcrypt($input_data['password']);
        $data['mobile'] = $input_data['mobile'];
        $data['login_account'] = $input_data['login_account'];
        $data['company'] = $input_data['company'];
        $data['username'] = $input_data['username'];
        LiveUsers::create($data);

        $token = $this->authenticate('live_users', $data['login_account'], $data['password']);
        return $this->resSuccess($token);
    }

    /**
     * [sendLoginCode 发送登录验证码]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function sendLoginCode(Request $request)
    {
        $params = [
            'mobile' => $request->mobile,
            'domain' => 'login',
        ];
        $send = sendCode('mobile', $params);
        if ($send['code']) {
            return $this->resFailed(600, $send['msg']);
        }
        return $this->resSuccess($send['msg']);
    }

    public function resetPwd(Request $request)
    {
        $input_data = $request->only('mobile','password','code');
        $check = checkCode('login', $input_data['mobile'], $input_data['code']);
        if ($check['code']) {
            return $this->resFailed(600, $check['msg']);
        }
        DB::beginTransaction();
        try {
            LiveUsers::where('mobile',$input_data['mobile'])->update(['password'=>bcrypt($request->password)]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->resFailed(402,'修改密码失败');
        }
        return $this->resSuccess([],'修改成功，重新登录');
    }

    /**
     * 获取会员信息
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $user_id = $this->user->id;

        try {
            $data = LiveUsers::where('id','=',$user_id)->select('id','username','img_url','created_at')->first();
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }
        $expiration = date('Y-m-d H:i:s',strtotime('+1year', strtotime($data['created_at'])));//
        $data['expiration'] = $expiration;
        unset($data['created_at']);
        return $this->resSuccess([
            'data' => $data
        ]);
    }

    /**
     * 修改会员信息
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function modifyUser(Request $request)
    {
        $data = $request->only('img_url', 'username');
        $user_id = $this->user->id;
        $live_id = $this->user->live_id;
        try {
            LiveUsers::where('id','=',$user_id)->update($data);
            $service = new ImService();
            $accid = Lives::where('id','=',$live_id)->select('accid')->first();
            $service->updateUser(['accid'=>$accid['accid'],'name'=>$data['username']]);
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 获取版本
     *
     * @Author LINZHE
     */
    public function versions(Request $request)
    {
        $data = AppVersions::orderBy('id', 'desc')->first();
        if($data['versions'] == $request->versions){
          #  $error = json_encode();
            return $this->resFailed(702, '已是最新版本');
        }
        return $this->resSuccess($data);
    }
}