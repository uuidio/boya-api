<?php

/**
 * @Author: swl
 * @Date:   2020-03-09 
 */
namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Http\Request;
use ShopEM\Models\MemberActivity;
use ShopEM\Repositories\MemberActivityRepository;

class MemberActivityController extends BaseController
{
     
     /**
     * 活动列表
     * swl 2020-4-9
     */

    public function lists(Request $request,MemberActivityRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = $input_data['per_page']??config('app.per_page');

        $lists = $repository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700, errorMsg(700));
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->activityListField()
        ]);
    }


    /**
     * 审核平台活动
     * @Author swl 
     * @param status 1:通过 2：驳回
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request){

        $data = $request->only('id','status','remark');
        if(!isset($data['id']) || $data['id']<=0){
             return $this->resFailed(700);
        }
        if(empty($data['status'])){
            return $this->resFailed('请上传要更新的状态');
        }

        $update = [
            'verify_status'=>$data['status'],
            'verify_remark'=>$data['remark']??''
        ];
 
        try {
            MemberActivity::where('id',$data['id'])->update($update);

        } catch (\Exception $e) {

            return $this->resFailed(702, $e->getMessage());
        }
       return $this->resSuccess('更新成功！');

    }
}
