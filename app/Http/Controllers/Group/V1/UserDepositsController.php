<?php
/**
 * @Filename UserDepositsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Models\Config;
use ShopEM\Models\DownloadLog;
use ShopEM\Models\PlatformAdminLogs;
use ShopEM\Models\RelatedLogs;
use ShopEM\Models\TradeEstimates;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserDeposit;
use ShopEM\Models\UserDepositLog;
use ShopEM\Models\UserXinpollInfo;
use ShopEM\Models\WxUserinfo;
use ShopEM\Repositories\UserAccountRepository;
use ShopEM\Repositories\UserDepositCashesListRepository;
use ShopEM\Http\Requests\Platform\ExamineRequest;
use ShopEM\Models\UserDepositCash;
use ShopEM\Repositories\TradeEstimatesListsRepository;
use ShopEM\Repositories\TradeRewardsListsRepository;
use ShopEM\Repositories\UserDepositLogsListsRepository;
use ShopEM\Repositories\UserDepositListsRepository;
use ShopEM\Repositories\RankingListRepository;
use Maatwebsite\Excel\Facades\Excel;
use ShopEM\Exports\DownLoadMap;
use ShopEM\Repositories\ApplyPromoterRepository;
use ShopEM\Repositories\DepartmentRepository;
use ShopEM\Jobs\DownloadLogAct;
use ShopEM\Services\Xinpoll\Sdk;

class UserDepositsController extends BaseController
{
    /**
     * 会员申请提现列表
     * @Author hfh_wind
     * @param Request $request
     * @param UserDepositCashesListRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function UserDepositCashesList(Request $request, UserDepositCashesListRepository $repository)
    {
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

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
        $id = $request['id'] ?? 0;

        if ($id <= 0) {
            return $this->resFailed(414, '参数错误!');
        }
        $info = UserDepositCash::find($id);

        if (empty($info)) {
            return $this->resFailed(700, '找不到数据!');
        }

        return $this->resSuccess($info);
    }


    /**
     * 审核推荐人资格
     * @Author hfh_wind
     * @param ExamineRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function Examine(ExamineRequest $request)
    {
        $data = $request->all();
        $admin_id = $this->groupUser;

        $service = new   \ShopEM\Services\SecKillService();
        $order = $service->isActionAllowed($admin_id, "examine_apply", 2 * 1000, 1);
        if (!$order) {
            return $this->resFailed(700, '审核提交中,请勿频繁请求!');
        }


        $info = UserDepositCash::find($data['id']);

        if (empty($info)) {
            return $this->resFailed(700, '找不到数据!');
        }

        if ($info['status'] == 'VERIFIED') {
            return $this->resFailed(700, '已审核通过,请勿重复审核!');
        }

        if ($info['status'] == 'DENIED') {
            return $this->resFailed(700, '已驳回,请勿重复审核!');
        }

        DB::beginTransaction();
        try {
            $upData = [
                'status' => $data['status'],
                'executor' => $admin_id,
                'examined_at' => date('Y-m-d H:i:s', time()),
            ];
            $memo = '审核成功!';
            //驳回恢复提现的金额
            if ($data['status'] == 'DENIED') {

                $res = UserDeposit::where('user_id', $info['user_id'])->increment('income', $info['amount']);

                $log['type'] = 1; //驳回增加
                $log['message'] = '会员微信提现驳回';
                $log['fee'] = $info['amount'];
                $memo = '审核成功，结果:提现驳回';

            } else if ($data['status'] == 'VERIFIED') {

                $xinPollInfo = UserXinpollInfo::where('user_id', $info['user_id'])->first();
                $xinPoll = new Sdk();
                $res = $xinPoll->issueWechat([
                    'orderNo'       =>  $info['serial_id'],
                    'acctName'      =>  $xinPollInfo['realname'],
                    'mobile'        =>  $xinPollInfo['mobile'],
                    'papersType'    =>  'ID_CARD',
                    'cardNo'        =>  $xinPollInfo['card'],
                    'amount'        =>  $info['real_amount'],
                    'pmtType'       =>  'PAYMENT',
                    'acctNo'        =>  '',
                ],[], [env('APP_URL').'/issue/xinpoll-notify']);

                if ($res['code'] != 1000) {
                    throw new \LogicException('转账失败，请联系管理员');
                } else {
                    $memo = '审核成功,流水号:'.$info['serial_id'].'('.$res['data']['batchNo'].')';
                    $upData['batch_no'] = $res['data']['batchNo'];
                }

                //通过
//                $wx_info = WxUserinfo::where('user_id', $info['user_id'])->first();
//
//                $payData = [
//                    'partner_trade_no' => $info['serial_no'],
//                    'openid' => $wx_info['openid'],
//                    'amount' => $info['amount'],
//                    'desc' => '帐户提现', //此参数一定不能少，判断是否是退款操作
//                    'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
//                    'pay_type' => 'transfer',
//                ];
//
//                $pay = new  \ShopEM\Services\PayToolService;
//                $result = $pay->dopay($payData, 'Wxpaymini');

//                if (!$result) {
//                    throw new \LogicException('转账失败,请求支付网关出错');
//                }
                $log['type'] = 2; //减少金额
                $log['message'] = '会员微信提现(手续费:'.$info['hand_fee'].')';
                $log['fee'] = -$info['amount'];
            }


            $log['user_id'] = $info['user_id'];
            $log['operator'] = $admin_id;
            $log['send_type'] = 1;//线上

            UserDepositLog::create($log);

            $log_text = $data['log_text'] ?? '';
            $info->update($upData);
            PlatformAdminLogs::create([
                'router'    =>  $request->url(),
                'admin_user_id'    =>  $request->user()->id,
                'admin_user_name'    =>  $request->user()->username,
                'ip'    =>  $request->ip(),
                'status'    =>  1,
                'memo'    =>  $memo,
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }
        return $this->resSuccess([], '审核成功!');
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

        $lists = $repository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields()
        ]);
    }


    /**
     * 会员推广订单列表(实际收益收益)
     * @Author hfh_wind
     * @param Request $request
     * @param TradeRewardsListsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeRewardsLists(Request $request, TradeRewardsListsRepository $repository)
    {
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

        $lists = $repository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields()
        ]);
    }


    /**
     * 会员收入日志
     * @Author hfh_wind
     * @param Request $request
     * @param UserDepositLogsListsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function UserDepositLogsLists(Request $request, UserDepositLogsListsRepository $repository)
    {
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

        $lists = $repository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields()
        ]);
    }


    /**
     * 会员账户列表
     * @Author hfh_wind
     * @param Request $request
     * @param UserDepositListsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function UserDepositLists(Request $request, UserDepositListsRepository $repository)
    {
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

        $lists = $repository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields()
        ]);
    }


    /**
     * 会员账户明细
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function UserDepositDetail(Request $request)
    {
        $user_id = $request['user_id'] ?? 0;
        if ($user_id <= 0) {
            return $this->resFailed(414, '参数错误!');
        }

        $res = UserDeposit::where('user_id', $user_id)->first();

        $return['estimated_count'] = $res['estimated'] ?? 0;//预估收益
        $return['income'] = $res['income'] ?? 0;//实际收益

        return $this->resSuccess($return);
    }


    /**
     * 分销团队-销售排行榜-汇总表
     * @return mixed
     */
    public function GroupCollectLists(Request $request, DepartmentRepository $Repository)
    {

        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

        $lists = $Repository->GroupCollectSearch($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $Repository->GroupCollectShowFields()
        ]);
    }


    /**
     * 分销个人-销售排行榜
     * @Author hfh_wind
     * @return int
     */
    public function UserRewardRankingList(Request $request, RankingListRepository $repository)
    {
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

        $lists = $repository->userRewardRankingSearch($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields()
        ]);
    }


    /**
     * 分销个人-销售排行榜
     * @Author hfh_wind
     * @return int
     */
    public function UserRewardRankingListDown(Request $request, RankingListRepository $repository)
    {
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

        $lists = $repository->userRewardRankingSearch($param);

        //获取下载表头
        $title = $repository->listFields();//需要导出的字段

        $export_title = array_column($title, 'title'); //表头

        $exportData = [];
        // 提取导出数据
        foreach ($lists as $k => $v) {
            foreach ($title as $fv) {

                $exportData[$k][$fv['key']] = $v[$fv['key']] ? $v[$fv['key']] : '';
            }
        }

        array_unshift($exportData, $export_title); // 表头数据合并

        $filePath = "UserRewardRankingListDown_" . date('Y-m-d_H_i_s') . '.xls';
//        return Excel::download(new UserAccountDown($exportData), $filePath);
        Excel::store(new DownLoadMap($exportData), $filePath, 'oss');

        $return['url'] = config('filesystems.disks.oss.domain') . $filePath;
        return $this->resSuccess($return, '导出成功!');
    }


    /**
     * 推广员列表
     * @Author RJie
     * @param Request $request
     * @param ApplyPromoterRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function userPromoterLists(Request $request, ApplyPromoterRepository $repository)
    {
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

        $lists = $repository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->PromoterListsShowFields(4)
        ]);
    }

    public function userCustomerList(Request $request, UserAccountRepository $repository)
    {

        $conf = Config::where('group', 'platform_attrs')->first();
        $conf_value = json_decode($conf['value'], true);
        $last_day = $conf_value['platform_attrs']['last_day'] ?? 7;  // 绑定时长

        $user_id = $request->input('user_id');
        $data['partner_id'] = $user_id;
        $data['per_page'] = $request->per_page == 0 ? config('app.page_size') : $request->per_page;
        try {
            $lists = DB::table('related_logs as a')
                ->select('b.id', 'b.mobile', 'c.nickname', 'c.headimgurl', 'a.created_at', 'a.hold')
                ->leftJoin('user_accounts as b', 'a.user_id', 'b.id')
                ->leftJoin('wx_userinfos as c', 'c.user_id', 'b.id');
            $lists = $lists->where('a.pid', $user_id)->where('status', 1)->paginate($data['per_page']);

            $newDate = date('Y-m-d H:i:s');
            foreach ($lists as $key => $value) {
                $tradeEstimate = DB::table('trade_estimates as a')
                    ->leftJoin('trades as b', 'a.tid', 'b.tid')
                    ->where('a.pid', $user_id)->where('a.user_id', $value->id)
                    ->sum('b.amount');

                $lists[$key]->countAccount = $tradeEstimate;
                $lists[$key]->countTrade = TradeEstimates::where('pid', $user_id)->where('user_id', $value->id)->count();

                $differ = $this->count_days($newDate, $value->created_at);
                $surplus = $last_day-$differ;
                $lists[$key]->surplus = $surplus;

                $lists[$key]->hold_text = $surplus > 0 ? '已绑定' : '已解除';
            }

        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess([
            'lists' => $lists,
            'fields' => $repository->customerListShowFields()
        ]);
    }

    public function count_days($a, $b)
    {
        $a_dt = strtotime($a);
        $b_dt = strtotime($b);
        return round(abs($a_dt - $b_dt) / 86400);

    }


    /**
     * 会员佣金统计汇总表
     * @param Request $request
     * @param ApplyPromoterRepository $applyPromoterRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function PromoterLists(Request $request, ApplyPromoterRepository $applyPromoterRepository)
    {

        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');
        $param['apply_status'] = 'success';

        $lists = $applyPromoterRepository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $applyPromoterRepository->PromoterListsShowFields(1)
        ]);
    }


    /**
     * 会员佣金统计汇总表
     * @return mixed
     */
    public function PromoterListsDown(Request $request)
    {

        $param = $request->all();

        $insert['type'] = 'PromoterLists';
        $insert['desc'] = '会员佣金统计汇总表导出';
        $insert['gm_id'] = 0;
        $res = DownloadLog::create($insert);

        $param['log_id'] = $res['id'];
        $param['apply_status'] = 'success';

        DownloadLogAct::dispatch($param);

        return $this->resSuccess('导出中请等待!');
    }


    /**
     * 合伙人
     * @Author hfh_wind
     * @param Request $request
     * @param ApplyPromoterRepository $applyPromoterRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function PartnerLists(Request $request, ApplyPromoterRepository $applyPromoterRepository)
    {
        $res = $this->get_sub(10);
        dd($res);
        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

        $lists = $applyPromoterRepository->search($param);

//        return $this->resSuccess([
//            'lists' => $lists,
//            'field' => $applyPromoterRepository->PromoterListsShowFields()
//        ]);
    }


    /**
     * 小店佣金统计汇总表
     * @return mixed
     */
    public function PromoterShopLists(Request $request, ApplyPromoterRepository $applyPromoterRepository)
    {

        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

        $param['partner_role'] = 2;//小店

        $lists = $applyPromoterRepository->search($param, 'promoter_shop');

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $applyPromoterRepository->PromoterListsShowFields(2)
        ]);
    }


    /**
     * 小店佣金统计汇总表下载
     * @return mixed
     */
    public function PromoterShopListsDown(Request $request)
    {

        $param = $request->all();

        $insert['type'] = 'PromoterShopLists';
        $insert['desc'] = '小店佣金统计汇总表导出';
        $res = DownloadLog::create($insert);

        $param['log_id'] = $res['id'];
        $param['apply_status'] = 'success';

        DownloadLogAct::dispatch($param);

        return $this->resSuccess('导出中请等待!');
    }
}
