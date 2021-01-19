<?php
/**
 * @Filename MemberController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Requests\Platform\ChangeUserPointRequest;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Repositories\UserAccountRepository;
use ShopEM\Services\User\UserPointsService;
use Illuminate\Support\Facades\Auth;
use ShopEM\Models\AdminUsers;
use ShopEM\Models\DownloadLog;
use Illuminate\Http\Request;
use ShopEM\Jobs\DownloadLogAct;

class MemberController extends BaseController {

    /**
     * 会员列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param UserAccountRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request,UserAccountRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['gm_id'] = $this->GMID;

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
        $input_data['gm_id'] = $this->GMID;

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
        $fields = $repository->listShowFields();
        foreach ($fields as $key => $field) {
            if ($field['dataIndex'] == 'default_gm_name') unset($fields[$key]);
        }
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $fields,
        ]);
    }


    /**
     * 获取用户的积分情况
     *
     * @Author djw
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail_point($user_id = 0)
    {
        $user_id = intval($user_id);
        if ($user_id <= 0) {
            return $this->resFailed(414);
        }
        $detail = \ShopEM\Models\UserPoint::select('point_count', 'expired_point', 'id')->find($user_id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 更改用户积分
     *
     * @Author djw
     * @param ChangeUserPointRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePoint(ChangeUserPointRequest $request)
    {
        $data = $request->only('user_id', 'modify_point', 'modify_remark');
        $user_id = $data['user_id'];
        if ($user_id <= 0) {
            return $this->resFailed(702, '会员参数错误');
        }

        $points = \ShopEM\Models\UserPoint::select('point_count')->find($user_id);

        if($data['modify_point'] < 0 && abs($data['modify_point']) > $points['point_count'])
        {
            return $this->resFailed(702, '会员积分不足');
        }

        //平台修改积分
        try
        {
            //平台操作会员积分时，先处理会员的积分过期
            $result = UserPointsService::pointExpiredCount($data['user_id']);
            if(!$result)
            {
                throw new \Exception('会员积分过期处理失败');
            }

            $result = UserPointsService::changePoint($data);
            if(!$result)
            {
                throw new \Exception('会员积分更改失败');
            }
//            $this->adminlog("更改会员积分[USER_ID:{$data['user_id']}]", 1);
        }
        catch(\Exception $e)
        {
//            $this->adminlog("更改会员积分[USER_ID:{$data['user_id']}]", 0);
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess('修改成功');
    }

    /**
     * [filterExport 筛选导出会员]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function filterExport(Request $request)
    {
        $relModel = new \ShopEM\Models\UserRelYitianInfo;
        $input = $request->all();
        if (isset($input['s'])) {
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
                $user_ids = $relModel->where(['new_yitian_user'=>1,'gm_id'=>$this->GMID])->pluck('user_id');
                $model = $model->whereIn('user_accounts.id',$user_ids);
                // $model = $model->where('user_accounts.new_yitian_user', $request->status);
            }
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
            // $value->new_yitian_user = $value->new_yitian_user == 0 ? '否' : '是';
        }

        $return['tHeader'] = ['账号','真实姓名','邮箱','生日','手机号','昵称','性别','创建时间','默认项目'];
        $return['filterVal'] = ['login_account','real_name','email','birthday','mobile','nickname','sex','created_at','default_gm_name'];
        $return['list'] = $res;
        return $this->resSuccess($return);
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

        $input_data['gm_id'] = $this->GMID;
        if (isset($input_data['s'])) {
            unset($input_data['s']);
        }

        $insert['type'] = 'UserAccount';
        $insert['desc'] = json_encode($input_data);
        $insert['gm_id'] = $input_data['gm_id'];

        $res = DownloadLog::create($insert);

        $data['log_id'] = $res['id'];
        //$data['log_id'] = 6;

        DownloadLogAct::dispatch($data);

        return $this->resSuccess('导出中请等待!');
    }

}
