<?php
/**
 * @Filename        WechatMiniController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\CheckMonileCodeRequest;
use ShopEM\Http\Requests\Shop\SendMonileCodeRequest;
use ShopEM\Http\Requests\Shop\WeChatMiniCreateAccountRequest;
use ShopEM\Http\Requests\Shop\WeChatMiniOpenIdRequest;
use ShopEM\Models\Payment;
use ShopEM\Models\RelatedLogs;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeAftersales;
use ShopEM\Models\TradeCancel;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\TradeRefunds;
use ShopEM\Models\UserAddress;
use ShopEM\Models\UserDeposit;
use ShopEM\Models\UserWallet;
use ShopEM\Models\WxUserinfo;
use ShopEM\Repositories\ConfigRepository;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserGoodsFavorite;
use ShopEM\Models\UserShopFavorite;
use ShopEM\Models\YiTianUserCard;
use ShopEM\Services\TlPay\WalletService;
use ShopEM\Services\User\UserPassport;
use ShopEM\Services\WeChatMini\WXMessage;
use ShopEM\Services\YitianGroupServices;
use ShopEM\Traits\ProxyOauth;
use ShopEM\Events\UserLoginEvent;
use ShopEM\Models\UserRelYitianInfo;


class WechatMiniController extends BaseController
{
    protected $yitiangroup_service;
    protected $coupon_service;

    public function __construct()
    {
        parent::__construct();
        $this->yitiangroup_service = new YitianGroupServices($this->GMID);
        $this->coupon_service = new \ShopEM\Services\Marketing\Coupon;
    }

    use ProxyOauth;

    public function test()
    {
        echo "test";
    }


    /**
     * [getMiniDeploy 使用的小程序配置数据]
     * @param  [type] &$appi      [description]
     * @param  [type] &$appsecret [description]
     * @return [type]             [description]
     */
    public function getMiniDeploy(&$appid,&$appsecret)
    {
        $appid = env('WECHAT_MINI_APPID');
        $appsecret = env('WECHAT_MINI_APPSECRET');
        return true;
    }


    /**
     * 会员信息
     *
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $id = $this->user['id'];
        $user = UserAccount::where('id', $id)->first();
//        $openid = $user['openid'];
        $user_id = $user['id'];
        $wx_info = $this->getWxUserInfo($user_id);
        $user_info = $this->getUserInfo($user);
        $user_info = $wx_info + $user_info;
        //会员信息
        $return = $user_info;

        $return['tradeinfo'] = $this->GetUserTradeCount($id);
        if ($wallet = UserWallet::where('user_id',$this->user->id)->first()) {
            $return['wallet'] = (new WalletService())->getWalletInfo($wallet);
        } else {
            $return['wallet'] = [
                'total'         =>  0,
                'physical_card' =>  0,
                'virtual_card'  =>  0
            ];
        }

        return $this->resSuccess($return);
    }

    /**
     * [autoLogin 自动登录]
     * @Author mssjxzw
     * @param  string $openid [description]
     * @return [type]          [description]
     */
    public function autoLogin($code = '')
    {
        $this->getMiniDeploy($appid, $appsecret,);
        // $appid = env('WECHAT_MINI_APPID');
        // $appsecret = env('WECHAT_MINI_APPSECRET');
        testLog(7777);
        try {
            //获取openid
            $jscode_res = $this->getOpenId($code, $appid, $appsecret);
            $openid = $jscode_res['openid'];
            if (!UserPassport::isExistsAccount($openid, null, 'openid')) {
                return $this->resFailed(555, '未找到账号');
            }
            $user = UserAccount::where('openid', $openid)->first();
            if ($user) {
                $token = $this->getToken($user);
                $jscode_res['token'] = $token ?: '';
//                $wx_info = $this->getWxUserInfo($openid);
                $wx_info = $this->getWxUserInfo($user['id']);
                $user_info = $this->getUserInfo($user);
                $jscode_res['user_info'] = $wx_info + $user_info;
            }
        } catch (\Exception $e) {
            return $this->resFailed(401, $e->getMessage());
        }

        return $this->resSuccess($jscode_res);
    }

    /**
     * [getAccount 获取会员账号]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    private function getAccount()
    {
        $account = 'hy' . date('Ymd') . '_' . getRandStr(6);
        if (UserPassport::isExistsAccount($account, null, 'login_account')) {
            $account = $this->getAccount();
        }
        return $account;
    }

    /**
     * 获取用户信息授权后存储用户信息并返回openid
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function openId(WeChatMiniOpenIdRequest $request)
    {
        //接收参数
        $code = $request->input('code');
        $encryptedData = $request->input('encryptedData');
        $iv = $request->input('iv');

        $gm_id = $request->input('gm_id');

        $encryptedData = urldecode($encryptedData);
        $iv = urldecode($iv);

//        $this->getMiniDeploy($appid,$appsecret);

        $param = Cache::get('gm_platform_'.$gm_id);

        if(empty($param)){
            throw new \Exception('配置参数异常！');
        }

        $appid = $param['mini_appid'];
        $appsecret =$param['mini_secret'];

        try {
            // testLog($appid);
            //获取openid
            $jscode_res = $this->getOpenId($code, $appid, $appsecret);

            //用户信息解密
            if (!isset($jscode_res['session_key'])) {
                return $this->resFailed(600, '无法获取session_key');
            }
            $en_res = $this->decryptData($appid, $jscode_res['session_key'], $encryptedData, $iv);

            //创建或更新微用户微信信息
            $wx_user=$this->save_wx_info($en_res,$appid);

            //用户已存在的话返回登录token
            $jscode_res['token'] = '';
//            $user = WxUserinfo::where('openid', $en_res['openId'])->first();
            $user_id=$wx_user['user_id']??0;
            if ($user_id) {
                $user=UserAccount::where('id', $user_id)->first();
                $token = $this->getToken($user);
                $jscode_res['token'] = $token ?: '';
//                $wx_info = $this->getWxUserInfo($en_res['openId']);
                $wx_info = $this->getWxUserInfo($user['id']);
                $user_info = $this->getUserInfo($user);
                $jscode_res['user_info'] = $wx_info + $user_info;
            }
        } catch (\Exception $e) {
            return $this->resFailed(401, $e->getMessage());
        }

        //返回openid
        return $this->resSuccess($jscode_res);
    }

    /**
     * 获取手机号码授权后创建账号并返回登陆token
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createAccount(WeChatMiniOpenIdRequest $request)
    {
        //接收参数
        $code = $request->input('code');
        $encryptedData = $request->input('encryptedData');
        $iv = $request->input('iv');

        $source = $request->input('source');
        $pid = $request->input('pid');
        $aid = $request->input('aid');
        $gid = $request->input('gid');

        //项目id
        $gm_id = $request->input('gm_id');


        $encryptedData = urldecode($encryptedData);
        $iv = urldecode($iv);

//        $this->getMiniDeploy($appid, $appsecret);
        // $appid = env('WECHAT_MINI_APPID');
        // $appsecret = env('WECHAT_MINI_APPSECRET');

        $param = Cache::get('gm_platform_'.$gm_id);

        if(empty($param)){
            throw new \Exception('配置参数异常！');
        }

        $appid = $param['mini_appid'];
        $appsecret =$param['mini_secret'];


        try {
            //获取openid
            $jscode_res = $this->getOpenId($code, $appid, $appsecret);

            //用户信息解密
            if (!isset($jscode_res['session_key'])) {
                return $this->resFailed(600, '无法获取session_key');
            }
            $en_res = $this->decryptData($appid, $jscode_res['session_key'], $encryptedData, $iv);
            $mobile = $en_res['phoneNumber'];

            $openid = $request['openid']??'';
            $user = UserAccount::where('mobile', $mobile)->first();
            if ($user) {
                $this->oldUserLogin($appid,$user,$openid);
            } else {
                $accountUser_data['pid'] = $pid ?? 0;
                $accountUser_data['source'] = $source ?? '';
                $accountUser_data['aid'] = $aid ?? 0;
                $accountUser_data['gid'] = $gid ?? 0;
                //新用户创建账号
                $user = $this->newUserLogin($mobile,$openid,$accountUser_data);
            }

            $wx_info = WxUserinfo::where(['openid'=>$jscode_res['openid']])->where('user_type', 1)->first();
            if ($wx_info) {
                testLog(1111);
                testLog($user);
                $wx_info->update(['user_id' => $user->id]);
            } else {
                testLog(222);
                testLog($user);
                WxUserinfo::create(['user_id'   => $user->id,
                    'openid'    => $jscode_res['openid'],
                    'sex'       => 0,
                    'user_type' => 1
                ]);
            }


            $count = UserDeposit::where('user_id', $user->id)->count();
            //创建记录
            if (!$count) {
                $deposit_data['user_id'] = $user->id;
                UserDeposit::create($deposit_data);
            }

            // 绑定会员关系
            if ($source == 'goods') {
                $this->SetRelated($user->id,$pid);
            }

            //获取登录token
            $token = $this->getToken($user);
            if (!$token) {
                return $this->resFailed(414);
            }
            $respon['token'] = $token;
            $wx_info = $this->getWxUserInfo($user['id']);
            $user_info = $this->getUserInfo($user);
            $respon['user_info'] = $wx_info + $user_info;
            return $this->resSuccess($respon);

            $jscode_res['token'] = $token;
            $jscode_res['user_info'] = $this->getUserInfo($user);

            return $this->resSuccess($jscode_res);
        } catch (\Exception $e) {
            return $this->resFailed(600, $e->getMessage());
        }
    }

    /**
     * 绑定会员管理
     * @param $user_id
     * @param $pid
     * @return bool
     * @throws \Exception
     */
    public function SetRelated($user_id, $pid)
    {
        $res=RelatedLogs::where(['user_id'=>$user_id,'pid'=>$pid])->count();

        if($res){
            return true;
        }

        try {
            $return['user_id'] = $user_id;
            $return['pid'] = $pid;
            $return['status'] = 1;
            RelatedLogs::create($return);

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }
        return true;
    }

    /**
     * [loginByCode 验证码登录]
     * @Author mssjxzw
     * @param  CheckMonileCodeRequest $request [description]
     * @return [type]                          [description]
     */
    public function loginByCode(CheckMonileCodeRequest $request, ConfigRepository $repository)
    {
        $check = checkCode('login', $request->mobile, $request->code);
        if ($check['code']) {
            return $this->resFailed(600, $check['msg']);
        }
        $this->getMiniDeploy($appid,$appsecret);

        $source = $request->input('source');
        $pid = $request->input('pid');
        $aid = $request->input('aid');
        $gid = $request->input('gid');

        $openid = $request['openid']??'';
        $user = UserAccount::where('mobile', $request->mobile)->first();
        if ($user) {
            //判断该手机号码绑定的openid是否跟当前微信号的openid不一样
            $doesntExist = WxUserinfo::where(['appid'=>$appid,'openid'=>$openid])->doesntExist();
            if ($openid && $user['openid'] !== $openid) {
                //如果是因为未绑定openid而不一样时，绑定openid
                if ($user['openid'])
                {
                    $oldAppId = WxUserinfo::where('openid',$user['openid'])->value('appid');
                    if($oldAppId != $appid) $doesntExist = true;
                }
                if (!$user['openid'] || $doesntExist) {
                    //老用户更新openid
                    $user->openid = $openid;
                    $user->save();
                } else {
                    return $this->resFailed(600, '该手机号码已绑定其他微信号');
                }
            }


        } else {
            //以下是静默注册
            $account = $this->getAccount();
            $u = explode('_', $account);
            $data = [
                'username' => $account,
                'password' => 'Hyflsc@' . substr($u[0], 2),
            ];
            $accountUser_data = UserPassport::signupUser($data);
            $accountUser_data['mobile'] = $request->mobile;
            if ($openid) {
                $accountUser_data['openid'] = $openid;
            }
            try {
                // 注册账号
                $accountUser_data['pid'] = $pid ?? 0;
                $accountUser_data['source'] = $source ?? '';
                $accountUser_data['aid'] = $aid ?? 0;
                $accountUser_data['gid'] = $gid ?? 0;

                $user = UserAccount::create($accountUser_data);
            } catch (\Exception $e) {
                return $this->resFailed(600, '账号创建失败');
            }
            $respon = $this->yitiangroup_service->signUpNotExistedMember($user->mobile);
            if ($respon) {
                $createRelYitian = $this->yitiangroup_service->createRelYitian($user->mobile,$user->id,true);
                $updateData = [
                    'yitian_id' => $respon['member_id'],
                    'new_yitian_user' => $respon['not_existed'],
                    'default'   => 1, //第一次注册的作为默认
                ];
                UserRelYitianInfo::where(['mobile'=>$user->mobile,'gm_id'=>$this->GMID])->update($updateData);
                // $user->update(['yitian_id' => $respon['member_id'],'new_yitian_user' => $respon['not_existed']]);
            }
            //判断是否开启了注册送积分
            $point_config = $repository->configItem('shop', 'point', $this->GMID);
            if (isset($point_config['open_register_point']) && $point_config['open_register_point']['value']) {
                //赠送积分
                if (isset($point_config['register_point_number']) && $point_config['register_point_number']['value']) {
                    $pointdata = array(
                        'user_id'  => $user->id,
                        'type'     => 'obtain',
                        'num'      => $point_config['register_point_number']['value'],
                        'behavior' => "注册赠送积分",
                        'remark'   => '注册赠送积分',
                    );
                    $result = $this->yitiangroup_service->updateUserYitianPoint($pointdata);
                    if (!$result) {
                        pointErrorLog($user['mobile'] . '注册赠送积分失败');
                    }
                }
            }
        }

        if ($openid) {
            $model = new \ShopEM\Models\WxUserinfo();
            $wx_info = $model->where('openid', $openid)->first();
            if ($wx_info) {
                $wx_info->update(['user_id' => $user->id,'appid' => $appid]);
            } else {
                $model->create(['user_id' => $user->id, 'openid' => $openid, 'sex' => 0,'appid'=>$appid]);
            }
        }

        //记录会员商品关系
        if ($source == 'goods') {
            (new WechatMiniController())->SetRelated($user->id,$pid);
        }

        //获取登录token
        $token = $this->getToken($user);
        if (!$token) {
            return $this->resFailed(414);
        }
        $respon['token'] = $token;
        $wx_info = $this->getWxUserInfo($user['id']);
        $user_info = $this->getUserInfo($user);
        $respon['user_info'] = $wx_info + $user_info;
        return $this->resSuccess($respon);
    }

    /**
     * [oldUserLogin 老会员登录验证]
     * @param  [type] $appid  [description]
     * @param  [type] $user   [description]
     * @param  string $openid [description]
     * @return [type]         [description]
     */
    public function oldUserLogin($appid,$user,$openid='')
    {
        $doesntExist = WxUserinfo::where(['appid'=>$appid,'openid'=>$openid])->doesntExist();
        if ($openid && $user['openid'] !== $openid) {
            //如果是因为未绑定openid而不一样时，绑定openid
            if ($user['openid'])
            {
                $oldAppId = WxUserinfo::where('openid',$user['openid'])->value('appid');
                if($oldAppId != $appid) $doesntExist = true;
            }


            if (!$user['openid'] || $doesntExist) {
                //老用户更新openid
                UserAccount::where('id',$user->id)->update(['openid'=>$openid]);

            } else {
                throw new \Exception("该手机号码已绑定其他微信号");

            }
        }
//        //加一个迁移会员微信信息更新
//        $oldExist = WxUserinfo::where('user_id',$user->id)->where('old_source_id','>',0)->exists();
//        if ($oldExist)
//        {
//            $service = new \ShopEM\Services\MigratingDataService;
//            $service->upholdWxUserInfo($user,$openid);
//        }
        return true;
    }


    public function newUserLogin($mobile,$openid='',$User_data)
    {
        $repository = new ConfigRepository;
        //以下是静默注册
        $account = $this->getAccount();
        $u = explode('_', $account);
        $data = [
            'username' => $account,
            'password' => 'Hyflsc@' . substr($u[0], 2),
        ];
        $accountUser_data = UserPassport::signupUser($data);
        $accountUser_data['mobile'] = $mobile;
        if ($openid) {
            $accountUser_data['openid'] = $openid;
        }
        try {
            $accountUser_data['pid'] = $User_data['pid'];
            $accountUser_data['source'] = $User_data['source'];
            $accountUser_data['aid'] = $User_data['aid'];
            $accountUser_data['gid'] = $User_data['gid'];

            $user = UserAccount::create($accountUser_data);
        } catch (\Exception $e) {
            throw new \Exception("账号创建失败");
            // return $this->resFailed(600, '账号创建失败');
        }
        /*$respon = $this->yitiangroup_service->signUpNotExistedMember($user->mobile);
        if ($respon) {
            $createRelYitian = $this->yitiangroup_service->createRelYitian($user->mobile,$user->id,true);
            $updateData = [
                'yitian_id' => $respon['member_id'],
                'new_yitian_user' => $respon['not_existed'],
                'default'   => 1, //第一次注册的作为默认
            ];
            UserRelYitianInfo::where(['mobile'=>$user->mobile,'gm_id'=>$this->GMID])->update($updateData);
            // $user->update(['yitian_id' => $respon['member_id'],'new_yitian_user' => $respon['not_existed']]);
        }
        //判断是否开启了注册送积分
        $point_config = $repository->configItem('shop', 'point', $this->GMID);
        if (isset($point_config['open_register_point']) && $point_config['open_register_point']['value']) {
            //赠送积分
            if (isset($point_config['register_point_number']) && $point_config['register_point_number']['value']) {
                $pointdata = array(
                    'user_id'  => $user->id,
                    'type'     => 'obtain',
                    'num'      => $point_config['register_point_number']['value'],
                    'behavior' => "注册赠送积分",
                    'remark'   => '注册赠送积分',
                );
                $result = $this->yitiangroup_service->updateUserYitianPoint($pointdata);
                if (!$result) {
                    pointErrorLog($user['mobile'] . '注册赠送积分失败');
                }
            }
        }*/
        return $user;
    }
    /**
     * [sendLoginCode 发送登录验证码]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function sendLoginCode(SendMonileCodeRequest $request)
    {
        return $this->resFailed(600, '请使用手机号一键登录');

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

    /**
     * 获取微信用户信息
     *
     * @Author djw
     * @param $openid
     * @return bool|mixed
     * @throws \Exception
     */
    private function getWxUserInfo($user_id)
    {
        $sex_text = [
            0 => '保密',
            1 => '男',
            2 => '女'
        ];
        $wx_info = [
            'nickname'   => '',
            'nickName'   => '',
            'sex'        => null,
            'sex_text'   => '保密',
            'headimgurl' => '',
            'avatarUrl'  => '',
            'birthday'   => null,
        ];
        $model = new \ShopEM\Models\WxUserinfo();
        //$info = $model->where('openid',$openid)->first();
        $info = $model->where('user_id', $user_id)->first();
        if ($info) {
            $res = Trade::where('user_id',$user_id)->whereNotNull('pay_time')->value('pay_time');
            $is_update_info = 0;
            if ($res && $info['is_update_info'] == 1) {
                $is_update_info = 1;
            }
            if (!$res && $info['is_update_info'] == 1) {
                $is_update_info = 1;
            }
            if ($res && $info['is_update_info'] == 0) {
                $is_update_info = 0;
            }
            if (!$res && $info['is_update_info'] == 0) {
                $is_update_info = 0;
            }
            $wx_info = [
                'nickname'   => $info['nickname'],
                'nickName'   => $info['nickname'],
                'sex'        => $info['sex'] == 0 ? null : $info['sex'],
                'headimgurl' => $info['headimgurl'],
                'avatarUrl'  => $info['headimgurl'],
                'birthday'   => empty($info['birthday']) ? null : date('Y-m-d', $info['birthday']),
                'email'      => $info['email'],
                'real_name'  => $info['real_name'],
                'is_update_info'  => $is_update_info,
            ];
            $wx_info['sex_text'] = isset($sex_text[$info['sex']]) ? $sex_text[$info['sex']] : '保密';
        }
        return $wx_info;
    }

    /**
     * 获取用户信息
     *
     * @Author djw
     * @param $openid
     * @return bool|mixed
     * @throws \Exception
     */
    private function getUserInfo($user)
    {
        $modelInfo = new UserRelYitianInfo;

//        $yitian = UserRelYitianInfo::where(['user_id'=>$user->id,'gm_id'=>$this->GMID])->first();
        $user_info['mobile'] = $user->mobile;
        $user_info['user_id'] = $user->id;
        $user_info['self_point'] = $modelInfo->getSelfPoint($user->mobile);

        $data = [
            'user_id' => $user->id,
            'status'  => 1
        ];
        $coupon = $this->coupon_service->getUserCouponList($data, false);
        $user_info['coupon'] = count($coupon);
        $goods_favorite = UserGoodsFavorite::where(['user_id'=>$user->id,'gm_id'=>$this->GMID])->count();
        $shop_favorite = UserShopFavorite::where(['user_id'=>$user->id,'gm_id'=>$this->GMID])->count();
        $user_info['favorite'] = $goods_favorite + $shop_favorite;
        $user_info['goods_favorite'] = $goods_favorite;
        $user_info['shop_favorite'] = $shop_favorite;

//        $card_type = [
//            8001 => 'V卡',
//            1001 => 'VIP卡',
//            1002 => 'VIP金卡',
//            1003 => 'VIP钻卡',
//        ];
//        $updateCard = false;
//        if (empty($yitian)) {
//            $updateCard = true;
//        }
//        if (!$updateCard)
//        {
//            if ( !$yitian->card_type_code && $yitian->yitian_id) $updateCard = true;
//        }
        //为空的话更新会员卡信息
//        if ($updateCard) {
//            $response = $this->yitiangroup_service->updateCardTypeCode($user->id, $user->mobile);
//            if ($response) {
//                // $user = UserAccount::where('id', $user->id)->first();
//                $yitian = UserRelYitianInfo::where(['user_id'=>$user->id,'gm_id'=>$this->GMID])->first();
//            }
//        }
//        if (!empty($yitian->yitian_point)) {
//            $user_info['point'] = $yitian->yitian_point;
//        }
//        if (!isset($user_info['point'])) {
//            $user_info['point'] = $this->yitiangroup_service->updateUserRewardTotal($user->mobile);
//        }
//        $user_info['default_gm_name'] = $this->isDefaultGm($user) ? $user->default_gm_name : $yitian->gm_name;
//        $user_info['card_type_code'] = $yitian->card_type_code??'';
//        $user_info['card_code'] = $yitian->card_code??'';
//
//        $cardModel = new YiTianUserCard;
//        $cardData = $cardModel->getCardInfo($this->GMID,$user_info['card_type_code']);
//        $user_info['card_type_text'] = $cardData['card_type_text'];
//        $user_info['card_img'] = $cardData['card_img'];
//        $user_info['card_name'] = $cardData['card_name'];
//        $user_info['gm_name'] = $cardData['gm_name'];
        return $user_info;
    }
    /**
     * [isDefaultGm 判断是否设置了默认]
     * @param  [type]  $user [description]
     * @return boolean       [description]
     */
    public function isDefaultGm($user)
    {
        if ($user->default_gm_id <= 0) {
            UserRelYitianInfo::where(['user_id'=>$user->id,'gm_id'=>$this->GMID])->update(['default'=>1]);
            return false;
        }
        return true;
    }

    /**
     * 获取微信小程序的openid
     *
     * @Author djw
     * @param $code
     * @param $appid
     * @param $appsecret
     * @return bool|mixed
     * @throws \Exception
     */
    private function getOpenId($code, $appid, $appsecret)
    {
        try {
            //获取session_key和openid
            $client = new \GuzzleHttp\Client();
            $api_url = 'https://api.weixin.qq.com/sns/jscode2session';
            $api_url = $api_url . '?appid=' . $appid;
            $api_url = $api_url . '&secret=' . $appsecret;
            $api_url = $api_url . '&js_code=' . $code;
            $api_url = $api_url . '&grant_type=authorization_code';
//            testLog($api_url);
            $respond = $client->request('GET', $api_url);
            if ($respond->getStatusCode() === 200) {
                $jscode_res = json_decode($respond->getBody()->getContents(), true);
                if (isset($jscode_res['errcode']) && $jscode_res['errcode'] !== 0) {
                    throw new \Exception($jscode_res['errmsg']);
                }
                // testLog($jscode_res);
                return $jscode_res;
            }
            throw new \Exception('请求失败');
        } catch (\Exception $exception) {
            throw new \Exception('无法获取openid：' . $exception->getMessage());
        }
    }

    /**
     * 微信小程序数据解密
     *
     * @Author djw
     * @param $appid
     * @param $session_key
     * @param $encryptedData
     * @param $iv
     * @return mixed
     * @throws \Exception
     */
    private function decryptData($appid, $session_key, $encryptedData, $iv)
    {
        try {
            //用户信息解密
            $wechat = new \ShopEM\Services\WeChatMini\WXBizDataCrypt($appid, $session_key);
            $errCode = $wechat->decryptData($encryptedData, $iv, $data);

            $heavy_data = $data;//引用值赋值给变量
            $en_res = json_decode($heavy_data, true);

            if ($errCode == 0) {
                return $en_res;
            } else {
                throw new \Exception($errCode);
            }
        } catch (\Exception $exception) {
            throw new \Exception('无法解密：' . $exception->getMessage());
        }
    }

    /**
     * 获取token
     *
     * @Author djw
     * @param $user
     * @return bool|mixed
     */
    private function getToken($user)
    {
        $u = explode('_', $user->login_account);
        $password = 'Hyflsc@' . substr($u[0], 2);
        $token = $this->authenticate('shop_users', $user->login_account, $password);
        if (!$token) {
            return false;
        }
        //记录登录日志
        $event = new UserLoginEvent();     //自定义
        event($event->broadcastOn($user->id, trim($user->login_account), '', ''));
        //益田的memberId为空时检查益田里是否有该账号，没有则创建
        $filter = ['mobile'=>$user->mobile,'gm_id'=>$this->GMID];
        $yitian = UserRelYitianInfo::where($filter)->first();

        $updateCard = false;
        if (empty($yitian)) {
            $updateCard = true;
            $this->yitiangroup_service->createRelYitian($user->mobile,$user->id);
            $yitian = UserRelYitianInfo::where($filter)->first();
        }
        if (!$yitian->yitian_id) {
            $respon = $this->yitiangroup_service->signUpNotExistedMember($user->mobile);
            if ($respon) {
                $updateData = ['yitian_id' => $respon['member_id'],'new_yitian_user' => $respon['not_existed']];
                UserRelYitianInfo::where($filter)->update($updateData);
                // $user->update(['yitian_id' => $respon['member_id'], 'new_yitian_user' => $respon['not_existed']]);
            }
        }
        return $token;
    }

    /**
     * 创建或更新微用户微信信息
     *
     * @Author djw
     * @param $en_res
     */
    private function save_wx_info($en_res,$appid)
    {
//        $this->getMiniDeploy($appid,$appsecret);
        $res = [
            'openid'     => $en_res['openId'],
            'nickname'   => $en_res['nickName'],
            'province'   => $en_res['province'],
            'city'       => $en_res['city'],
            'country'    => $en_res['country'],
            'headimgurl' => $en_res['avatarUrl'],
            'unionId'    => $en_res['unionId']??'',
            'sex'        => (int)$en_res['gender'],
            'user_id'    => 0,
            'appid'      => $appid,
        ];
        $model = new \ShopEM\Models\WxUserinfo();
        $info = $model->where('openid', $res['openid'])->first();
        if ($info) {
            $s = 0;
            if ($res['nickname'] && empty($info->nickname)) {
                $info->nickname = $res['nickname'];
                $s = 1;
            }
            if ($res['province'] && empty($info->province)) {
                $info->province = $res['province'];
                $s = 1;
            }
            if ($res['city'] && empty($info->city)) {
                $info->city = $res['city'];
                $s = 1;
            }
            if ($res['country'] && empty($info->country)) {
                $info->country = $res['country'];
                $s = 1;
            }
            if ($res['headimgurl'] && empty($info->headimgurl)) {
                $info->headimgurl = $res['headimgurl'];
                $s = 1;
            }
            if ($res['sex'] && empty($info->sex)) {
                $info->sex = $res['sex'];
                $s = 1;
            }
            if ($res['unionId'] && empty($info->unionId)) {
                $info->unionId = $res['unionId'];
                $s = 1;
            }
            if ($s) {
                $info->save();
            }
        } else {
            $info=$model->create($res);
        }
        return $info;
    }


    //状态转换
    private $tradeStatus = [
        1 => 'WAIT_BUYER_PAY',    // 已下单等待付款
        2 => 'WAIT_SELLER_SEND_GOODS',    // 已付款等待发货
        3 => 'WAIT_BUYER_CONFIRM_GOODS',  // 已发货等待确认收货
        4 => 'TRADE_FINISHED',    // 已完成
        5 => 'TRADE_FINISHED',    // 已完成并且待评价
    ];


    /**
     * 订单数据统计
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function GetUserTradeCount($id)
    {
        $return['by_unpay'] = $this->getTradeCount(1,$id);
        $return['by_send'] = $this->getTradeCount(2,$id);
        $return['by_confirm'] = $this->getTradeCount(3,$id);
        $return['by_evaluate'] = $this->getTradeCount(5,$id);

        return  $return;
    }


    public function getTradeCount($status,$user_id)
    {
        //如果status传的是数字的话，需要转换成对应的状态值
        if ($status) {
            if (isset($this->tradeStatus[$status])) {
                $request['status'] = $this->tradeStatus[$status];
            }else{
                return false;
            }
        }
        $request['user_id']=$user_id;

        $model = new Trade();

        if($status == 5) {
            $model = $model->where('buyer_rate', 0);
        }

        $model = $model->select('trades.*')->leftJoin('trade_cancels', 'trade_cancels.tid', '=', 'trades.tid')->where( function ($query){
            $query->whereNull('cancel_id')->orWhereIn('trade_cancels.process', ['1','3']);
        });
        $model = $model->leftJoin('trade_aftersales', 'trade_aftersales.tid', '=', 'trades.tid')->where( function ($query){
            $query->whereNull('aftersales_bn')->orWhereIn('trade_aftersales.status', ['2','3']);
        });

        $count = $model->where(['trades.status'=>$request['status'],'trades.user_id'=>$request['user_id']])->count();

        return $count;
    }

    public function pushPointChangeMessage()
    {
        $obj = new WXMessage();
        $mobile = request('mobile',0);
        $user = UserAccount::where('mobile',$mobile)->first();
        return $obj->pointChangeMessage([
            'username'=>$user->login_account,
            'change'=>request('change',0),
            'point'=>request('point',0),
            'time'=>request('time',0),
            'reason'=>request('reason',0)
        ],$user->openid);
    }

}
