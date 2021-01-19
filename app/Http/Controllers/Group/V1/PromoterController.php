<?php
/**
 * @Filename PromoterController.php
 * Created by lanlnk
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-02-24
 * @Time: 15:15
 */
namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Support\Facades\Redis;
use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Http\Requests\Platform\ChangePartnersRelatedRequest;
use ShopEM\Http\Requests\Platform\GetUserPertnerInfoRequest;
use ShopEM\Http\Requests\Platform\SetPartnersRelatedRequest;
use ShopEM\Http\Requests\Platform\SetPartnersRequest;
use ShopEM\Http\Requests\Platform\UnfreezePartnersRequest;
use ShopEM\Models\Department;
use ShopEM\Models\PartnerRelatedLog;
use ShopEM\Models\RelatedLogs;
use ShopEM\Models\SetPartnersLog;
use ShopEM\Models\UserXinpollInfo;
use ShopEM\Repositories\ApplyPromoterRepository;
use ShopEM\Repositories\GoodsSpreadListsRepository;
use ShopEM\Models\ApplyPromoter;
use ShopEM\Models\UserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ShopEM\Repositories\RelatedLogsListRepository;
use ShopEM\Http\Requests\Platform\SetDepartmentRequest;
use ShopEM\Repositories\SetPartnersLogsRepository;
use ShopEM\Repositories\UserDepositCashesListRepository;

class PromoterController extends BaseController
{
    /**
     * 获取所有推荐人列表
     * @param Request $request
     * @param ApplyPromoterRepository $applyPromoterRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request,ApplyPromoterRepository $applyPromoterRepository)
    {

        $param = $request->all();
        $param['per_page'] = $param['per_page'] ?? config('app.per_page');

        $lists = $applyPromoterRepository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $applyPromoterRepository->listShowFields()
        ]);
    }

    /**
     * 推荐人详情
     * @param Request $request
     * @param UserXinpollInfo $xinpollInfoModel
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request, UserXinpollInfo $xinpollInfoModel)
    {
        $id = intval($request->id);

        try
        {
            $detail = ApplyPromoter::find($id);

            if (empty($detail))
            {
                return $this->resFailed(700);
            }

            $department_name = Department::where('id', $detail['department_id'])->value('name');
            $detail['department_name'] = $department_name ?? '';

            $detail['is_verified'] = false;
            $detail['verified_info'] = null;
            $xinPollInfo = $xinpollInfoModel->where('user_id', $detail->user_id)->first();
            if ($xinPollInfo) {
                $detail['is_verified'] = true;
                $detail['verified_info'] = $xinPollInfo;
            }

            return $this->resSuccess($detail);
        }
        catch (\Exception $e)
        {
            Log::error($e->getMessage());
            return $this->resFailed(600);
        }
    }


    /**
     * 设置部门
     * @Author hfh_wind
     * @param SetDepartmentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function SetDepartment(SetDepartmentRequest $request)
    {

        $department_id=$request['department_id'];
        $department=Department::find($department_id);

        if(empty($department)){
            return $this->resFailed(700,'部门对应信息找不到!');
        }

        try
        {
            $detail = ApplyPromoter::find($request['id']);

            if (empty($detail))
            {
                return $this->resFailed(700,'推广员信息有误!');
            }
            $detail->update(['department_id'=>$department_id]);

            UserAccount::where('id',$detail['user_id'])->update(['department_id'=>$department_id]);

        }
        catch (\Exception $e)
        {
            return $this->resFailed(600);
        }

        return $this->resSuccess([],"配置成功!");
    }


    /**
     * 审核推荐人资格
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function examine(Request $request)
    {

        $data = $request->only('user_id', 'apply_status', 'fail_reason');

        if ($data['user_id'] <= 0)
        {
            throw new \LogicException('参数错误!');
        }

        $name = UserAccount::where('id', $data['user_id'])->value('login_account');
        $msg_text = $name.'审核成为推荐人';

        if (UserAccount::where('id',$data['user_id'])->where('is_promoter',1)->exists())
            return $this->resFailed( 702, '该会员已经是推荐人');

        $apply = ApplyPromoter::where('user_id', $data['user_id'])->first();
        if (!$apply)
            return $this->resFailed( 702, '会员不存在');

        $updatePromoter['apply_status'] =  $data['apply_status'];
        $updatePromoter['fail_reason'] =   $data['fail_reason'] ?? '';

        if($updatePromoter['apply_status'] == 'fail' && empty($updatePromoter['fail_reason']))
        {
            return $this->resFailed( 702, '审核失败需要填写原因');
        }

        DB::beginTransaction();
        try
        {
            ApplyPromoter::where('user_id', $data['user_id'])->update($updatePromoter);
            if($updatePromoter['apply_status'] == 'fail')
            {
                $updateUser['is_promoter'] = 3 ;
                UserAccount::where('id', $data['user_id'])->update($updateUser);
            }

            if($updatePromoter['apply_status'] == 'success')
            {
                $updateUser['is_promoter'] = 1 ;
                $updateUser['partner_role'] = 1 ;
                $updateUser['department_id'] = $apply['department_id']; //记录会员所属部门
                UserAccount::where('id', $data['user_id'])->update($updateUser);
            }
            DB::commit();
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            Log::error($e->getMessage());
            $this->adminlog($msg_text.'失败', 0);
            return $this->resFailed(702,'更新失败');
        }

        //日志
        $this->adminlog($msg_text.'成功', 1);
        return $this->resSuccess();

    }


    /**
     * 获取所有推物信息列表
     * @Author hfh_wind
     * @param Request $request
     * @param GoodsSpreadListsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodsSpreadLists(Request $request,GoodsSpreadListsRepository $repository)
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
     * 获取所有推物信息列表
     * @Author hfh_wind
     * @param Request $request
     * @param RelatedLogsListRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function RelatedLogsList(Request $request, RelatedLogsListRepository $repository)
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
     * 直接授权分销(身份1-推广员,2-小店,3-分销商,4-经销商)
     * @Author hfh_wind
     * @param SetPartnersRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function SetPartners(SetPartnersRequest $request)
    {
        $param = $request->only('id', 'partner_status', 'partner_role', 'type');

        //partner_role  0-普通会员,1-推广员,2-小店,3-分销商,4-经销商
        $userInfo = UserAccount::where('id', $param['id'])->first();

        if (empty($userInfo)) {
            return $this->resFailed(700, '找不到会员!');
        }

        //审核通过的时候判断
        if ($param['type'] == 1) {
            if ($userInfo['partner_role'] == $param['partner_role']) {
                return $this->resFailed(702, '请勿重复授权!');
            }
        }

//        if ($param['partner_role'] > $userInfo['partner_role']) {
        //申请表也处理
        $info = ApplyPromoter::where('user_id', $param['id'])->first();

        DB::beginTransaction();
        if (empty($info)) {
            $insert['user_id'] = $param['id'];
            $insert['mobile'] = $userInfo['mobile'];
            $insert['apply_status'] = 'success';
            $insert['register_type'] = 'pla';
            $insert['partner_role'] = $param['partner_role'];
            if ($param['partner_role'] == 2) {
                $insert['is_promoter'] = 1;
            }
            ApplyPromoter::create($insert);
//            //记录绑定时间
//            $relatelog['user_id'] = $param['id'];
//            $relatelog['partner_id'] = $param['id'];
//            $relatelog['status'] = 1;
//            PartnerRelatedLog::create($relatelog);

            $log['remarks'] = "设置身份!";
        } else {

            $insert['partner_role'] = $param['partner_role'];
            //如果升级为小店,父级就设置为0
            if ($param['partner_role'] == 2 && $userInfo['partner_role'] < $param['partner_role']) {
                $insert['partner_id'] = 0;
            }
            ApplyPromoter::where('id', $info['id'])->update($insert);

            if ($userInfo['partner_role'] > $param['partner_role']) {
                $log['remarks'] = "降级身份!";
            } elseif ($userInfo['partner_role'] < $param['partner_role']) {
                $log['remarks'] = "升级身份!";
            } else {
                $log['remarks'] = "修改身份!";
            }

        }

//        }

        try {

            //审核同意
            if (!$param['partner_status']) {
                if ($userInfo['partner_id'] && $param['partner_role'] == 2) {
                    //原来父级置零
                    $updateData['partner_id'] = 0;
                    //历史关系过期
                    PartnerRelatedLog::where([
                        'user_id' => $param['id'],
                        'type'    => 2,
                        'status'  => 1
                    ])->update(['remarks' => '后台授权变更', 'status' => 0]);

                }
                if($param['partner_role'] == 2) {

                    //记录绑定时间
                    $relatelog['user_id'] = $param['id'];
                    $relatelog['partner_id'] = $param['id'];
                    $relatelog['status'] = 1;
                    $relatelog['is_own'] = 1;//自己绑自己标识
                    $is_exist = PartnerRelatedLog::where('user_id', $param['id'])->where('partner_id',$param['id'])->count();
                    if ($is_exist) {
                        PartnerRelatedLog::where('user_id', $param['id'])->where('partner_id', $param['id'])->update($relatelog);
                    } else {
                        PartnerRelatedLog::create($relatelog);
                    }
                }

                $updateData['partner_status'] = 0;
                $updateData['partner_role'] = $param['partner_role'];

                //原本不是推广员,直接授权小店的时候默认成为推广员
                if ($userInfo['is_promoter'] != 1 && $param['partner_role'] == 2) {
                    $updateData['is_promoter'] = 1;
                }

                UserAccount::where('id', $param['id'])->update($updateData);
                //变更客户身份
                RelatedLogs::where(['user_id' => $param['id'], 'status' => 1])->update(['status' => 0]);

                $check = RelatedLogs::where(['user_id' => $param['id'], 'pid' => $param['id']])->count();

                if (!$check) {
                    $related_logs_insert['user_id'] = $param['id'];
                    $related_logs_insert['pid'] = $param['id'];
                    $related_logs_insert['status'] = 1;
                    $related_logs_insert['hold'] = 1;
                    RelatedLogs::create($related_logs_insert);
                }

                RelatedLogs::where(['user_id' => $param['id'], 'pid' => $param['id']])->update(['status' => 1, 'hold' => 1]);

                if ($param['partner_role'] == 2) {
                    $partner_key = "partner_type_2_" . $param['id'];
                    Redis::set($partner_key,1);
                }

                //如果升级分销商,且原本是店家的
                if($param['partner_role'] == 3  &&  $userInfo['partner_role']==2){
                    $partner_key = "partner_type_2_" . $param['id'];
                    Redis::del($partner_key);
                    RelatedLogs::where(['user_id' => $param['id'], 'hold' => 1])->delete();
                    PartnerRelatedLog::where(['user_id' => $param['id'], 'is_own' => 1])->delete();
                }


                //如果降级推广员,且原本是店家
                if($param['partner_role'] == 1 && $userInfo['partner_role'] == 2){
                    //第一步
                    $partner_key = "partner_type_2_" . $param['id'];
                    Redis::del($partner_key);
                    RelatedLogs::where(['user_id' => $param['id'], 'hold' => 1])->delete();
                    PartnerRelatedLog::where(['user_id' => $param['id'], 'is_own' => 1])->delete();
                    //合伙人绑定全部设置为失效
                    PartnerRelatedLog::where(['user_id' => $param['id']])->update(['status'=>0]);
                }
            }
            //冻结身份
            if ($param['partner_status'] == 1) {

                $updateData['partner_status'] = 1;
                UserAccount::where('id', $param['id'])->update($updateData);
                $log['remarks'] = "冻结身份!";
            }

            //日志记录
            $log['user_id'] = $param['id'];
            $log['old_role'] = $userInfo['partner_role'];
            $log['change_role'] = $param['partner_role'];

            SetPartnersLog::create($log);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $msg = $e->getMessage();
            return $this->resFailed(700, $msg);
        }

        return $this->resSuccess([], "操作成功!");
    }


    /**
     * 记录修改身份日志
     * @Author hfh_wind
     * @param Request $request
     * @param SetPartnersLogsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function SetPartnersLog(Request $request, SetPartnersLogsRepository $repository)
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
     *  改合伙人关联
     * @Author hfh_wind
     * @param SetPartnersRelatedRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function SetPartnersRelated(SetPartnersRelatedRequest $request)
    {
        $param = $request->only('user_id', 'partner_ids');

        //partner_role  0-普通会员,1-推广员,2-小店,3-分销商,4-经销商
        $userInfo = UserAccount::find($param['user_id']);

        if (empty($userInfo)) {
            return $this->resFailed(700, '找不到会员!');
        }

//        $test=['1','2','3'];
        //partner_ids 绑定当前小店的,推广员id 集合
        $param['partner_ids'] = is_array($param['partner_ids']) ? $param['partner_ids'] : json_decode($param['partner_ids'],
            true);
//        dd($param['partner_ids']);
        foreach ($param['partner_ids'] as $val) {

            $check = UserAccount::where('id', $val)->select('is_promoter', 'gid', 'partner_role', 'partner_id',
                'partner_status')->first();
            if ($check['partner_role'] >= $userInfo['partner_role']) {
                return $this->resFailed(700, '会员身份不符合!,请勿操作');
            }
            if ($check['partner_id']) {
                return $this->resFailed(700, '会员已有父级!,请勿操作');
            }
        }

        try {

            foreach ($param['partner_ids'] as $val) {
                $update['partner_id'] = $param['user_id'];
                UserAccount::where('id', $val)->update($update);
                ApplyPromoter::where('user_id', $val)->update($update);

                //记录绑定时间
                $relatelog['user_id'] = $val;
                $relatelog['partner_id'] = $param['user_id'];
                $relatelog['status'] = 1;
                $relatelog['type'] = 2;
                $is_exist = PartnerRelatedLog::where('user_id', $val)->where('partner_id', $param['user_id'])->count();
                if ($is_exist) {
                    PartnerRelatedLog::where('user_id', $val)->where('partner_id', $param['user_id'])->update($relatelog);
                } else {
                    PartnerRelatedLog::create($relatelog);
                }
            }

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            return $this->resFailed(700, $msg);
        }

        return $this->resSuccess([], "操作成功!");
    }



    /**
     *  修改某个会员的上下级关系
     * @Author hfh_wind
     * @param ChangePartnersRelatedRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ChangePartnersRelated(ChangePartnersRelatedRequest $request)
    {
        $param = $request->only('user_id', 'partner_id');

        $user_id = $param['user_id'];
        $partner_id = $param['partner_id'];
        //partner_role  0-普通会员,1-推广员,2-小店,3-分销商,4-经销商
        $userInfo = UserAccount::find($user_id);

        if (empty($userInfo)) {
            return $this->resFailed(700, '找不到会员!');
        }
        //父级
        $check = UserAccount::where('id', $partner_id)->select('is_promoter', 'gid', 'partner_role',
            'partner_id',
            'partner_status')->first();

        if ($check['partner_status'] == 1) {
            return $this->resFailed(700, '要关联的父级已冻结,请勿操作');
        }

        if ($userInfo['partner_role'] >= $check['partner_role']) {
            return $this->resFailed(700, '会员身份不符合!,请勿操作');
        }


        try {


            //更改父级关系
            $update['partner_id'] = $partner_id;
            UserAccount::where('id', $user_id)->update($update);
            ApplyPromoter::where('user_id', $user_id)->update($update);

            //记录绑定时间
            //先重置原先的记录,按照父级的等级找有效记录
            PartnerRelatedLog::where(['user_id' => $user_id, 'status' => 1,'type'=>$check['partner_role']])->update(['status' => 0]);
            $relatelog['user_id'] = $user_id;
            $relatelog['partner_id'] = $partner_id;
            $relatelog['status'] = 1;
            $relatelog['remarks'] = '平台修改关系';
            $relatelog['type'] = $check['partner_role'];
            $is_exist = PartnerRelatedLog::where('user_id', $user_id)->where('partner_id', $partner_id)->count();
            if ($is_exist) {
                PartnerRelatedLog::where('user_id', $user_id)->where('partner_id', $partner_id)->update($relatelog);
            } else {
                PartnerRelatedLog::create($relatelog);
            }


        } catch (\Exception $e) {
            $msg = $e->getMessage();
            return $this->resFailed(700, $msg);
        }

        return $this->resSuccess([], "操作成功!");
    }


    /**
     * 会员信息(包含上下级)
     * @Author hfh_wind
     * @param GetUserPertnerInfoRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetUserInfo(GetUserPertnerInfoRequest $request)
    {
        $user_id = $request['id'];

        //1-找父级,0-找下级
        $type = $request['type'];

        $user_info = UserAccount::where('id', $user_id)->first();

        if (empty($user_info)) {
            return $this->resFareiled(700, "找不到会员!");
        }

        $return['user_info'] = $user_info;
        //父级
        if ($type == 1) {
            $partner_id = $user_info['partner_id'];

            $return['partner'] = [];
            if ($partner_id) {
                $return['partner'] = UserAccount::where([
                    'user_accounts.id'             => $partner_id,
                    'user_accounts.partner_status' => 0
                ])->leftJoin('wx_userinfos',
                    'user_accounts.id', '=', 'wx_userinfos.user_id')->select('wx_userinfos.nickname',
                    'wx_userinfos.headimgurl', 'user_accounts.mobile')->first();
            }
        } else {
            //下级
            $partner_id = $user_info['id'];

            $return['partner'] = [];
            if ($partner_id) {
                $return['partner'] = UserAccount::where([
                    'user_accounts.partner_id'     => $partner_id,
                    'user_accounts.partner_status' => 0
                ])->leftJoin('wx_userinfos',
                    'user_accounts.id', '=', 'wx_userinfos.user_id')->select('wx_userinfos.nickname',
                    'wx_userinfos.headimgurl', 'user_accounts.mobile')->get();
            }
        }

        return $this->resSuccess($return);
    }


    /**
     * 推广员解绑小店
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function UnbindRelated(Request $request)
    {
        $user_id = $request['id']??0;
        if ($user_id <= 0) {
            return $this->resFailed(414, "参数错误!");
        }

        $user_info = UserAccount::where(['id' => $user_id])->select('is_promoter', 'partner_id',
            'partner_role')->first();
//
//        if (empty($user_info) || !$user_info['partner_id'] || $user_info['partner_role'] != 1) {
//            return $this->resFailed(700, "会员身份不符合,无发操作!");
//        }

        try {

            UserAccount::where(['id' => $user_id])->update(['partner_id' => 0]);
            ApplyPromoter::where(['user_id' => $user_id])->update(['partner_id' => 0]);
            //先重置原先的记录
            PartnerRelatedLog::where(['user_id' => $user_id, 'status' => 1])->update(['status' => 0]);

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            return $this->resFailed(700, $msg);
        }

        return $this->resSuccess([],'操作成功!');
    }


    /**
     * 冻结或者解冻会员
     * @Author hfh_wind
     * @param UnfreezePartnersRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function UnfreezePartners(UnfreezePartnersRequest $request)
    {
        $param = $request->only('id','partner_type', 'type');

        //partner_role  0-普通会员,1-推广员,2-小店,3-分销商,4-经销商
        $userInfo = UserAccount::where('id', $param['id'])->first();

        if (empty($userInfo)) {
            return $this->resFailed(700, '找不到会员!');
        }

        //partner_type=1-推广员,2-其他身份
        try {
            if($param['partner_type'] ==1){
                //type 1 冻结  type 2 解冻
                if($param['type'] ==1){
                    $updateData['is_promoter'] = 4; //推广员冻结
                }else{
                    $updateData['is_promoter'] = 1; //推广员解冻
                }
            }else{
                //type 1 冻结  type 2 解冻
                if($param['type'] ==1){
                    $updateData['partner_status'] = 1;
                }else{
                    $updateData['partner_status'] = 0;
                }
            }


            UserAccount::where('id', $param['id'])->update($updateData);


        } catch (\Exception $e) {
            DB::rollback();
            $msg = $e->getMessage();
            return $this->resFailed(700, $msg);
        }

        return $this->resSuccess([], "操作成功!");
    }

    public function exportListsData(Request $request, UserDepositCashesListRepository $Repository)
    {
        $input_data = $request->all();
        $lists = $Repository->search($input_data,true);
        //获取下载表头
        $title=$Repository->listFields();
        $return['trade']['tHeader']= array_column($title,'title'); //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['trade']['list']['data']= $lists; //表头

        return $this->resSuccess($return);
    }
}
