<?php
/**
 * Created by lanlnk
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-02-25
 * @Time: 14:16
 */


namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\ApplyPromoter;
use ShopEM\Models\Article;
use ShopEM\Models\Config;
use ShopEM\Models\GoodsSpreadLogs;
use ShopEM\Models\GoodsSpreadQrs;
use ShopEM\Models\PartnerRelatedLog;
use ShopEM\Models\RelatedLogs;
use ShopEM\Models\TradeEstimates;
use ShopEM\Models\TradeRewards;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserDeposit;
use ShopEM\Models\UserDepositCash;
use ShopEM\Models\UserDepositLog;
use ShopEM\Models\UserXinpollInfo;
use ShopEM\Models\WxUserinfo;
use ShopEM\Repositories\ConfigRepository;
use ShopEM\Repositories\DepartmentRepository;
use ShopEM\Repositories\GoodsSpreadListsRepository;
use ShopEM\Repositories\UserDepositCashesListRepository;
use ShopEM\Repositories\TradeEstimatesListsRepository;
use ShopEM\Repositories\EstimatesOrderRepository;
use ShopEM\Repositories\RelatedLogsListRepository;
use ShopEM\Repositories\ApplyPromoterRepository;
use ShopEM\Repositories\PromoterListRepository;
use Illuminate\Support\Facades\DB;
use ShopEM\Services\TradeService;
use ShopEM\Services\WeChatMini\CreateQrService;
use  ShopEM\Http\Requests\Shop\PromoterRequest;
use  ShopEM\Http\Requests\Shop\ApplyCashOutRequest;
use  ShopEM\Http\Requests\Shop\CheckerExamineRequest;
use  ShopEM\Http\Requests\Shop\CreatPartnerWxMiniQrRequest;


class PromoterController extends BaseController
{
    /**
     * 判断是否会员状态
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyCheck(Request $request)
    {
        $user_id = $this->user->id;

        $infos = [];
        $state = UserAccount::where('id', $user_id)->select('is_promoter', 'partner_role', 'partner_status')->first();
        $reason = ApplyPromoter::where('user_id', $user_id)->select('fail_reason', 'apply_status',
            'partner_id', 'partner_role','apply_role')->first();


        $infos['partner_info'] = $state;
        $infos['apply_status'] = $state['is_promoter']??0;  //推广员的申请状态
        $infos['partner_role'] = 0;


        $infos['partner_id']= $reason['partner_id']??0;

        if (!empty($reason) && $reason['apply_role'] > 1 && $reason['apply_status'] != 'success') {

            $infos['is_apply'] = 1;
            $infos['partner_status'] = $reason['apply_status'];
            $infos['fail_reason'] = $reason['fail_reason'];
            $infos['apply_role'] = $reason['apply_role'];
        } elseif(!empty($reason)  &&  $reason['apply_status'] == 'success'){
            $infos['partner_status'] = $reason['apply_status'];
        }else{

            //如果在申请推广员也是不能申请
            if($state['is_promoter'] ==2){
                $infos['is_apply'] = 1;
                $infos['apply_role'] = $reason['apply_role'];
            }else{
                $infos['is_apply'] = 0;
            }

            $infos['partner_status'] = '';
            $infos['fail_reason'] = '';
        }


        //如果是申请,有绑定小店的情况下
        if (isset($reason['apply_status']) && $reason['apply_status'] != 'fail' && $reason['partner_id']) {
            $partner = WxUserinfo::where('user_id', $reason['partner_id'])->select('nickname', 'headimgurl')->first();
            $infos['partner_shop'] = $partner;
//            $infos['partner_piture'] = Article::where('id', 29)->first();
        }

        if (isset($state['is_promoter']) && $state['is_promoter'] == 3) {
            $infos['fail_reason'] = $reason['fail_reason'];
        }

        //标记小店状态
        if (isset($state['partner_status']) && $state['partner_status'] == 0 && in_array($state['partner_role'],
                [2, 3, 4])
        ) {
            $infos['is_apply'] = 2;
            $infos['partner_role'] = $state['partner_role'];
            $infos['partnering_status'] = $state['partner_status'];
        }

        return $this->resSuccess($infos);

    }


    /**
     * 获取小店邀请信息(海报)
     * @Author hfh_wind
     * @return int
     */
    public function GetPartnerShop(Request $request)
    {
        $partner_id = $request['partner_id']??0;
        $type = $request['type']??0;
        if ($partner_id <= 0 || !$type) {
            return $this->resFailed(414, '参数错误!');
        }

        $return['partner'] = UserAccount::where([
            'user_accounts.id'             => $partner_id,
            'user_accounts.partner_status' => 0
        ])->leftJoin('wx_userinfos',
            'user_accounts.id', '=', 'wx_userinfos.user_id')->select('wx_userinfos.nickname',
            'wx_userinfos.headimgurl')->first();

        if (empty($return['partner'])) {
            return $this->resSuccess([], '推广信息失效!');
        }

        if ($type == 2) {
            $return['partner_piture'] = Article::where('id', 29)->first();
        } elseif ($type == 3) {
            $return['partner_piture'] = Article::where('id', 31)->first();
        } else {
            $return['partner_piture'] = [];
        }

        return $this->resSuccess($return);
    }


    /**
     * 推广员信息(二维码)
     * @Author hfh_wind
     * @return int
     */
    public function GetPromoterInfo()
    {
        $user_id = $this->user->id;

        $check = UserAccount::where('id', $user_id)->select('is_promoter', 'mobile')->first();

        if (empty($check)) {
            return $this->resFailed(700, '查不到会员信息!');
        }

        if ($check['is_promoter'] != 1) {
            return $this->resFailed(700, '暂无推广权限,请稍后!');
        }

        $qr = GoodsSpreadQrs::where(['user_id' => $user_id, 'type' => 1])->first();

        $wx_info = WxUserinfo::where('user_id', $user_id)->select('nickname', 'headimgurl')->first();

        $return['nickname'] = $wx_info['nickname'];
        $return['headimgurl'] = $wx_info['headimgurl'];
        $return['partnerInfo'] = $check;
        $return['wx_mini_person_qr'] = $qr['wx_mini_goods_person_qr'];

        return $this->resSuccess($return);
    }


    /**
     * 推广员生成个人小程序二维码
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetWxMiniQr(Request $request)
    {
        $user_id = $this->user->id;
        $gm_id=$this->GMID;
        //判断是否推广员
        if ($this->user->is_promoter != 1) {
            $check = UserAccount::where('id', $user_id)->select('is_promoter')->first();
            if ($check['is_promoter'] != 1) {
                return $this->resFailed(700, '您尚未拥有推广权限!');
            }
        }

        $info = GoodsSpreadQrs::where(['user_id' => $user_id, 'type' => 1])->first();
        if (!empty($info)) {
            $return['wx_mini_person_qr'] = $info['wx_mini_goods_person_qr'];
        } else {

            //小程序二维码
            $service = new CreateQrService();
            $scene = "t=g&id=" . $user_id;

            $page = "pages/index/indexCustomer";
            $res = $service->GetWxQr($scene, $page,$gm_id);

            if(!empty($res)) {
                $insert_data['goods_id'] = 0;
                $insert_data['user_id'] = $user_id;
                $insert_data['wx_mini_goods_person_qr'] = $res;
                $insert_data['type'] = 1; //推广中心图片

                GoodsSpreadQrs::create($insert_data);

                $return['wx_mini_person_qr'] = $res;
            }else{
                return $this->resFailed(700, '请稍后再试!');
            }
        }

        return $this->resSuccess($return);
    }


    /**
     * 申请判断
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyAction(Request $request)
    {
        $configRepository = new ConfigRepository();
        $conf_value = $configRepository->configItem('group','platform_attrs');
        
        // $conf = Config::where('group', 'platform_attrs')->where('page','group')->first();
        // $conf_value = json_decode($conf['value'], true);

        $param = $request->only('real_name', 'job_number', 'mobile', 'id_number', 'department', 'id_positive',
            'id_other_side', 'department_id', 'register_type', 'shop_name', 'address', 'partner_mobile', 'partner_id',
            'remarks');
        $param['user_id'] = $this->user->id;

        $param['register_type'] = $param['register_type']??'per';

//        $checkXinPoll = (new UserXinpollInfo())->where('user_id', $this->user->id)->first();
//        if (!$checkXinPoll) return $this->resFailed(500, '未实名认证');

//        if (isset($param['job_number']) && $param['register_type'] == 'per' && empty($param['job_number'])) {
//            return $this->resFailed(414, '工号不能为空!');
//        }

        $check = UserAccount::where('id', $param['user_id'])->select('is_promoter', 'partner_role',
            'partner_id','mobile')->first();
        if (empty($check)) {
            return $this->resFailed(700, '查无此会员!');
        }

        $result['apply_role']=1; //申请推广员
        //个人,申请推广员
        if ($param['register_type'] == 'per') {
            if ($check['is_promoter'] == '1') {
                return $this->resFailed(700, '该账号已经是推广员!');
            }
        }

        //小店推荐,申请推广员
        if ($param['register_type'] == 'pt') {

            if ($check['is_promoter'] == '1') {
                return $this->resFailed(700, '该账号已经是推广员!');
            }

            //推荐的小店,正常状态
            $partner = UserAccount::where([
                'id'             => $param['partner_id'],
                'partner_role'   => 2,
                'partner_status' => 0
            ])->select('id',
                'mobile')->first();
            if (empty($partner)) {
                return $this->resFailed(700, '推荐人无效,请核对!');
            }
            $result['partner_mobile'] = $partner['mobile'];
            $result['shop_name'] = $param['shop_name']??'';
            $result['partner_id'] = $partner['id'];
            $result['remarks'] = $param['remarks']??'';
            $result['address'] = $param['address']??'';
            $result['register_type'] = $param['register_type'];
        }

        //分销商推荐,申请小店
        if ($param['register_type'] == 'dt') {

            if ($check['partner_role'] == '2' || $check['partner_id']) {
                return $this->resFailed(700, '该账号已经是小店,或已经绑定关系');
            }

            //推荐的分销商,正常状态
            $partner = UserAccount::where([
                'id'             => $param['partner_id'],
                'partner_role'   => 3,
                'partner_status' => 0
            ])->select('id',
                'mobile')->first();
            if (empty($partner)) {
                return $this->resFailed(700, '推荐人无效,请核对!');
            }
            $result['partner_mobile'] = $partner['mobile'];
            $result['shop_name'] = $param['shop_name']??'';
            $result['partner_id'] = $partner['id'];
            $result['remarks'] = $param['remarks']??'';
            $result['address'] = $param['address']??'';
            $result['register_type'] = $param['register_type'];
            $result['apply_role']=2;
        }

        //如果本身不是推广员就改成,待申请2
        if($check['is_promoter']  !=1){
            $result['is_promoter'] = 2;
        }



        $result['user_id'] = $param['user_id'];
//        $result['real_name'] = $checkXinPoll['realname'];
        $result['job_number'] = $param['job_number']??'';
        $result['mobile'] = $check['mobile'];
        $result['id_number'] = $param['id_number'] ?? '';
        $result['department'] = $param['department'] ?? '';
        $result['department_id'] = $param['department_id'] ?? 0;

        $photo =
            [
                'p' => $param['id_positive'] ??'',
                'o' => $param['id_other_side'] ?? '',
            ];
        $result['id_photo'] = json_encode($photo);

        $info = ApplyPromoter::where('user_id', $param['user_id'])->orderBy('updated_at', 'desc')->first();
        if ($info['apply_status'] == 'apply') {
            return $this->resFailed(700, '该账号资格正在审核中,请勿重复提交!');
        }


//        $data = $this->reprocessData($param);

        try {
            //免审直接成为推广员
            if (!$conf_value['platform_attrs']['status']) {
                $this->applyDirectAccount($result);
            } else {
                $this->applyAccount($result);
            }
        } catch (\Exception $e) {
            return $this->resFailed(600, $e->getMessage());
        }

        return $this->resSuccess([], "申请成功!请等待审核");
    }


    /**
     * 申请成为推荐人
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyAccount($param)
    {

        if ($param['user_id'] <= 0) {
            throw new \LogicException('参数错误!');

        }

        if(isset($param['is_promoter'])){
            $updateData['is_promoter']=$param['is_promoter'];
        }

        try {
            //这里处理驳回之后更新
            if (ApplyPromoter::where('user_id', $param['user_id'])->exists()) {
                $param['apply_status'] = 'apply';
                $param['fail_reason'] = '';
                ApplyPromoter::where('user_id', $param['user_id'])->update($param);
                UserAccount::where('id', $param['user_id'])->update($updateData);
            } else {
                ApplyPromoter::create($param);
                UserAccount::where('id', $param['user_id'])->update($updateData);

            }
            return true;
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * 免签成为推荐人
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyDirectAccount($param)
    {

        if ($param['user_id'] <= 0) {
            throw new \LogicException('参数错误!');
        }

        $param['apply_status'] = 'success';
        $updateData['is_promoter'] = 1;
        $updateData['partner_role'] = $param['apply_role'];
        DB::beginTransaction();
        try {
            ApplyPromoter::create($param);
            UserAccount::where('id', $param['user_id'])->update($updateData);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \LogicException('申请失败' . $e->getMessage());
        }

    }


    /**
     * 获取推广信息列表
     * @Author hfh_wind
     * @param Request $request
     * @param ApplyPromoterRepository $applyPromoterRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodsSpreadLists(Request $request, GoodsSpreadListsRepository $repository)
    {
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');
        $param['pid'] = $this->user->id;
        $lists = $repository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields()
        ]);
    }


    /**
     * 获取所有推物信息列表
     * @Author hfh_wind
     * @param Request $request
     * @param ApplyPromoterRepository $applyPromoterRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function RelatedLogsList(Request $request, RelatedLogsListRepository $repository)
    {
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');
        $param['pid'] = $this->user->id;
        $lists = $repository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields()
        ]);

    }

    /**
     * 个人推广信息
     * @Author hfh_wind
     * @return int
     */
    public function GetPersonDistribution(Request $request)
    {
        $user_id = $this->user->id;

        $type = $request['type']??'';
        if (empty($type)) {
            return $this->resFailed(414, '参数错误!');
        }

        //1-推广员,2-小店,3-分销商,4-经销商
        $role = ApplyPromoter::where('user_id', $user_id)->first();
        $return['partner_role'] = $role['partner_role'];

        $userDeposit = TradeRewards::where('pid', $user_id)->where('type', '<>', 3)->sum('reward_value');

        $return['wechet_open_id'] = WxUserinfo::where(['user_id' => $user_id, 'user_type' => 2])->count();

        //累计收益
        $return['history_amount'] = $userDeposit ? $userDeposit : 0;

        //推物绑定的人去重
//        $goodsSpreadLogsUserCout = GoodsSpreadLogs::where(['pid' => $user_id])->selectRaw('count(distinct user_id) as total ')->first();
        $relatedLogs = RelatedLogs::where(['pid' => $user_id])->count();
        $return['spread_peoples'] = $relatedLogs ? $relatedLogs : 0;

        //邀请用户
        $return['son_members'] = UserAccount::where('pid', $user_id)->count();

        //累计推广订单
//        $goodsSpreadLogsCount = GoodsSpreadLogs::where([
//            'pid'    => $user_id,
//            'status' => 2
//        ])->selectRaw('count(distinct tid) as total ')->first();
//        $return['spread_orders'] = $goodsSpreadLogsCount['total']??0;
        $tradeEstimates = TradeEstimates::where('pid',
            $user_id)->where('type', '<>',
            3)->selectRaw('IFNULL(sum(reward_value),0) as reward_value,count(distinct tid) as trade_count ')->first();
        $return['spread_orders'] = $tradeEstimates['trade_count'];

        $count = $this->GetOrderDetails($type, $user_id);

        $return['spread_orders_bytime'] = $count['spread_orders'];//付款订单数
        $return['spread_peoples_bytime'] = $count['spread_peoples'];//邀请好友数
        $return['spread_amounts_bytime'] = $count['spread_amounts'];//预估佣金

        //转化天数
        $return['change_rewarde_day'] = 30;

        return $this->resSuccess($return);
    }


    /**
     * 推广订单明细
     * @Author hfh_wind
     * @return int
     */
    public function GetOrderDetails($type = 'now', $user_id)
    {

        switch ($type) {
            case 'now':
                $time_start = date('Y-m-d 00:00:00', time());
                $time_end = date('Y-m-d 23:59:59', time());
                break;
            case 'yesterday':
                $time_start = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $time_end = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;
            case 'week':
                $time_start = date('Y-m-d 00:00:00', strtotime('-7 day'));
                $time_end = date('Y-m-d 23:59:59', time());
                break;
            case 'month':
                $time_start = date('Y-m-d 00:00:00', strtotime('-30 day'));
                $time_end = date('Y-m-d 23:59:59', time());
                break;
        }


        //今日付款订单
//        $spreadLogs = GoodsSpreadLogs::where('pid', $user_id)->whereIn('status',
//            [2, 3])->selectRaw('count(distinct tid) as total ')->where('created_at', '>=',
//            $time_start)->where('created_at', '<=', $time_end)->first();
        $spreadLogs = TradeEstimates::where('pid',
            $user_id)->where('type', '<>', 3)->selectRaw('count(distinct tid) as total ')->where('created_at', '>=',
            $time_start)->where('created_at', '<=', $time_end)->first();
        $return['spread_orders'] = $spreadLogs['total'];

        //推广的人
        $return['spread_peoples'] = UserAccount::where('pid', $user_id)->where('created_at', '>=',
            $time_start)->where('created_at', '<=', $time_end)->count();

        //预估收益金额
        $return['spread_amounts'] = TradeEstimates::where('pid', $user_id)->where('type', '<>', 3)->where('status',
            0)->where('created_at',
            '>=',
            $time_start)->where('created_at', '<=', $time_end)->sum('reward_value');


        return $return;
    }


    /**
     * 会员提现详情
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetUserDetail(Request $request)
    {

        $user_id = $this->user->id;

//        $partner_role = UserAccount::where('id', $user_id)->select('partner_role')->first();
//
//        $return['partner_role'] = $partner_role['partner_role'];

        $return['info'] = UserDeposit::where(['user_id' => $user_id])->first();

        $return['info']['income'] = $return['info']['income']??0;

        $configRepository = new ConfigRepository();
        $config = $configRepository->configItem('group','platform_attrs');

        $return['apply_amount'] = $config['platform_attrs']['apply_amount']??0;
        $return['max_apply_amount'] = $config['platform_attrs']['max_apply_amount']??0;
        $return['service_charge'] = $config['platform_attrs']['service_charge']??0;

        return $this->resSuccess($return);
    }


    /**
     * 检查申请提现状态
     * @Author hfh_wind
     * @return int
     */
    public function ApplyCashOutCheck()
    {
        $user_id = $this->user->id;

        $check = UserDepositCash::where(['user_id' => $user_id])->get();

        if (empty($check)) {
            $return['status'] = 0;
        } else {
            //没有审核时间的
            $to_verify = UserDepositCash::where(['user_id' => $user_id, 'status' => 'TO_VERIFY'])->where('examined_at',
                null)->count();

            $denied = UserDepositCash::Where(['user_id' => $user_id, 'status' => 'DENIED'])->where('check_status',
                0)->orderBy('id', 'desc')->count();

//            $denied=UserDepositCash::Where(['user_id'=>$user_id])->Where('status','<>','TO_VERIFY')->orderBy('id','desc')->count();
            if ($to_verify) {
                $return['status'] = 1;
            } elseif ($denied) {
                $return['status'] = 2;
            } else {
                $return['status'] = 3;
            }
        }

//        if(empty($check)){
//            return $this->resSuccess([],'尚未申请!');
//        }
        return $this->resSuccess($return);
    }


    /**
     * 申请提现
     * @Author hfh_wind
     * @param $param
     * @return bool
     */
    public function ApplyCashOut(ApplyCashOutRequest $request)
    {

        $user_id = $this->user->id;

        $service = new   \ShopEM\Services\SecKillService();
        $order = $service->isActionAllowed($user_id, "apply_cash_out", 2 * 1000, 1);
        if (!$order) {
            return $this->resFailed(700, '审核提交中,请勿频繁请求!');
        }
        //检查是否授权拿到open_id
//        $wechet_open_id = WxUserinfo::where(['user_id' => $user_id, 'user_type' => 2])->count();
//
//        if (!$wechet_open_id) {
//            return $this->resFailed(700, '请先授权信息!');
//        }

        $check = UserDepositCash::where(['user_id' => $user_id, 'status' => 'TO_VERIFY'])->first();

        if (!empty($check)) {
            return $this->resFailed(700, '还有申请在审核中,请勿重复!');
        }

        $request_apply_amount = $request['apply_amount'];

        $configRepository = new ConfigRepository();
        $conf_value = $configRepository->configItem('group','platform_attrs');

        // $conf = Config::where(['group' => 'platform_attrs'])->first();
        // $conf_value = json_decode($conf['value'], true);

        $apply_amount = $conf_value['platform_attrs']['apply_amount']??0;
        $max_apply_amount = $conf_value['platform_attrs']['max_apply_amount']??0;

        if ($request_apply_amount < $apply_amount) {

            return $this->resFailed(700, '最低申请提现金额是' . $apply_amount);
        }

        if ($request_apply_amount > $max_apply_amount) {

            return $this->resFailed(700, '最高申请提现金额是' . $max_apply_amount);
        }

        DB::beginTransaction();
        try {

            //先扣减
            $res = UserDeposit::where('user_id', $user_id)->where('income', '>=',
                $request_apply_amount)->decrement('income', $request_apply_amount);

            if (!$res) {
                throw new \LogicException('申请失败.!');
            }
            $balance = UserDeposit::where('user_id', $user_id)->select('income')->first();
            $out_type = $request['out_type']??1;

            $insert['out_type'] = $out_type;
            $insert['user_id'] = $user_id;
            $insert['amount'] = $request_apply_amount;
            $insert['status'] = 'TO_VERIFY';
            $configRepository = new ConfigRepository();
            $config = $configRepository->configItem('group','platform_attrs');
            $insert['hand_fee'] = round($insert['amount'] * $config['platform_attrs']['service_charge'] / 100, 2);
            $insert['real_amount'] = $insert['amount'] - $insert['hand_fee'];
            $insert['serial_id'] = TradeService::createId('cash');
            $insert['balance'] = $balance->income;
            UserDepositCash::create($insert);


            UserDepositCash::Where(['user_id' => $user_id, 'status' => 'DENIED'])->where('check_status',
                0)->update(['check_status' => 1]);

            //日志
            $log['type'] = 2;
            $log['user_id'] = $user_id;
            $log['operator'] = 'user';
            $log['fee'] = -$request_apply_amount;
            $log['send_type'] = 1;//线上
            $log['message'] = '会员提现申请扣减';
            $log['out_type'] = $out_type;
            UserDepositLog::create($log);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \LogicException('申请失败!' . $e->getMessage());
        }

        return $this->resSuccess([], '申请成功!');

    }


    /**
     * 会员个人申请提现列表
     * @Author hfh_wind
     * @param Request $request
     * @param UserDepositCashesListRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function UserDepositCashesList(Request $request, UserDepositCashesListRepository $repository)
    {
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

        $param['user_id'] = $this->user->id;
        $lists = $repository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields()
        ]);
    }


    /**
     * 会员提现申请详情
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetUserApplyDetail(Request $request)
    {
        $id = $request['id']??0;

        if ($id <= 0) {
            return $this->resFailed(414, '参数错误!');
        }
        $param['user_id'] = $this->user->id;

        $info = UserDepositCash::where(['id' => $id, 'user_id' => $param['user_id']])->first();

        if (empty($info)) {
            return $this->resFailed(700, '找不到数据!');
        }

        return $this->resSuccess($info);
    }


    /**
     * 会员推广订单列表(预估收益)
     * @Author hfh_wind
     * @param Request $request
     * @param TradeEstimatesListsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeEstimatesLists(Request $request, TradeEstimatesListsRepository $repository)
    {
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');
        //属于自己推广的订单
        $param['pid'] = $this->user->id;
//        $param['status'] = 0;//退款订单不显示
        $lists = $repository->search($param);
        $res = TradeEstimates::where(['pid' => $param['pid']])->where('status',
            0)->selectRaw('SUM(reward_value) as reward')->first();

        $total = $res['reward']??0;

        return $this->resSuccess([
            'total' => $total,
            'lists' => $lists,
            'field' => $repository->listShowFields()
        ]);
    }


    /**
     * 会员子订单列表(预估收益)
     * @Author hfh_wind
     * @param Request $request
     * @param TradeOrderListsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function EstimatesOrderLists(Request $request, EstimatesOrderRepository $repository)
    {
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');
        //属于自己推广的订单
        $pid = $this->user->id;
        $param['pid'] = $pid;
//        $param['status'] = 0;
        $param['type'] = $request['type']??1;

        $lists = $repository->search($param);


//        $model= new  TradeEstimates();


//        if (isset($request['created_start'])) {
////            $model=$model->where('created_at','>=',$request['created_start'])->where('created_at','<=',$request['created_end']);
//            $where = " and e.`created_at` >= '" . $request['created_start'] . "' and e.`created_at` <= '" . $request['created_end'] . "' ";
//        } else {
//            $where = '';
//        }
//
//        $status=$request['status']??0;
//        if ($status) {
//            $where.="  and e.`status` =".$status;
//        }
//
//        //售后状态的
//        if (isset($request['failure'])) {
//            $where.="  and e.`status` <> 0";
//        }
//
//        if (isset($request['iord'])) {
//            $where.="  and e.`iord` =".$request['iord'];
//        }
//
//
//        $sql = "select sum(e.reward_value) as reward_value,sum(o.amount) as amount from `em_trade_estimates` as e
//LEFT JOIN   em_trade_orders as o   on  e.oid =o.oid
//where  e.`status`=0   and  e.`pid` =" . $pid . $where;
//        $sql = "select sum(e.reward_value) as reward_value from `em_trade_estimates` as e
//LEFT JOIN   em_trade_orders as o   on  e.oid =o.oid
//where  e.`status`=0   and  e.`pid` =" . $pid . $where;
//
//        $db = DB::select($sql);


//        $oid_sql = "select  distinct e.oid  from `em_trade_estimates` as e
//LEFT JOIN   em_trade_orders as o   on  e.oid =o.oid
//where  e.`status`=0   and  e.`pid` =" . $pid . $where;
//
//        $oid_db = DB::select($oid_sql);
//
//        if(count($oid_db)>0){
//            foreach($oid_db as $key){
//
//            }
//        }

        //未售后的订单
//        $res = $model->where('pid', $pid)->where('status',
////            0)->selectRaw('SUM(reward_value) as reward')->first();
//            0)->get();
//
//        $reward_value=0;
//        $goods_amount=0;
//        foreach($res  as $key=>$value){
//            $reward_value +=$value['reward_value'];
//            $amount=TradeOrder::where(['oid'=>$value['oid']])->select('amount')->first();
//            $goods_amount +=$amount['amount']??0;
//        }
//
        $un_rewark_count = TradeEstimates::where('pid',
            $pid)->where('type', '<>',
            3)->selectRaw('IFNULL(sum(reward_value),0) as reward_value,count(distinct tid) as trade_count')->where([
            'status' => 0,
            'iord'   => 1
        ])->first();

        $goods_amount = $lists['goods_amount']??0;
        $total = $lists['goods_total']??0;
        $goods_amount = round($goods_amount, 3); //分销金额
        $total = round($total, 3);//销售金额
        $un_rewark_count = $un_rewark_count['reward_value'];//销售金额

        return $this->resSuccess([
            'total'            => $total,
            'goods_amount'     => $goods_amount,
            'un_rewark_amount' => $un_rewark_count,
            'lists'            => $lists,
            'field'            => $repository->listShowFields()
        ]);
    }


    /**
     * 所有可见部门
     *
     * @Author djw
     * @param DepartmentRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function allShowDepartment(DepartmentRepository $departmentRepository)
    {
        $epartmentas = $departmentRepository->allItems();
        if (empty($epartmentas)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess($epartmentas);
    }


    /**
     * 生成小店推广二维码
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function CreatPartnerWxMiniQr(CreatPartnerWxMiniQrRequest $request)
    {
        $user_id = $this->user->id;
        $gm_id=$this->GMID;
        $type = $request['type'];

        //2-小店,3-分销商,4-经销商
        //pt-小店  dt-分销商  dl-经销商
        //判断是否推分销商
        $check = UserAccount::where('id', $user_id)->select('partner_role')->first();

        if (empty($check)) {
            return $this->resFailed(700, '查不到会员信息!');
        }

        if ($type == 2) {
            if ($check['partner_role'] != 2 || $check['partner_status'] == 1) {
                return $this->resFailed(700, '操作有误,暂无权限!');
            }

            $scene = "t=fx&id=" . $user_id;
            $page = "pagesB/user/generalize/partnerPoster";  //拉推广员的邀请码
        } elseif ($type == 3) {
            if ($check['partner_role'] != 3 || $check['partner_status'] == 1) {
                return $this->resFailed(700, '操作有误,暂无权限!');
            }
            $scene = "t=dt&id=" . $user_id;
//            $page = "pages/index/index";
            $page = "pagesB/user/generalize/distributorPoster";  //拉小店的邀请码
        } elseif ($type == 4) {
            if ($check['partner_role'] != 4 || $check['partner_status'] == 1) {
                return $this->resFailed(700, '操作有误,暂无权限!');
            }

//            $scene = "t=dl&id=" . $user_id;
//            $page = "pagesB/user/generalize/partnerPoster";
        }

        //小程序二维码
        $service = new CreateQrService();

//        $page = "pagesB/user/generalize/index";
//        $page = "pages/index/index";
        $res = $service->GetWxQr($scene, $page,$gm_id);


        $update_data['pt_wx_mini_qr'] = $res;

        UserAccount::where('id', $user_id)->update($update_data);

        return $this->resSuccess($update_data, '生成成功!');
    }


    /**
     * 小店二维码判断是否允许申请
     * @Author hfh_wind
     * @return int
     */
    public function CheckPartner(Request $request)
    {
        $partner_id = $request['partner_id']??0;

        if ($partner_id <= 0) {
            return $this->resFailed(414, '合伙人id必填!');
        }

        $user_id = $this->user->id;

        $check = UserAccount::where('id', $user_id)->select('is_promoter', 'partner_id', 'partner_role',
            'pt_wx_mini_qr', 'mobile')->first();
        $return['apply'] = false;
        if (empty($check)) {

            return $this->resSuccess($return, '查不到会员信息!');
        }

        $check_partner = UserAccount::where(['id' => $partner_id, 'partner_status' => 0])->select('id', 'partner_role',
            'mobile')->first();


        if (empty($check_partner)) {
            return $this->resSuccess($return, '推荐人信息无效!');
        }


        //如果是分销商那只能推小店
        if($check_partner['partner_role'] ==3){
            if ($check['is_promoter'] == 2) {
                return $this->resSuccess($return, '已经是小店身份!');
            }
        }else{
            if ($check['is_promoter'] == 1) {

                return $this->resSuccess($return, '已经是推广员!');
            }
        }

        $return['partnerInfo'] = $check_partner;
        $return['apply'] = true;

        return $this->resSuccess($return);
    }


    /**
     * 分销商信息
     * @Author hfh_wind
     * @return int
     */
    public function GetPartnerInfo()
    {
        $user_id = $this->user->id;

        $check = UserAccount::where('id', $user_id)->select('partner_role', 'pt_wx_mini_qr', 'mobile')->first();

        if (empty($check)) {
            return $this->resFailed(700, '查不到会员信息!');
        }

//        if ($check['partner_role'] != 2) {
//            return $this->resFailed(700, '操作有误,暂无权限!');
//        }
        $wx_info = WxUserinfo::where('user_id', $user_id)->select('nickname', 'headimgurl')->first();

        $return['nickname'] = $wx_info['nickname'];
        $return['headimgurl'] = $wx_info['headimgurl'];
        $return['partnerInfo'] = $check;

        return $this->resSuccess($return);
    }


    /**
     * 分销商推广信息
     * @Author hfh_wind
     * @return int
     */
    public function GetPartnerDistribution(Request $request)
    {
        $user_id = $this->user->id;

        $type = $request['type']??'';
        if (empty($type)) {
            return $this->resFailed(414, '参数错误!');
        }

        $user_info = UserAccount::where(['id' => $user_id])->select('partner_id', 'partner_role',
            'pt_wx_mini_qr', 'partner_status')->first();

        if (empty($user_info)) {
            return $this->resFailed(700, '数据找不到!');
        }
        $return['user_info'] = $user_info;
        //小店
        if ($user_info['partner_role'] == 2) {

            //实际收益
            $userDeposit = TradeRewards::where('pid', $user_id)->sum('reward_value');

            //累计收益
            $return['history_amount'] = $userDeposit ? $userDeposit : 0;

            //邀请推广员
            $return['partner_members'] = UserAccount::where('partner_id', $user_id)->count();

            //累计分成订单
            $tradeEstimates = TradeEstimates::where('pid',
                $user_id)->where('type', '=',
                3)->selectRaw('IFNULL(sum(reward_value),0) as reward_value,count(distinct tid) as trade_count ')->first();
            $return['spread_orders'] = $tradeEstimates['trade_count'];

        } elseif ($user_info['partner_role'] == 3) {
            //分销商
            //小店的id
            $user_info = UserAccount::where(['partner_id' => $user_id])->select('partner_id', 'partner_role',
                'pt_wx_mini_qr', 'partner_status')->first();


        }


        $count = $this->GetTradeProfitsDetails($type, $user_id);

        $return['sold_orders_bytime'] = $count['sold_orders'];//付款订单数
        $return['partner_bytime'] = $count['partner'];//邀请推广员
        $return['sold_amounts_bytime'] = $count['sold_amounts'];//预估分成金额

        return $this->resSuccess($return);
    }


    /**
     * 分成订单明细
     * @Author hfh_wind
     * @return int
     */
    public function GetTradeProfitsDetails($type = 'now', $user_id)
    {

        switch ($type) {
            case 'now':
                $time_start = date('Y-m-d 00:00:00', time());
                $time_end = date('Y-m-d 23:59:59', time());
                break;
            case 'yesterday':
                $time_start = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $time_end = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;
            case 'week':
                $time_start = date('Y-m-d 00:00:00', strtotime('-7 day'));
                $time_end = date('Y-m-d 23:59:59', time());
                break;
            case 'month':
                $time_start = date('Y-m-d 00:00:00', strtotime('-30 day'));
                $time_end = date('Y-m-d 23:59:59', time());
                break;
        }


        $spreadLogs = TradeEstimates::where('pid',
            $user_id)->selectRaw('count(distinct tid) as total ')->where('type', '=', 3)->where('created_at', '>=',
            $time_start)->where('created_at', '<=', $time_end)->first();
        $return['sold_orders'] = $spreadLogs['total'];

        //推广的人
        $return['partner'] = UserAccount::where('partner_id', $user_id)->where('created_at', '>=',
            $time_start)->where('created_at', '<=', $time_end)->count();

        //预估收益金额
        $return['sold_amounts'] = TradeEstimates::where('pid', $user_id)->where('type', '=', 3)->where('status',
            0)->where('created_at',
            '>=',
            $time_start)->where('created_at', '<=', $time_end)->sum('reward_value');


        return $return;
    }


    /**
     * 小店合伙人
     * @Author hfh_wind
     * @param Request $request
     * @param UserAccountRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function PromoterList(Request $request, PromoterListRepository $repository)
    {
        $user_id = $this->user->id;
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');
        $param['partner_id'] = $user_id;//小店
        $lists = $repository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listFields()
        ]);
    }


    /**
     * 推广员上级小店
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */
    public function PartnerRelated()
    {
        $user_id = $this->user->id;

        $res = UserAccount::where('id', $user_id)->select('partner_id')->first();
        $return['partner'] = [];
        if (isset($res['partner_id'])) {

            $return['partner'] = UserAccount::where('user_accounts.id', $res['partner_id'])->leftJoin('wx_userinfos',
                'user_accounts.id', '=', 'wx_userinfos.user_id')->select('wx_userinfos.nickname',
                'wx_userinfos.headimgurl')->first();

        }

        return $this->resSuccess($return);
    }


    /**
     * 申请记录列表
     * @return mixed
     */
    public function ApplyRecordLists(Request $request, ApplyPromoterRepository $applyPromoterRepository)
    {
        $user_id = $this->user->id;

        $param = $request->all();

        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

        $param['partner_id'] = $user_id;

        $lists = $applyPromoterRepository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $applyPromoterRepository->listShowFields()
        ]);
    }


    /**
     *  审核申请详情
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ApplyRecordDetail(Request $request)
    {
        $id = intval($request->id);

        try {
            $detail = ApplyPromoter::find($id);

            if (empty($detail)) {
                return $this->resFailed(700);
            }

            $detail['photo'] = json_decode($detail['id_photo'], true);

        } catch (\Exception $e) {
            return $this->resFailed(700, $e->getMessage());
        }

        return $this->resSuccess($detail);
    }


    /**
     * 审核(推广员,小店)
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function CheckerExamine(CheckerExamineRequest $request)
    {
        $user_id = $this->user->id;

        $data = $request->only('id', 'apply_status', 'fail_reason');


        $checker_info = UserAccount::where(['id' => $user_id, 'partner_status' => 0])->select('partner_role',
            'partner_id', 'partner_status', 'id', 'mobile','is_promoter')->first();

        if (empty($checker_info)) {
            return $this->resFailed(700, '审核人信息异常,请稍后再试!');
        }


        $updatePromoter['apply_status'] = $data['apply_status'];
        $updatePromoter['fail_reason'] = $data['fail_reason'] ?? '';

        $apply = ApplyPromoter::where(['id' => $data['id'], 'apply_status' => 'apply'])->first();
        if (!$apply) {
            return $this->resFailed(702, '提交的审核记录不存在');
        }

        //1-审核推广员 2-审核小店
        if ($apply['apply_role'] == 1) {
            //如果不是小店无法审核推广员
            if ($checker_info['partner_role'] != 2) {
                return $this->resFailed(700, '不是小店身份,暂无审核权限,请稍后再试!');
            }

            if (UserAccount::where('id', $apply['user_id'])->where('is_promoter', 1)->exists()) {
                return $this->resFailed(702, '该会员已经是推广员');
            }

        } elseif ($apply['apply_role'] == 2) {

            //如果不是分销商无法审核小店
            if ($checker_info['partner_role'] != 3) {
                return $this->resFailed(700, '不是分销商身份,暂无审核权限,请稍后再试!');
            }

            $exists = UserAccount::where('id', $apply['user_id'])->where('partner_role', 2)->select('partner_role',
                'is_promoter', 'partner_id')->first();
            if (!empty($exists) || $exists['partner_id']) {
                return $this->resFailed(702, '该会员已经是小店身份');
            }
        }

        if ($updatePromoter['apply_status'] == 'fail' && empty($updatePromoter['fail_reason'])) {
            return $this->resFailed(702, '审核失败需要填写原因');
        }

        DB::beginTransaction();
        try {


            if ($updatePromoter['apply_status'] == 'success') {
                //审核推广员
                if ($apply['apply_role'] == 1) {
                    $updateUser['is_promoter'] = 1;
                    $updateUser['partner_role'] = 1;
                    $updateUser['partner_id'] = $apply['partner_id']; //记录父级小店id
                    UserAccount::where('id', $apply['user_id'])->update($updateUser);

                    $updatePromoter['is_promoter'] = 1;
                    $updatePromoter['partner_role'] = 1;


                    //记录绑定时间
                    $relatelog['user_id'] = $apply['user_id'];
                    $relatelog['partner_id'] = $user_id;
                    $relatelog['status'] = 1;
                    $relate_log['type'] = 2;
                    $is_exist = PartnerRelatedLog::where(['user_id'=> $apply['user_id'],'partner_id'=> $apply['partner_id'],'type'=>2])->count();
                    if ($is_exist) {
                        PartnerRelatedLog::where(['user_id'=> $apply['user_id'],'partner_id'=> $apply['partner_id'],'type'=>2])->update($relatelog);
                    } else {
                        PartnerRelatedLog::create($relatelog);
                    }

                } elseif ($apply['apply_role'] == 2) {
                    //小店审核成通过那么推广员也通过
                    $updateUser['is_promoter'] = 1;
                    $updateUser['partner_role'] = 2;
                    $updateUser['partner_id'] = $apply['partner_id']; //记录父级分销商id
                    UserAccount::where('id', $apply['user_id'])->update($updateUser);

                    $updatePromoter['partner_role'] = 2;

                    //记录绑定推广员部分,自己绑定自己
                    $relate_log['user_id'] = $apply['user_id'];
                    $relate_log['partner_id'] = $apply['user_id'];
                    $relate_log['status'] = 1;
                    $relate_log['type'] = 2;
                    $relate_log['is_own'] = 1;
                    $is_exist = PartnerRelatedLog::where(['user_id'=> $apply['user_id'],'partner_id'=> $apply['user_id'],'type'=>2,'is_own'=>1])->count();
                    if ($is_exist) {
                        PartnerRelatedLog::where(['user_id'=> $apply['user_id'],'partner_id'=> $apply['partner_id'],'type'=>2,'is_own'=>1])->update($relate_log);
                    } else {
                        PartnerRelatedLog::create($relate_log);
                    }

                    //绑定店家部分,小店绑定分销商
                    $relatelog['user_id'] = $apply['user_id'];
                    $relatelog['partner_id'] = $user_id;
                    $relatelog['status'] = 1;
                    $relatelog['type'] = 3;
                    $is_exist = PartnerRelatedLog::where(['user_id'=> $apply['user_id'],'type'=>3])->where('partner_id',
                        $apply['partner_id'])->count();
                    if ($is_exist) {
                        PartnerRelatedLog::where(['user_id'=> $apply['user_id'],'type'=>3])->where('partner_id',
                            $apply['partner_id'])->update($relatelog);
                    } else {
                        PartnerRelatedLog::create($relatelog);
                    }

                }


                $updatePromoter['examine_time'] = date('Y-m-d H:i:s', time());
                //审核类型小店
                if ($apply['apply_role'] == 2) {

                    //变更客户身份
                    RelatedLogs::where(['user_id' => $apply['user_id'], 'status' => 1])->update(['status' => 0]);

                    $check = RelatedLogs::where([
                        'user_id' => $apply['user_id'],
                        'pid'     => $apply['user_id']
                    ])->count();

                    if (!$check) {
                        $related_logs_insert['user_id'] = $apply['user_id'];
                        $related_logs_insert['pid'] = $apply['user_id'];
                        $related_logs_insert['status'] = 1;
                        $related_logs_insert['hold'] = 1;
                        RelatedLogs::create($related_logs_insert);
                    }

                    RelatedLogs::where([
                        'user_id' => $apply['user_id'],
                        'pid'     => $apply['user_id']
                    ])->update(['status' => 1, 'hold' => 1]);

                    $partner_key = "partner_type_2_" . $apply['user_id'];
                    Redis::set($partner_key, 1);
                }
            }else{
                //推广员驳回
                if ($apply['apply_role'] == 1) {
                    $updateUser['is_promoter'] = 3;
                    UserAccount::where('id', $apply['user_id'])->update($updateUser);
                }elseif ($apply['apply_role'] == 2  && $checker_info['is_promoter'] !=1) {
                    //申请店家,本身不是推广员就设置为0
                    $updateUser['is_promoter'] = 0; //记录父级分销商id
                    UserAccount::where('id', $apply['user_id'])->update($updateUser);
                }
            }

            $updatePromoter['checker_phone'] = $checker_info['mobile'];
            $updatePromoter['checker_id'] = $checker_info['id'];
            ApplyPromoter::where('id', $data['id'])->update($updatePromoter);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->resFailed(702, $e->getMessage().'更新失败');
        }

        return $this->resSuccess();
    }

    public function checkVerified(UserXinpollInfo $model)
    {
//        $check = $model->where('user_id', $this->user->id)->first();
//        if ($check && $check['realname'] && $check['card'] && $check['mobile']) {
            return $this->resSuccess([],'已实名认证');
//        } else {
//            return $this->resFailed(500, '未实名认证');
//        }
    }

}
