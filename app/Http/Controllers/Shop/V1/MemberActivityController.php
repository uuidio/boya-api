<?php
/**
 * @Filename  MemberActivityController.php
 * @Author   swl 2020-4-9
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Repositories\MemberActivityRepository;
use ShopEM\Models\MemberActivity;
use ShopEM\Models\MemberActivitySku;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\UserAccount;
use ShopEM\Models\YiTianUserCard;
use ShopEM\Models\MemberActivityApply;
use ShopEM\Models\UserPointLog;


class MemberActivityController extends BaseController
{

    /**
    * 获取主活动列表
    * @Author  swl 2020-4-9
    */
    public function lists(Request $request,MemberActivityRepository $repository){
        $data = $request->all();
        if (!isset($data['gm_id'])) 
        {
            $data['gm_id'] = $this->GMID;
        }
        $data['per_page'] =  $data['per_page']??config('app.per_page');    
        // 获取展示，以及审核通过的活动
        $data['is_show'] = 1;
        $data['verify_status'] = 1;
        $lists = $repository->searchActivity($data);
        return $this->resSuccess($lists);     
    }

    /**
    * 获取活动详情
    * @Author  swl 2020-4-9
    */
    public function detail(Request $request){
        
        if (!$request->has('id')) {
            return $this->resFailed(406, '参数错误!');
        }
        $id = $request->id;
        $activity = MemberActivity::find($id);
        if(empty($activity)){
            return $this->resFailed(406, '活动不存在!');
        }
        $activity['sku'] =  MemberActivitySku::where('activity_id',$id)->get();
        return $this->resSuccess($activity);     

     }

     /**
    * 会员报名活动
    * @Author  swl 2020-4-9
    */
     public function apply(Request $request){
        $data = $request->only('activity_id','activity_sku_id','vocher');
        $data['user_id'] = $this->user->id;
        $data['gm_id'] = $request->gm_id??$this->GMID;
        // $data['user_id'] = 64;
        DB::beginTransaction();
        try {
            
           $res = $this->isCanApply($request);
           if(!$res['status']){
                throw new \Exception($res['msg']);
           }
           //生成报名记录
           MemberActivityApply::create($data);
           DB::commit();

        } catch (\Exception $e) {
             DB::rollback();
            return $this->resFailed(406, $e->getMessage());
        }
        return $this->resFailed('success');
     }

     // 判断是否可以报名
     public function isCanApply($request){
        $data = $request->all();
        $data['gm_id'] = $data['gm_id']??$this->GMID;
        $user_id = $this->user->id;
        // $user_id = 64;
        $activity = MemberActivitySku::find($data['activity_sku_id']);
        // $activity['allow_apply_card'] = '954,444,9527';
          // 获取允许报名的会员卡号
        $cards = explode(',', $activity['allow_apply_card']);
        $myCode = UserAccount::select('card_code')->find($user_id);
        $result = ['status'=>true];
        // 判断等级是否符合报名
        if(!in_array($myCode->card_code,$cards)){
            $result['status'] = false;
            $result['msg'] = '你的会员等级无法参与活动!'; 
            return $result;

        }else{
            $res = YiTianUserCard::where(['card_code'=>$myCode->card_code,'gm_id'=>$data['gm_id']])->first();
            if(empty($res)){            
                $result['status'] = false;
                $result['msg'] = '你不是该平台会员!';
                return $result;
            }

        }
        //判断积分是否足够
        if($activity['point']>0){
            $pointMol = new \ShopEM\Models\UserPoint;
            $point = $pointMol->find($user_id);

            if($point['point_count']<$activity['point']){
                $result['status'] = false;
                $result['msg'] = '会员积分不够!';
            }else{
                //生成积分消费记录
                $surplus = $point['point_count'] - $activity['point'];
                $point->update(['point_count'=>$surplus]);
                $log = [
                    'behavior_type'=>'consume',
                    // 'behavior'=>'报名活动扣减',
                    'behavior'=>'报名活动扣减积分',
                    'point'=>$activity['point'],
                    'gm_id'=>$data['gm_id'],
                    'user_id'=>$user_id
                ];
                UserPointLog::create($log);
            }
        }  

        return $result;
     }

}