<?php
/**
 * @Filename YitianGroupServices
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-04-15 09:46:01
 * @version 	V1.0
 */
namespace ShopEM\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserRelYitianInfo;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\UserPointLog;
use ShopEM\Jobs\UpdateCrmUserInfo;

class YitianGroupServices {

    protected $client;
    protected $client_id;
    protected $client_secret;
    protected $ent_id;
    protected $gmId;
    protected $AppId;
    protected $Secret;
    protected $appCode;
    protected $corpCode;
    protected $orgCode;
    protected $cardTypeCode;

    protected $ServiceSataus = true;
    protected $expiresAt;
    public function __construct($gmId=1)
    {
        $this->gmId = $gmId;
        $platform = $this->getPlatform($gmId);
        $this->serviceDeploy($gmId,$platform);
        //过期时间
        $this->expiresAt = \Carbon\Carbon::now()->addMinutes(5);
    }
    /**
     * [serviceDeploy 全局服务配置]
     * @param  [type] $gmId     [description]
     * @param  [type] $platform [description]
     * @return [type]           [description]
     */
    public function serviceDeploy($gmId,$platform)
    {
        if(empty($platform) && $gmId != 1)
        {
            $this->ServiceSataus = false;
            return true;
        } 
        if (empty($platform) && $gmId == 1) 
        {
            //正式环境
            $this->client = new client(['base_uri' => env('YITIAN_BASE_URI')]);
            $this->AppId = env('YITIAN_APP_ID');
            $this->Secret = env('YITIAN_SECRET');
            $this->appCode = env('YITIAN_APP_CODE');
            $this->corpCode = env('YITIAN_CORP_CODE');
            $this->orgCode = env('YITIAN_ORG_CODE');
            $this->cardTypeCode = '8001';
        }
        if ($platform) 
        {
            $this->client = new client(['base_uri' => $platform->base_uri]);
            $this->AppId = $platform->app_id ;
            $this->Secret = $platform->secret ;
            $this->appCode = $platform->app_code ;
            $this->corpCode = $platform->corp_code ;
            $this->orgCode = $platform->org_code ;
            $this->cardTypeCode = $platform->default_type_code ;
        }
    }

    public function testApi()
    {
        //测试环境
        //http://syapi.yitiangroup.com:8080/api/
        /*$this->client = new client(['base_uri' => 'http://ceshi.yitiangroup.com:8080/api/']);
        $this->AppId = 'apixssc101';
        $this->Secret = '6EDBA28470F655938FE71E149AB789A1';
        $this->appCode = 'apixssc101';
        $this->corpCode = 101;
        $this->orgCode = 1110;*/
    }

    public function getPlatform($gmId)
    {
        $hour = \Carbon\Carbon::now()->addHour();
        $gm_cache_key = 'yitan_gm_platform_id_'.$gmId;
        // $gmData = GmPlatform::find($gmId);
        $gmData = Cache::remember($gm_cache_key, $hour, function () use ($gmId){
            return GmPlatform::find($gmId);
        });
        $error_text = '';
        if (empty($gmData)) $error_text = '该项目ID-'.$gmId.'还没创建';
        if ($gmData && empty($gmData->base_uri)) $error_text = '该项目:'.$gmData->platform_name.'还未进行配置';
        if ($gmData && empty($gmData->default_type_code)) $error_text = '该项目:'.$gmData->platform_name.'还未进行会员等级配置';

        if (!empty($error_text)) 
        {
            Cache::forget($gm_cache_key);
            $this->errorLog($error_text);
            return [];
        }
        return $gmData;
    }

    /**
     * [createRelYitian 创建新的益田多项目关联]
     * @param  [type] $mobile  [description]
     * @param  [type] $user_id [description]
     * @param  [type] $reset   [注册进入不需要跑 signUpNotExistedMember]
     * @return [type]          [description]
     */
    public function createRelYitian($mobile,$user_id,$reset=false)
    {
        $data['mobile'] = $mobile;
        $data['user_id'] = $user_id;
        $data['gm_id'] = $this->gmId;
        $doesntExist = UserRelYitianInfo::where($data)->doesntExist();
        if ($doesntExist) {
            $user = UserRelYitianInfo::create($data);
            if ($reset) 
            {
                $respon = $this->signUpNotExistedMember($mobile);
                if ($respon) {
                    $update['yitian_id'] = $respon['member_id'];
                    $update['new_yitian_user'] = $respon['not_existed'];
                }
                UserRelYitianInfo::where('id',$user->id)->update($update);
            }
            return $user->id;
        }
        return false;
    }

    /**
     * 获取token
     *
     * @Author djw
     * @return mixed
     * pass
     */
    public function getToken() {
        $seconds = \Carbon\Carbon::now()->addMinute(100);
        $cache_key = 'yapi_token_'.$this->gmId;
        $token = Cache::remember($cache_key, $seconds, function (){
            $api = 'platform/token';
            $api .= '?appid='.$this->AppId;
            $api .= '&secret='.$this->Secret;
            $respond = $this->client->request('GET', $api);
            if ($respond->getStatusCode() === 200) {
                $result = $respond->getBody()->getContents();
                if (!is_null(json_decode($result))) {
                    $result = json_decode($result, true);
                    if (isset($result['Result']) && $result['Result']['HasError'] == true) {
                        throw new \Exception('CRM:token获取失败,'.$result['Result']['ErrorMessage']);
                    }
                    return $result['token'];
                }
                return $result;
            }
            return false;
        });

        if (!$token) {
            Cache::forget($cache_key);
            $this->errorLog('无法获取token');
        }
        return $token;
    }

    /**
     * 如果账号不存在则创建账号
     *
     * @Author djw
     * @param $mobile
     * @return bool|mixed|string
     * check
     */
    public function signUpNotExistedMember($mobile){
        $not_existed = 0;
        try{
//            return false;
            $memberID = $this->checkExistingMember($mobile);
            if ($memberID === false) {
                $not_existed = 1;
                $memberID = $this->memberSignUp($mobile);
            }
        }catch (\Exception $e) {
            return false;
        }
        return ['member_id' => $memberID, 'not_existed' => $not_existed];
    }

    /**
     * 会员检查
     *
     * @Author djw
     * @param $mobile
     * @return bool|mixed
     * ok
     */
    public function checkExistingMember($mobile) {

        $api = 'member/CheckExistingMember';
        $body['data'] = [
            'checkType' => 1,
            'checkCode' => $this->corpCode . $mobile,
        ];
        $data = [
            'json' => $body
        ];
        $result = $this->request($api, $data);
        if (isset($result['Result']) && $result['Result']['HasError'] == true) {
            $info = '手机号码' . $mobile . '检查是否存在时出现错误，原因：' . $result['Result']['ErrorMessage'];
            $this->errorLog($info);
            throw new \Exception($info);
        }
        if ($result['Data']['existed'] == 0) {
            return false;
        }

        return $result['Data']['memberID'];
    }

    /**
     * 会员注册
     *
     * @Author djw
     * @param $mobile
     * @return bool|mixed
     * pass
     */
    public function memberSignUp($mobile) {

        $api = 'member/MemberSignUp';
        $body['data'] = [
            'mobileNo' => $mobile,
            'cardTypeCode' => $this->cardTypeCode,
        ];
        $data = [
            'json' => $body
        ];
        $result = $this->request($api, $data);
        if (isset($result['Result']) && $result['Result']['HasError'] == true) {
            $info = '手机号码' . $mobile . '同步注册失败，原因：' . $result['Result']['ErrorMessage'];
            $this->errorLog($info);
            throw new \Exception($info);
        }
        return $result['Data']['memberId'];
    }

    /**
     * 会员可用积分查询
     *
     * @Author djw
     * @param $mobile
     * @return bool|mixed
     * pass
     */
    public function rewardTotal($mobile) {

        $cache_key = 'YITIAN_POINT_USER_MOBILE_'.$mobile.'_GMID_'.$this->gmId;
        if (Cache::has($cache_key)) {
            return Cache::get($cache_key);
        }
        $api = 'reward/RewardTotal';
        $body['data'] = [
            'identifierType' => 3,
            'identifierCode' => $this->corpCode . $mobile,

        ];
        $data = [
            'json' => $body
        ];
        $result = $this->request($api, $data);
        if (isset($result['Result']) && $result['Result']['HasError'] == true) {
            $info = '手机号码' . $mobile . '的积分查询失败，原因：' . $result['Result']['ErrorMessage'];
            $this->errorLog($info);
            throw new \Exception($info);
        }
        Cache::put($cache_key,$result,$this->expiresAt);

        return $result;
    }

    /**
     * 更新会员积分
     *
     * @Author djw
     * @param $mobile
     * @param $user_id
     * @return bool
     * ok
     */
    public function updateUserRewardTotal($mobile) {
        try{
            //判断是否已经关联迁移
            $newRel = true;
            // $userInfo = UserAccount::select('yitian_id')->where('mobile', $mobile)->first();
            $filter['mobile'] = $mobile;
            $filter['gm_id'] = $this->gmId;
            $userInfo = UserRelYitianInfo::select('yitian_id')->where($filter)->first();
            if ($this->gmId == 1 && (empty($userInfo) || !$userInfo->yitian_id) ) 
            {
                $newRel = false;
                $userInfo = UserAccount::select('yitian_id','id')->where('mobile', $mobile)->first();
                $this->createRelYitian($mobile,$userInfo->id);
            }
            //益田的memberId为空时直接返回0积分
            if (!$userInfo->yitian_id && !$userInfo->yitian_card_id) {
                return 0;
            }
            $result = $this->rewardTotal($mobile);
            $point = $result['Data']['balance'] ?? 0;
            UserRelYitianInfo::where($filter)->update(['yitian_point' => $point]);

        }catch (\Exception $e) {
            return 0;
        }
        return $point;
    }

    /**
     * 会员查询
     *
     * @Author djw
     * @param $mobile
     * @return bool|mixed
     * pass
     */
    public function memberInfo($mobile) {
        
        $api = 'member/MemberInfo';
        $body['data'] = [
            'identifierType' => 3,
            'identifierCode' => $this->corpCode . $mobile,

        ];
        $data = [
            'json' => $body
        ];
        $result = $this->request($api, $data);
        if (isset($result['Result']) && $result['Result']['HasError'] == true) {
            $info = '手机号码' . $mobile . '的会员查询失败，原因：' . $result['Result']['ErrorMessage'];
            $this->errorLog($info);
            return false;
        }
        
        return $result;
    }

    /**
     * 会员积分调整
     *
     * @Author djw
     * @param $mobile
     * @return bool|mixed
     * pass
     */
    public function pointAdjustment($mobile, $point, $remark, $log_id = 0) {

        $api = 'reward/PointAdjustment';
        $body['data'] = [
            'queryType' => 3,
            'code' => $this->corpCode . $mobile,
            'storeCode' => null, //商铺编码
            'point' => $point, //调整的值
            'logType' => 21, //101:签到送积分 33:储值卡充值 13:营销 4:抽奖 8:手工操作 30:生日送积分 3:兑换 32:活动积分 15:购卡续卡 17:推荐送积分 10:互动  6:转入 9:系统清零 11:活动 2:交易 14:兑换撤销 12:促销 5:转移 7:退货 31:礼卷积分 1:初始化 15:积分续卡
            'remark' => $remark, //备注

        ];
        $data = [
            'json' => $body
        ];
        $result = $this->request($api, $data);
        if (isset($result['Result']) && $result['Result']['HasError'] == true) {
            $msg = $this->errorType($result['Result']['ErrorMessage']);
            $info = '会员' . $mobile . '的积分调整失败，原因：' . $msg;
            $this->errorLog($info);
            UserPointLog::where('id',$log_id)->update(['push_crm'=>3,'crm_msg'=>$info]);
            throw new \Exception($msg);
        }
        UserPointLog::where('id',$log_id)->update(['push_crm'=>2]);
        return $result;
    }


     // 错误提示
    private function errorType($error_code){
        $arr = [
            'Insufficient point'=>'积分不足，去逛逛获取积分'
        ];
        return isset($arr[$error_code]) ? $arr[$error_code]  : $error_code;
    }

    /**
     * 更新益田的积分
     *
     * @Author djw
     * @param $params
     * @return bool
     * ok
     */
    public function updateUserYitianPoint($params)
    {
        switch ($params['type']) {
            case "obtain":
                $params['modify_point'] = abs($params['num']);
                break;
            case "consume":
                $params['modify_point'] = 0 - $params['num'];
                break;
        }
        // $userInfo = UserAccount::select('mobile', 'yitian_id')->where('id', $params['user_id'])->first();
        $filter['user_id'] = $params['user_id'];
        $filter['gm_id'] = $this->gmId;
        $userInfo = UserRelYitianInfo::select('yitian_id','mobile')->where($filter)->first();
        if ($this->gmId == 1 && (empty($userInfo) || !$userInfo->yitian_id)) 
        {
            $userInfo = UserAccount::select('yitian_id','mobile','id')->where('id', $params['user_id'])->first();
            $this->createRelYitian($userInfo->mobile,$userInfo->id);
        }
        //益田的memberId为空时直接返回false
        if (!$userInfo->yitian_id) {
            return false;
        }

        $paramsPoint = array(
            'user_id' => $params['user_id'],
            'mobile' => $userInfo['mobile'],
            'modify_remark' => $params['remark'],
            'modify_point' => $params['modify_point'],
            'behavior' => $params['behavior'],
            'log_type'  => $params['log_type']??'normal',
            'log_obj'   => $params['log_obj']??'',
        );
        if (isset($params['order'])) {
            $paramsPoint['order'] = $params['order'];
        }
        $result = $this->changePoint($paramsPoint);
        return $result;
    }

    /**
     * 推送CRM订单信息
     *
     * @Author czb
     * @param $params
     * @return bool
     * ok
     */
    public function tradePushCrm($params)
    {
        $api = 'Sale/sales';
        $body['data'] = [
            'online'           => 1,
            'transTime'        => $params['transTime'],
            'storeCode'        => $params['storeCode'] ?? 'L1-KFT-02',
            'cardCode'         => $params['cardCode'],
            'receiptNo'        => $params['receiptNo'],    //订单号
            'payableAmount'    => $params['payableAmount'],
            'netAmount'        => $params['netAmount'],
            'discountAmount'   => $params['discountAmount'],
            'getPointAmount'   => $params['getPointAmount'],
        ];
        $data = [
            'json' => $body
        ];
        $result = $this->request($api, $data);
        if (isset($result['Result']) && $result['Result']['HasError'] == true) {
            $exception = '订单号:'.$params['receiptNo'].$result['Result']['ErrorMessage'];
            if($params['receiptNo'])
            {
                $error['push_crm'] = 3;
                $error['crm_msg'] = $result['Result']['ErrorMessage'];
                if (!isset($params['log_type']) || $params['log_type'] == 'trade') 
                {
                    DB::table('trades')->where('tid',$params['receiptNo'])->update($error);
                }
                UserPointLog::where('id',$params['log_id'])->update($error);
            }
            throw new \Exception($exception);
        }

        return $result;
    }

    /**
     * 从CRM检查退货
     *
     * @Author czb
     * @param $params
     * @return bool
     * ok
     */
    public function returnedPurchaseCheck($params, $uid)
    {
        $api = 'sale/MallItemVoidCheck';
        $body['data'] = [
            'storeCode'           => $params['storeCode'] ?? 'L1-KFT-02',            //门店id
            'originTransDate'     => $params['originTransDate'],      //原交易日期
            'receiptno'           => $params['receiptno'],            //新退货单号
            'org_receiptno'       => $params['org_receiptno'],        //原小票号
            'returnamount'        => $params['returnamount'],         //退货金额
            'forcereturnoption'   => $params['forcereturnoption'],    //系统参数(forcereturnoption=1),则允许在界面上选择强制退货，否则提示积分不足不允许退货
            'receiptDate'         => $params['receiptDate'],          //退货日期
        ];
        $data = [
            'json' => $body
        ];
        $result = $this->request($api, $data);
        if (isset($result['Result']) && $result['Result']['HasError'] == true) {
            if ($result['Result']['ErrorCode']  == 1) {
                return true;
            }
            $data = [
                'uid'          => $uid,
                'api'          => $api,
                'params'       => json_encode($data),
                'fail_reason'  => $result['Result']['ErrorMessage'],
            ];
            DB::table('crm_fail')->insert($data);
        }
        return $result;
    }
    /**
     * 更新crm会员资料
     *
     * @Author czb
     * @param $params
     * @return bool
     * ok
     */
    public function updateCrmUserInfo($params)
    {
        $user_id = $params['user_id'];
        unset($params['user_id']);
        $filter = ['user_id'=>$user_id,'gm_id'=>$this->gmId];
        $userYitian = UserRelYitianInfo::where($filter)->select('mobile','card_code','yitian_id')->first()->toArray();
        $params['mobileNo'] = $userYitian['mobile'] ? $this->corpCode.$userYitian['mobile'] : '';
        $params['cardcode'] = $userYitian['card_code'] ?? '';
        $params['memberID'] = $userYitian['yitian_id'] ?? '';

        $api = 'member/UpdateMemberInfo';
        $body['data'] = [
            'fullName'       => $params['real_name'] ?? '',
            'nickName'       => $params['nick_name'] ?? '',
            'gender'         => $params['gender'] ?? 0,
            'dateOfBirth'    => $params['dateOfBirth'] ?? '',
            'cardcode'       => $params['cardcode'],
        ];
        $data = [
            'json' => $body
        ];
        $result = $this->request($api, $data);
        if (isset($result['Result']) && $result['Result']['HasError'] == true) {
            throw new \Exception('用户id:'.$params['mobileNo'].$result['Result']['ErrorMessage']);
        }
        return true;
    }
    /**
     * 推送CRM退货信息
     *
     * @Author czb
     * @param $params
     * @return bool
     * ok
     */
    public function refundPushCrm($params)
    {
        $api = 'sale/MallItemVoid';
        $body['data'] = [
            'storeCode'           => $params['storeCode'],            //门店id
            // 'cashierID'           => $params['cashierID'],            //收银员ID
            'originTransDate'     => $params['originTransDate'],      //原交易日期
            'receiptno'           => $params['receiptno'],            //新退货单号
            'org_receiptno'       => $params['org_receiptno'],        //原小票号
            'returnamount'        => $params['returnamount'],         //退货金额
            'forcereturnoption'   => $params['forcereturnoption'],    //系统参数(forcereturnoption=1),则允许在界面上选择强制退货，否则提示积分不足不允许退货
            'receiptDate'         => $params['receiptDate'],          //退货日期
        ];
        $data = [
            'json' => $body
        ];
        $result = $this->request($api, $data);
        if (isset($result['Result']) && $result['Result']['HasError'] == true) {
            $error['push_crm'] = 3;
            $error['crm_msg'] = $result['Result']['ErrorMessage'];
            UserPointLog::where('id',$params['log_id'])->update($error);
            $this->errorLog('订单号:'.$params['org_receiptno'].$result['Result']['ErrorMessage']);
            return false;
            throw new \Exception('订单号:'.$params['org_receiptno'].$result['Result']['ErrorMessage']);
        }

        $this->errorLog(json_encode($result));
        return $result;
    }


    /**
     * @brief 积分改变
     *
     * @param $params
     *
     * @return
     * pass
     */
    public function changePoint($params)
    {
        if(!$params['user_id'])
        {
            throw new \Exception('会员参数错误');
        }
        if(!$params['modify_point'])
        {
            throw new \Exception('会员积分参数错误');
        }

        DB::beginTransaction();
        try{
            $data['push_crm'] = 1;
            $data['gm_id'] = $this->gmId;
            $data['user_id'] = $params['user_id'];
            $data['remark'] = isset($params['modify_remark']) && $params['modify_remark'] ? $params['modify_remark'] : "平台修改";
            $data['point'] = abs($params['modify_point']);
            if($params['modify_point'] >= 0)
            {
                $data['behavior_type'] = "obtain";
                $data['behavior'] = isset($params['behavior']) && $params['behavior'] ? $params['behavior'] : "平台手动增加积分";
            }
            elseif($params['modify_point'] < 0)
            {
                $data['behavior_type'] = "consume";
                $data['behavior'] = isset($params['behavior']) && $params['behavior'] ? $params['behavior'] : "平台手动扣减积分";
            }
            $data['log_type'] = $params['log_type']??'normal';
            if (!empty($params['log_obj'])) $data['log_obj'] = $params['log_obj'];

            $log = UserPointLog::create($data);
            if(!$log)
            {
                throw new \Exception('会员积分值明细记录失败');
            }

            //同步更新益田里的积分
            if (isset($params['order'])) {
                $goods_name = array_column($params['order'], 'goods_name');
                $remark = implode($goods_name, '\\');
            } else {
                $remark = isset($params['modify_remark']) && $params['modify_remark'] ? $params['modify_remark'] : "平台修改";
            }
            $result = $this->pointAdjustment($params['mobile'], $params['modify_point'], $remark, $log->id);
    
            //把当前积分存到数据库里
            $point = $result['Data'] ?? 0;

            $filter['mobile'] = $params['mobile'];
            $filter['gm_id'] = $this->gmId;
            UserRelYitianInfo::where($filter)->update(['yitian_point' => $point]);
            // UserAccount::where('mobile', $params['mobile'])->update(['yitian_point' => $point]);
            $gm_name = GmPlatform::where('gm_id',$data['gm_id'])->value('platform_name');
            $dataMessage = [
                'mobile'    => $params['mobile'],
                'gm_name'   => $gm_name,
                'change'    => $params['modify_point'],
                'point'     => $point,
                'time'      => date('Y-m-d H:i:s'),
                'reason'    => $data['behavior'],
            ];
            (new \ShopEM\Models\WechatMessage())->pointChangeMessage($dataMessage);


            DB::commit();
            return $point;
        }catch(\LogicException $e){
            DB::rollback();
            throw new \Exception($e->getMessage());
            return false;
        }
    }
    /**
     * @brief 订单积分改变
     *
     * @param $params
     *
     * @return
     * pass
     */
    public function tradeChangePoint($params)
    {
        if(!$params['user_id'])
        {
            throw new \Exception('会员参数错误');
        }
        if(!$params['modify_point'])
        {
            throw new \Exception('会员积分参数错误');
        }

        DB::beginTransaction();
        try{
            $data['push_crm'] = 1;
            $data['gm_id'] = $this->gmId;
            $data['user_id'] = $params['user_id'];
            $data['remark'] = isset($params['modify_remark']) && $params['modify_remark'] ? $params['modify_remark'] : "平台修改";
            $data['point'] = abs($params['modify_point']);
            if($params['modify_point'] >= 0)
            {
                $data['behavior_type'] = "obtain";
                $data['behavior'] = isset($params['behavior']) && $params['behavior'] ? $params['behavior'] : "平台手动增加积分";
            }
            elseif($params['modify_point'] < 0)
            {
                $data['behavior_type'] = "consume";
                $data['behavior'] = isset($params['behavior']) && $params['behavior'] ? $params['behavior'] : "平台手动扣减积分";
            }
            $log = UserPointLog::create($data);
            if(!$log)
            {
                // return false;
                throw new \Exception('会员积分值明细记录失败');
            }

            DB::commit();
            return $log->id;
        }catch(\LogicException $e){
            DB::rollback();
            // throw new \Exception($e->getMessage());
            return false;
        }
    }
    /**
     * 批量更新用户的会员卡信息
     *
     * @Author djw
     * @return bool
     * ok
     */
    public function updateUsersCardTypeCode()
    {
        // $users = UserAccount::whereNotNull('yitian_id')->where('gm_id',$this->gmId)->get();
        $users = UserRelYitianInfo::whereNotNull('yitian_id')->where('gm_id',$this->gmId)->get();
        if ($users) {
            foreach ($users as $user) {
                if ($user['mobile']) {
                    $data['user_id']= $user['user_id'];
                    $data['mobile'] = $user['mobile'];
                    $data['gm_id']  = $user['gm_id'];
                    $data['updateCardTypeCode'] = true;
                    UpdateCrmUserInfo::dispatch($data);
                    // $this->updateCardTypeCode($user['id'], $user['mobile']);
                }
            }
        }
        return true;
    }

    /**
     * 更新用户的会员卡信息
     *
     * @Author djw
     * @return bool
     * ok
     */
    public function updateCardTypeCode($user_id, $user_mobile)
    {
        $card_type_code = $card_id = $card_code = '';

        $yitian_user = $this->memberInfo($user_mobile);
        if (!$yitian_user) {
            return false;
        }
        $yitian_id = $yitian_user['Data']['MemberID']??'';
        foreach ($yitian_user['Data']['CardinfoList'] as $card) {
            if ($card['IsMainCard'] == 1) {
                $card_type_code = $card['CardTypeCode'];
                $card_code = $card['CardCode'];
                $card_id = $card['CardID'];
                $card_point = $card['RewardPoints']??0;
            }
        }
        $data = [
            'yitian_id' => $yitian_id,
            'card_type_code' => $card_type_code,
            'card_code' => $card_code,
            'yitian_card_id' => $card_id,
            'yitian_point' => $card_point,
        ];
        $filter['user_id'] = $user_id;
        $filter['gm_id'] = $this->gmId;
        UserRelYitianInfo::where($filter)->update($data);
        // UserAccount::where('id', $user_id)->update($data);
        return true;
    }


    /**
     * [allMemberInfo 通过手机查询所有会员信息]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function allMemberInfo($mobileNo)
    {
        $api = 'member/AllMemberInfo';
        $body['data'] = [
            'mobileNo'  => $mobileNo,
        ];
        $data = [
            'json' => $body
        ];
        $result = $this->request($api, $data);
        return $result;
    }
    /**
     * 积分转移——把 A 项目积分转移至 B，通过卡 ID 入参实现
     */
    public function pointsTransfer($params)
    {
        $api = 'reward/PointsTransfer';
        $body['data'] = [
            'sourceCardId'  => $params['sourceCardId'],  //原始cardid
            'targetCardId'  => $params['targetCardId'],  //目标cardid
            'points'        => $params['points'],        //转换的积分
            'scale'         => $params['scale'],         //比例
            'remarks'       => $params['remarks'],       //备注
        ];
        $data = [
            'json' => $body
        ];
        $result = $this->request($api, $data);
        $this->errorLog(['pointsTransfer'=>$result]);
        if (isset($result['Result']) && $result['Result']['HasError'] == true) {
            $error = $result['Result']['ErrorMessage'];
            // $this->errorLog($error);
            throw new \Exception($error);
            // return false;
        }
        return $result['Data'];
    }

    public function masterStoreList()
    {
        $api = 'master/storelist';
        $body['data'] = [
            'pageIndex' => 0,
            'pageSize'  => 0,
            'orderField'=> null,
            'isAsc'     => false,
            'mallCode'  => $this->corpCode,
            'enabled'   => 1,
        ];
        $data = [
            'json' => $body
        ];
        $result = $this->request($api, $data);
        if (isset($result['Result']) && $result['Result']['HasError'] == true) {
            $error = $result['Result']['ErrorMessage'];
            $this->errorLog($error);
            // throw new \Exception($error);
            return false;
        }
        return $result['Data'];
    } 

    /**
     * 错误日志记录
     *
     * @Author moocde <mo@mocode.cn>
     * @param $info
     */
    public function errorLog($info)
    {
        $filename = storage_path('logs/' . 'yapi-errorlog-' . date('Y-m-d') . '.log');
        file_put_contents($filename, '[' . date('Y-m-d H:i:s') . '] ' . print_r($info, true) . "\n", FILE_APPEND);
    }

    /**
     * 请求接口
     *
     * @Author djw
     * @param $api
     * @param $data
     * @return bool|mixed
     */
    public function request($api, $data)
    {
        if (!$this->ServiceSataus) {
            return false;
        }
        $cache_key = 'yapi_token_'.$this->gmId;
        try {
            $api_url = $this->setApiToken($api);

            //设置请求头
            if (!isset($data['headers'])) {
                $data['headers'] = [
                    'Content-Type' => 'application/json'
                ];
            }
            //设置shared
            if (!isset($data['json']['shared'])) {
                $data['json']['shared'] = $this->getShared();
            }
            // testLog(['api_url'=>$api_url,'data'=>$data]);
            $result = false;
            $respond = $this->client->request('POST', $api_url, $data);
            if ($respond->getStatusCode() === 200) {
                $result = json_decode($respond->getBody()->getContents(), true);

                //如果令牌过期，刷新令牌并重新请求
                if (isset($result['Result']) && $result['Result']['ErrorCode'] == 401 && $result['Result']['ErrorMessage'] == 'Invalid Token or expired.') {
                    Cache::forget($cache_key);
                    $result = $this->request($api, $data);
                }
            }
        }
        catch(\Exception $e)
        {
            return false;
        }
        return $result;
    }

    /**
     * 获取shared
     *
     * @Author djw
     * @return mixed
     */
    private function getShared() {
        return [
            'appCode' => $this->appCode,
            'corpCode' => $this->corpCode,
            'orgCode' => $this->orgCode,
        ];
    }

    /**
     * 为api加上token
     *
     * @Author djw
     * @param $old_url
     */
    private function setApiToken($api){
        //检查链接中是否存在 ?
        $check = strpos($api, '?');
        //如果存在 ?
        if($check !== false)
        {
            if(substr($api, $check+1) != '')
            {
                //如果有参数
                $api .= '&';
            }
        }
        else //如果不存在 ?
        {
            $api .= '?';
        }
        $token = $this->getToken();
        return $api . 'token=' . $token;
    }



    /**
     * 礼券领取（会员领券购券）接口
     * @Author djw
     * @return mixed
     */
    public function VIPTicketSchemeAction($param)
    {
        try {
            $url = 'k3cloud/services/XMXHVIPCardWebService.asmx/VIPTicketSchemeAction';

            $post_data_lstParams = Array(
                'strParams' => Array(
                    "VIPNumber"      => $param['vipNumber'],
                    "MobilNo"      => $param['mobile'],
                    "SchemeId"      => $param['scheme_id'],
                    "SettleAmt"      => $param['settle_amt'] ?? 0,
                    "SettleType"      => $param['settle_type'] ?? '',
                )
            );
            $res = $this->HttpPost($url, $post_data_lstParams, "VIPTicketSchemeAction", true);
        } catch (\Exception $exception) {
            throw new \Exception('领取失败');
        }

        return $res;
    }

}
