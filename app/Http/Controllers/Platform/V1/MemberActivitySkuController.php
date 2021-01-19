<?php
/**
 * @Filename  MemberActivitySkuController.php
 * @Author    swl
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Requests\Platform\MemberActivitySkuRequest;
// use ShopEM\Repositories\MemberActivitySkuRepository;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\MemberActivitySku;
use ShopEM\Models\MemberActivity;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MemberActivitySkuController extends BaseController
{

    
    /*
    * 添加活动场次
    * swl 2020-4-8
    */
    public function save(MemberActivitySkuRequest $request){

         $activity_id = $request->activity_id;
         if(empty($activity_id)){
            return $this->resFailed(414,'缺少活动id');
        }

        $activity = MemberActivity::find($activity_id);

        if(empty($activity)){

            return $this->resFailed(414,'该活动不存在');    
        }

        $data = $request->only('activity_id','place','money','point','voucher','max_people_num','min_people_num','apply_way','apply_start_time','apply_end_time','activity_start_time','activity_end_time','allow_apply_card');
        
        if(!empty($data['allow_apply_card'])){
            $data['allow_apply_card'] = implode(',', $data['allow_apply_card']);
        }
        $data['money'] =  $data['money']??0;
        $data['point'] =  $data['point']??0;

        //验证数据
        $this->__checkAddPost($data);

        // 报名方式：1：免费 2、金额 3、积分/牛币 4、积分+金额 5、凭证
        if(in_array($data['apply_way'], array(1,3,5))){
             $data['money'] =  0 ;
        }
        if(in_array($data['apply_way'], array(1,2,5))){
             $data['point'] =  0 ;
        }

        try {
            
            MemberActivitySku::create($data);
           
        } catch (\Exception $e) {
 
            return $this->resFailed(702, $e->getMessage());
        }
        return $this->resSuccess();     
    }


    /**
     *  添加活动验证信息
     * swl
     */

    private function __checkAddPost($ruledata)
    {
        $rules = [
            'time'             => date('Y-m-d H:i:s', time()),
            'apply_start_time' => 'after:time',
            'apply_end_time'   => 'after:apply_start_time',
            'activity_start_time'       => 'after:apply_end_time',
            'activity_end_time'         => 'after:activity_start_time',
        ];
        $messages = [
            'apply_start_time.after' => '活动报名的开始时间必须大于当前时间',
            'apply_end_time.after'   => '活动报名结束时间必须大于报名的开始时间',
            'activity_start_time.after'       => '活动开始时间必须大于活动报名结束时间',
            'activity_end_time.after'         => '活动结束时间必须大于活动开始时间',
        ];
        
        $validator = Validator::make($ruledata, $rules, $messages);
        $error = [];
        $error = $validator->errors()->all();
        
        // 报名方式是金额或金额加积分的时候，报名金额需大于等于0.01
        if(in_array($ruledata['apply_way'], array(2,4))){
            if($ruledata['money'] < 0.01){
                $error[] = '报名金额需要大于等于0.01';
            }
        }
        // 报名方式是积分或金额加积分的时候，报名积分需大于等于0
        if(in_array($ruledata['apply_way'], array(3,4))){
            if($ruledata['point'] < 1){
                $error[] = '报名积分不能小于1';
            }
        }
        if ($error) {
            throw new HttpResponseException(response()->json([
                'code' => '414',
                'message'   => $error,
                'result'    => []
            ], 200));
        } else {
            return true;
        }
    }


    /*
    * 更新活动场次
    * swl 2020-4-8
    */
    public function update(MemberActivitySkuRequest $request){

        $id = $request->id;
        if ($id <= 0) {
            return $this->resFailed(414, '场次id必传!');
        }

        $activityInfo = MemberActivitySku::find($id);
    
        $nowTime = date('Y-m-d H:i:s', time());
      
        if ($nowTime > $activityInfo['activity_start_time']) {
            return $this->resFailed(414, '活动时间已经开始，不可以对其活动进行操作！');
        }
        
        $data = $request->only('activity_id','place','money','point','voucher','max_people_num','min_people_num','apply_way','apply_start_time','apply_end_time','activity_start_time','activity_end_time','apply_member_level');
        $data['money'] =  $data['money']??0;
        $data['point'] =  $data['point']??0;

        //验证数据
        $this->__checkAddPost($data);

        // 报名方式：1：免费 2、金额 3、积分/牛币 4、积分+金额 5、凭证
        if(in_array($data['apply_way'], array(1,3,5))){
             $data['money'] =  0 ;
        }
        if(in_array($data['apply_way'], array(1,2,5))){
             $data['point'] =  0 ;
        }

        try {
            
            MemberActivitySku::where('id',$id)->update($data);
           
        } catch (\Exception $e) {
 
            return $this->resFailed(702, $e->getMessage());
        }
        return $this->resSuccess();     
    }


     /**
     *  活动场次详情
     *
     * @Author djw
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = MemberActivitySku::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }


     /**
     * 删除活动场次
     * @Author swl
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $ativity = MemberActivitySku::find($id);
        if (empty($ativity)) {
            return $this->resFailed(414, '没有此活动场次');
        }
        $now = time();
        $star = strtotime($ativity->activity_start_time);
        $stop = strtotime($ativity->activity_end_time);
        if ($now > $star && $now < $stop) {
            return $this->resFailed(414, '该活动场次进行中，不能删除!');
        }
        try {
            MemberActivitySku::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

}