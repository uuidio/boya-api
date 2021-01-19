<?php
/**
 * @Filename        PassportController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\SignupUserRequest;
use ShopEM\Http\Requests\Shop\UserAccountRequest;
use ShopEM\Http\Requests\Shop\SendMonileCodeRequest;
use ShopEM\Http\Requests\Shop\CheckMonileCodeRequest;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserLoginLog;
use ShopEM\Services\Aliyun\AliyunSms as Sms;
use ShopEM\Services\User\UserPassport;
use ShopEM\Traits\ProxyOauth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use ShopEM\Events\UserLoginEvent;


class PassportController extends BaseController
{
    use ProxyOauth;

    public function test()
    {

        return $this->resSuccess([], '这是测试!!');
    }


    /**
     * @Author hfh_wind
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(UserAccountRequest $request,UserPassport $service)
    {
        $type = $service->checkLoginNameType($request->username);
        $hasUser = UserAccount::where($type,$request->username)->select('id',$type)->first();

        if (empty($hasUser)) {
            return $this->resFailed(402);
        }

        $token = $this->authenticate('shop_users');

        if (!$token) {
            return $this->resFailed(402);
        }

        //记录登录日志
        $event = new UserLoginEvent();     //自定义
        if (!isset($hasUser->mobile)) {
            $hasUser->mobile = 0;
        }
        event($event->broadcastOn($hasUser->id,$hasUser->mobile,'',''));


        return $this->resSuccess($token);
    }

    /**
     * [loginByCode 验证码登录]
     * @Author mssjxzw
     * @param  CheckMonileCodeRequest $request [description]
     * @return [type]                          [description]
     */
    public function loginByCode(CheckMonileCodeRequest $request)
    {
        $check = checkCode('login',$request->mobile,$request->code);
        if ($check['code']) {
            return $this->resFailed(600, $check['msg']);
        }
        
        $openid = $request['openid']?$request['openid']:'';
        if (UserPassport::isExistsAccount($request->mobile)) {
            $user = UserAccount::where('mobile',$request->mobile)->first();
            //如果是老用户，就更改账号和密码
            if (strpos($user['login_account'],'shopem') === 0) {
                $account = $this->getAccount();
                $data = [
                    'username'=>$account,
                    'password'=>'Hyflsc@'.date('Ymd'),
                ];
                $accountUser_data = UserPassport::signupUser($data);
                $user->login_account = $account;
                $user->password = $accountUser_data['password'];
                
                $user->save();
            }
            //如果有openid就更新
            if ($openid) {
                UserAccount::where('id',$user->id)->update(['openid'=>$openid]);
            }
            
        }else{
            $account = $this->getAccount();
            $data = [
                'username'=>$account,
                'password'=>'Hyflsc@'.date('Ymd'),
            ];
            $accountUser_data = UserPassport::signupUser($data);
            $accountUser_data['mobile'] = $request->mobile;
            if ( $openid ) $accountUser_data['openid'] = $openid;
            $user=UserAccount::create($accountUser_data);
        }
        $u = explode('_',$user['login_account']);
        $password = 'Hyflsc@'.substr($u[0],2);
        $token = $this->authenticate('shop_users',$user['login_account'],$password);
        if (!$token) {
            return $this->resFailed(402);
        }
        //记录登录日志
        $event = new UserLoginEvent();     //自定义
        event($event->broadcastOn($user->id,trim($user['login_account']),'',''));
        return $this->resSuccess($token);
    }

    /**
     * [sendLoginCode 发送登录验证码]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function sendLoginCode(SendMonileCodeRequest $request)
    {
        $params = [
            'mobile'=>$request->mobile,
            'domain'=>'login',
        ];
        $send = sendCode('mobile',$params);
        if ($send['code']) {
            return $this->resFailed(600, $send['msg']);
        }
        return $this->resSuccess($send['msg']);
    }

    /**
     * 完成注册流程
     *
     * @Author hfh_wind
     * @param PasspordRequest $request
     * @return array|\Illuminate\Http\JsonResponse
     * @throws \Exception
     * @throws \ShopEM\Services\User\Exception
     */
    public function doRegister(SignupUserRequest $request)
    {
        $userInfo = $request->all();

        $accountUser_data = UserPassport::signupUser($userInfo);

        DB::beginTransaction();
        try {

            $user_id=UserAccount::create($accountUser_data);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $msg = $e->getMessage();
            throw new \Exception('会员注册失败' . $msg);
        }

        $token = $this->authenticate('shop_users');


        if (!$token) {
            return $this->resFailed(402);
        }

        //记录登录日志
        $event = new UserLoginEvent();     //自定义
        event($event->broadcastOn($user_id->id,trim($userInfo['username']),'',''));

        return $this->resSuccess($token);
    }


    /**
     * 退出
     *
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        if (Auth::guard('shop_users')->check()) {
            Auth::guard('shop_users')->user()->token()->delete();
        }

        return $this->resSuccess([], '退出成功!');
    }

    /**
     * [autoLogin 自动登录]
     * @Author mssjxzw
     * @param  string  $openid [description]
     * @return [type]          [description]
     */
    public function autoLogin($openid = '')
    {
        if (!$openid) {
            return $this->resFailed(414, '参数不全');
        }
        if (!UserPassport::isExistsAccount($openid,null,'openid')) {
            return $this->resFailed(555, '未找到账号');
        }
        $user_id = UserAccount::where('openid',$openid)->first();
        $u = explode('_',$user_id['login_account']);
        $password = 'Hyflsc@'.substr($u[0],2);
        $token = $this->authenticate('shop_users',$user_id['login_account'],$password);
        if (!$token) {
            return $this->resFailed(402);
        }
        //记录登录日志
        $event = new UserLoginEvent();     //自定义
        event($event->broadcastOn($user_id->id,trim($user_id['login_account']),'',''));

        return $this->resSuccess($token);
    }

    /**
     * [getAccount 获取会员账号]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    private function getAccount()
    {
        $account = 'hy'.date('Ymd').'_'.getRandStr(6);
        if (UserPassport::isExistsAccount($account,null,'login_account')) {
            $account = $this->getAccount();
        }
        return $account;
    }

    /**
     * [createAccount 创建账号]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    public function createAccount()
    {
        $mobile = request('mobile');
        if (!$mobile) {
            return $this->resFailed(414, '请输入手机号');
        }
        $code = request('code');
        $check = checkCode('login',$mobile,$code);
        if ($check['code']) {
            return $this->resFailed(600, $check['msg']);
        }
        $openid = request('openid');
        if (!$openid) {
            return $this->resFailed(414, '请输入openid');
        }
        $wx_info = \ShopEM\Models\WxUserinfo::where('openid',$openid)->first();
        if (!$wx_info) {
            return $this->resFailed(414, '无效openid');
        }
        $user = UserAccount::where('mobile',$mobile)->first();
        if ($user) {
            if (strpos($user->login_account,'shopem') === 0) {
                $account = $this->getAccount();
                $data = [
                    'username'=>$account,
                    'password'=>'Hyflsc@'.date('Ymd'),
                ];
                $accountUser_data = UserPassport::signupUser($data);
                $user->login_account = $account;
                $user->password = $accountUser_data['password'];
            }
            $user->openid = $openid;
            $user->save();
        }else{
            $account = $this->getAccount();
            $data = [
                'username'=>$account,
                'password'=>'Hyflsc@'.date('Ymd'),
            ];
            $accountUser_data = UserPassport::signupUser($data);
            $accountUser_data['mobile'] = $mobile;
            $accountUser_data['openid'] = $openid;
            $user=UserAccount::create($accountUser_data);
        }
        $user_info = \ShopEM\Models\UserProfile::where('id',$user->id)->first();
        if ($user_info) {
            $user_info->head_pic = $wx_info->headimgurl;
            $user_info->name = $wx_info->nickname;
            $user_info->sex = $wx_info->sex == 1?$wx_info->sex:($wx_info->sex == 2)?0:2;
            $user_info->save();
        }
        $u = explode('_',$user->login_account);
        $password = 'Hyflsc@'.substr($u[0],2);
        $token = $this->authenticate('shop_users',$user->login_account,$password);
        if (!$token) {
            return $this->resFailed(402);
        }
        //记录登录日志
        $event = new UserLoginEvent();     //自定义
        event($event->broadcastOn($user->id,trim($user['login_account']),'',''));
        return $this->resSuccess($token);
    }


    /**
     * [wechatLogin 判断自动登录]
     * @Author mssjxzw
     * @param  string  $openid [description]
     * @return [type]          [description]
     */
    public function wechatLogin(Request $request)
    {
        $openid = $request->input('openid', '');
        if (!$openid) {
            return $this->resSuccess($this->_loginData('',''));
        }
        $user_id = UserAccount::where('openid',$openid)->first();
        $account = explode('_',$user_id['login_account']);
        $password = 'Hyflsc@'.substr($account[0],2);
        $token = $this->authenticate('shop_users',$user_id['login_account'],$password);
        if (!$token) {
            return $this->resSuccess($this->_loginData('',$openid));
        }
        //记录登录日志
        $event = new UserLoginEvent();     //自定义
        event($event->broadcastOn($user_id->id,trim($user_id['login_account']),'',''));

        return $this->resSuccess($this->_loginData($token,$openid));
    }
    /**
     * [_loginData 格式登录的返回数据]
     * @Author nlx
     * @param  string $token  [description]
     * @param  string $openid [description]
     * @return [type]         [description]
     */
    public function _loginData($token='',$openid='')
    {
        $hasUser = 1;
        if (!$token) {
            $msg = 'token不存在';
            $hasUser = -2;
        }else{
            $token = $token['access_token'];
        }
        if (!$openid) {
            $msg = 'openid 参数不全';
            $hasUser = -1;
        }
        $data['token'] = $token??'';
        $data['openid'] = $openid??'';
        $data['msg'] = $msg??'请求成功';
        $data['hasUser'] = $hasUser;
        return $data;
    }



}