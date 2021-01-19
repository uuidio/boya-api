<?php
/**
 * @Filename MemberActivityApplyController.php
 *
 * @Author swl
 */

namespace ShopEM\Http\Controllers\Platform\V1;


use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\MemberActivityApply;
use ShopEM\Repositories\MemberActivityApplyRepository;

class MemberActivityApplyController extends BaseController
{
    
    /**
    * 获取报名列表
    * @Author  swl 2020-4-22
    */
    public function lists(Request $request,MemberActivityApplyRepository $repository){

        $data = $request->all();
        $data['per_page'] = $data['per_page']??config('app.per_page');
        $data['gm_id'] = $this->GMID;
        $lists = $repository->search($data);

        if (empty($lists)) {
            return $this->resFailed(700, errorMsg(700));
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->activityListField()
        ]);
    }

     /**
    * 审核报名
    * @Author  swl 2020-4-23
    * @param 报名状态：1、待审核 2、报名通过 3、报名失败
    */

     public function verify(Request $request){

        $data = $request->only('id','status','remark');
        // dd($data);
        if(!isset($data['id']) || $data['id']<=0){
             return $this->resFailed(700);
        }
        if(empty($data['status']) || $data['status'] <=1){
            return $this->resFailed('请上传正确的更新的状态');
        }

        $update = [
            'verify_status'=>$data['status'],
            'verify_remark'=>$data['remark']??''
        ];
 
        try {
            MemberActivityApply::where('id',$data['id'])->update($update);

        } catch (\Exception $e) {

            return $this->resFailed(702, $e->getMessage());
        }
       return $this->resSuccess('更新成功！');
     }
}