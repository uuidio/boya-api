<?php

/**
 * @Author: swl
 * @Date:   2020-03-10
 */
namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Support\Facades\Redis;
use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Models\ApplyPromoter;
use ShopEM\Models\RelatedLogs;
use ShopEM\Models\UserAccount;
use ShopEM\Repositories\UserAccountRepository;
use ShopEM\Repositories\UserPointLogsRepository;
use ShopEM\Services\User\UserPointsService;
use Illuminate\Support\Facades\Auth;
use ShopEM\Models\AdminUsers;
use Illuminate\Http\Request;
use ShopEM\Models\DownloadLog;
use ShopEM\Jobs\DownloadLogAct;


class MemberController extends BaseController
{
	 /**
     * 会员列表
     *
     * @Author swl
     * @param Request $request UserAccountRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request,UserAccountRepository $repository)
    {
        $input_data = $request->all();
        $lists = $repository->allListItems($input_data);
        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields('group'),
        ]);
    }

    /**
     * 获取用户的积分明细
     *
     * @Author swl
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pointLogList(Request $request,UserPointLogsRepository $repository)
    {

        $input_data = $request->all();
        $lists = $repository->listItems($input_data);
        if (empty($lists)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * [search 筛选会员列表]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function search(Request $request)
    {
        $repository = new UserAccountRepository();
        $input_data = $request->all();

        $input_data['per_page'] = request('size', config('app.per_page'));

        $lists = $repository->listItems($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        $lists = $lists->toArray();
        foreach ($lists['data'] as $key => &$value) {
            if ($value['birthday']) {
                $value['birthday'] = date('Y-m-d',$value['birthday']);
            } else {
                $value['birthday'] = '未知';
            }
        }
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields('group'),
        ]);
    }

     /**
     * [filterExport 筛选导出会员]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function filterExport(Request $request)
    {
        $input = $request->all();
       if (isset($input['s']))
       {
           unset($input['s']);
        }
        $model = \ShopEM\Models\UserAccount::select('user_accounts.*','wx_userinfos.nickname','wx_userinfos.sex','wx_userinfos.real_name','wx_userinfos.birthday','wx_userinfos.email')
            ->leftJoin('wx_userinfos', 'wx_userinfos.user_id','=','user_accounts.id');
        if ($request->filled('from')) {
            $model = $model->whereDate('user_accounts.created_at','>=',$request->from);
       }
        if ($request->filled('to')) {
            $model = $model->whereDate('user_accounts.created_at','<=',$request->to);
        }
        if ($request->filled('status')) {
            if ($request->status == 1) {
               $model = $model->where('user_accounts.new_yitian_user', $request->status);
            }
        }

        if ($request->filled('gm_id')) {
               $gm_id =$request->gm_id;
               $model = $model->leftJoin('user_rel_yitian_infos', 'user_rel_yitian_infos.user_id','=','user_accounts.id')
                                ->whereIn('user_rel_yitian_infos.gm_id', $gm_id)
                                ->where('user_rel_yitian_infos.default', 1);

        }

        $res = $model->get();
        /*
         *组装导出表结构
         */
        foreach ($res as $key => &$value) {
            $value->sex      = $value->sex == 0?'未知':($value->sex == 1?'男':'女');
            if ($value->birthday) {
                $value->birthday = date('Y-m-d',$value->birthday);
            } else {
                $value->birthday = '未知';
            }
            $value->new_yitian_user = $value->new_yitian_user == 0 ? '否' : '是';

        }

        $return['tHeader'] = ['账号','真实姓名','邮箱','出生日期','手机号','昵称','性别','创建时间','默认项目','已注册项目'];
        $return['filterVal'] = ['login_account','real_name','email','birthday','mobile','nickname','sex','created_at', 'default_gm_name' , 'user_register_platform'];
        $return['list'] = $res;
        return $this->resSuccess($return);
    }


    /**
     * 后台授权会员成为推广员
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function becomePromoter(Request $request)
    {
        $param = $request->only('id', 'status', 'department_id');

        $id=$param['id']??0;
        if ($id <= 0) {
            return $this->resFailed(414, '会员参数错误');
        }

        $userInfo = UserAccount::where('id', $id)->first();

        if (empty($userInfo)) {
            return $this->resFailed(700, '找不到会员!');
        }

//        $department = Department::find($department_id);
//
//        if (empty($department)) {
//            return $this->resFailed(700, '部门对应信息找不到!');
//        }

        try {
            if (in_array($param['status'], [1, 4])) {
                if ($param['status'] == 1) {
                    if ($userInfo['is_promoter'] == 1) {
                        return $this->resFailed(702, '该用户已是推广员,请勿重复授权!');
                    }

                    if ($userInfo['is_promoter'] == 0) {
                        $createData = array(
                            'user_id'       => $id,
                            'mobile'        => $userInfo['mobile'],
                            'real_name'     => $userInfo['login_account'],
                            'apply_status'  => 'success',
                            'register_type' => 'pla',
                            'partner_role' => 1,
                            //'department_id' => $department_id,
                        );
                        $updateData = array(
                            'is_promoter'   => $param['status'],
                            'partner_role'   =>1,
                            //'department_id' => $department_id,
                        );

                        $promoter = UserAccount::where('id', $id)->update($updateData);
                        ApplyPromoter::create($createData);
                        //推广员身份推物无需绑定他人
                        $partner_key = "partner_type_1_" . $id;
                        Redis::set($partner_key,$id);

                        //老数据过期
                        $update_out_time['created_at'] = date('Y-m-d H:i:s', time());
                        $update_out_time['status'] = 0;
                        RelatedLogs::where([
                            'user_id' => $id,
                            'status'  => 1
                        ])->update($update_out_time);
                    }

                    if ($userInfo['is_promoter'] == 4) {
                        $updateData = array(
                            'is_promoter' => $param['status'],
                        );
                        $promoter = UserAccount::where('id', $id)->update($updateData);
                    }

                }

                if ($param['status'] == 4) {
                    if ($userInfo['is_promoter'] == 0) {
                        return $this->resFailed(702, '该用户不是推广员,无法冻结!');
                    }
                    $updateData = array(
                        'is_promoter' => $param['status'],
                    );
                    $promoter = UserAccount::where('id', $id)->update($updateData);
                }

            } else {
                return $this->resFailed(701, "状态错误!");
            }

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            return $this->resFailed(600, $msg);
        }


        return $this->resSuccess([], '操作成功');
    }


    /**
     * 推广员列表
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchExtension(Request $request)
    {
        $repository = new UserAccountRepository();
        $input_data = $request->all();

        $input_data['per_page'] = request('size', config('app.per_page'));
        $input_data['is_woa'] = request('is_woa', 0);
        $input_data['partner_role'] = 1;

        $lists = $repository->extensionList($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        $lists = $lists->toArray();


        foreach($lists['data']  as $key=> $value){
            $relatedLogs = RelatedLogs::where(['pid' => $value['id']])->count();
            $lists['data'][$key]['goods_spread'] = $relatedLogs ? $relatedLogs : 0;
        }

        $listShowFields = $input_data['is_woa'] ? $repository->woaListShowFields() : $repository->extensionShowFields();
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $listShowFields,
        ]);
    }

    /**
     * 会员信息导出
     * @Author Huiho
     * @param UserAccountRepository $repository
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userAccountDown(Request $request)
    {
        $input_data = $request->all();

        if (isset($input_data['s']))
        {
            unset($input_data['s']);
        }

        $insert['type'] = 'UserAccount';
        $insert['desc'] = json_encode($input_data);
        $insert['gm_id'] = 0;

        $res = DownloadLog::create($insert);

        $data['log_id'] = $res['id'];

        DownloadLogAct::dispatch($data);

        return $this->resSuccess('导出中请等待!');
    }


}
