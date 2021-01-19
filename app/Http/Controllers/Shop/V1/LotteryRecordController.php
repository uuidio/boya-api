<?php

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Jobs\LotteryGrant;
use ShopEM\Models\ActivityTickets;
use ShopEM\Models\Lottery;
use ShopEM\Models\LotteryRecord;
use ShopEM\Models\UserRelYitianInfo;
use ShopEM\Models\UserPointErrorLog;
use ShopEM\Repositories\LotteryRecordRepository;


class LotteryRecordController extends BaseController
{
    protected $luck_draw_num = 3;  // 会员每天抽奖次数
    protected $integral = 0; // 抽奖扣减积分

    public function luckDraw(Request $request)
    {
        $input_data = $request->only('lottery_id','gm_id' );
        $input_data['lottery_id'] = $input_data['lottery_id'] ?? 0 ;

        $user_id = $this->user->id;  // 会员ID
        $date = date('Y-m-d');

        if (!isset($input_data['gm_id']))
        {
            return $this->resFailed(901,'参数丢失，请重新进入');
        }
        
        $yitian_user = UserRelYitianInfo::where('user_id' ,$this->user->id)->where('gm_id' ,$input_data['gm_id'])->first();
        if (!$yitian_user) 
        {
            return $this->resFailed(901,'请从小程序首页进入该功能');
        }
        // 查询机器活动
        if(intval($input_data['lottery_id']) <= 0){
            $activity = Lottery::where('parent_id',0)->where('use_type',0)->where('status',1)->where('gm_id' , $input_data['gm_id'])->first();
        }else{
            $activity = Lottery::where('status',1)->where('id',$input_data['lottery_id'])->where('gm_id' , $input_data['gm_id'])->first();
        }

        if(empty($activity)){
            return $this->resFailed(901,'敬请期待');
        }

        $luck_draw_num = $activity->luck_draw_num;  // 设置每天抽奖次数
        $integral = !empty($activity->integral)?$activity->integral:0;  // 设置抽奖扣减积分
        $activity_id = $activity->id;
        $activity_name = $activity->name;
        $activity_type = $activity->use_type;


        $lottery_count = LotteryRecord::where('user_account_id', $user_id)->where('gm_id' , $activity->gm_id)->where('luck_draw_date', $date)->where('activities_id',$activity_id)->count();

        //判断抽奖资格
        if($activity->is_grade_limit == 1)
        {
            $grade_limit = $activity->grade_limit;
            // 开启会员等级的转盘
            //查询会员等级
            $card_code = UserRelYitianInfo::where('user_id' , $user_id)->where('gm_id' , $activity->gm_id)->value('card_type_code');

            //无会员等级会员无法参与
            if(!$card_code)
            {
                return $this->resFailed(901, '您无参与此活动的资格');
            }

           //$verify_data  = array_column($grade_limit , $card_code);
            if(!empty($grade_limit) && is_array($grade_limit))
            {
                $verify_data  = in_array($card_code , $grade_limit);
            }

            //不在活动等级范围内的会员无法参与
            if(!$verify_data)
            {
                return $this->resFailed(901, '您无参与此活动的资格');
            }

//            if ($lottery_count >= $verify_data[0])
//            {
//                return $this->resFailed(901, '已经达到今天可参与活动的次数');
//            }
        }
//        else
//        {
            if ($lottery_count >= $luck_draw_num)
            {
                return $this->resFailed(901, '今日没有抽奖次数了');
            }
//        }


        // 查询机器奖项
        $lottery = Lottery::where('status', 1)->where('parent_id',$activity->id)->where('gm_id',$activity->gm_id)->get();
        if(count($lottery) <= 0){
            return $this->resFailed(901,'敬请期待');
        }
        // 设置中奖值范围
        $draw = $lottery_ids = [];
        $range = 0;
        foreach ($lottery as $key => $value) {
            $draw[$value->id] = ($value->probability * 1000) + $range;
            $range += ($value->probability * 1000);
            $lottery_ids[] = $value->id;  // 奖项ID
        }
        $draw[] = 100 * 1000;  // 谢谢惠顾 概率

        $rid = $this->get_rand($draw); //根据概率获取奖项id

//        $thank_lottery = Lottery::where('parent_id',$activity->id)->where('status', 1)->where('type',0)->where('gm_id',$activity->gm_id)->first();
        $thank_lottery = Lottery::where('parent_id',$activity->id)->where('type',0)->where('gm_id',$activity->gm_id)->first();
        // 判断奖项ID是否存在
        if (!in_array($rid, $lottery_ids)) {
            $detail = $thank_lottery;  // 谢谢惠顾
        }else{
            $detail = Lottery::find($rid);
        }

        // 判断奖项是否领完
        if (!empty($detail) && $detail->remnant_num <= 0 && $rid != 1) {
            $detail = $thank_lottery;
        }

        // 判断电影票是否还有库存
        if($detail->type == 2)
        {
            $movie = ActivityTickets::where('status', 0)->where('user_id', 0)->where('gm_id', $detail->gm_id)->first();
            if (empty($movie)) {
                $detail = $thank_lottery;
            }

            // 判断是否已抽中过电影票
            $oneMovie = ActivityTickets::where('status',1)->where('user_id',$user_id)->where('type','choujiang')->where('gm_id', $detail->gm_id)->first();
            if($oneMovie){
                $detail = $thank_lottery;
            }
        }

        $winning = $detail->type == 0 ? 0 : 1; // 中奖状态

        $yitiangroup_service = new \ShopEM\Services\YitianGroupServices($detail->gm_id);

        //扣积分行为
        try
        {
            if($integral > 0)
            {
                // 扣减积分
                $params = [
                    'num' => $integral,  // 会员积分
                    'user_id' => $user_id,  // 会员ID
                    'tid' => '0',
                    'remark' => '抽奖消耗积分',
                    'behavior' => '抽奖消耗积分',
                    'type' => 'consume',  // 消耗
                    'log_type' => 'luckDraw',  // 添加
                ];
                $point = $yitiangroup_service->updateUserYitianPoint($params);
                if($point)
                {
                    $point = true;
                }
            }
            else
            {
                $point = true;
            }
            if($point !== false)
            {
                // 扣减积分成功
                // 记录抽奖信息
                $data = [
                    'user_account_id' => $user_id,
                    'lottery_id' => $detail->id,
                    'lottery_name' => $detail->name,
                    'number' => 1,
                    'luck_draw_date' => $date,
                    'status' => $winning,
                    'activities_id' => $activity_id,
                    'activities_name' => $activity_name,
                    'activities_type' => $activity_type,
                    'integral' => $integral,
                    'is_show' => $detail->is_show,
                    'gm_id' => $detail->gm_id,

                ];
                $add = LotteryRecord::create($data);
            }
            else
            {
                return $this->resFailed(901,'积分不足');
            }
        }
        catch (\Exception $e)
        {
            return $this->resFailed(701, $e->getMessage());
        }

        //如果是积分商品单独发放
        if($detail->type == 1)
        {
            $record = LotteryRecord::find($add->id);
            $params = [
                'num' => $detail->prize,  // 会员积分
                'user_id' => $user_id,  // 会员ID
                'tid' => '0',
                'remark' => '抽奖发放中奖积分',
                'behavior' => '抽奖送积分',
                'type' => 'obtain',  // 添加
                'log_type' => 'luckDraw',  // 添加
            ];
            $point_send = $yitiangroup_service->updateUserYitianPoint($params);
            if ($point_send)
            {
                // 发放记录
                $grant_data = [
                    'grant_status' => 1,
                    'grant_time' => date('Y-m-d H:i:s'),
                    'prize' => $detail->prize,
                ];
                $record->update($grant_data);

                // 变更奖品数量
                $detail->update([
                    'remnant_num' => $detail->remnant_num - 1
                ]);
                return $this->resSuccess($detail);
            }
            else
            {
                // 积分未发放
                UserPointErrorLog::create([
                    'user_id' => $user_id,
                    'tid' => '0',
                    'behavior_type' => 'obtain',
                    'point' => $detail->prize??0,
                    'message' => '抽奖：CRM积分未发放'
                ]);
                return $this->resFailed($detail,'发放奖品失败,请联系平台补发');
            }
        }
        else
        {
            // 队列发放奖品
            $params = [
                'user_id' => $user_id,
                'lottery_id' => $detail->id,
                'lottery_record_id' => $add->id,
                'luck_draw_num' => $luck_draw_num,
                'gm_id' => $detail->gm_id,
                'date' => $date
            ];
            LotteryGrant::dispatch($params);

        }

        return $this->resSuccess($detail);

    }

    /**
     * 抽奖
     * @Author RJie
     * @param $proArr
     * @return int|string
     */
    public function get_rand($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = max($proArr);

        $randNum = mt_rand(1, $proSum); //返回随机整数
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            }
        }

        return $result;
    }

    /**
     * 中奖记录
     *
     * @Author RJie
     * @param Request $request
     * @param LotteryRecordRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request,LotteryRecordRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = 15;
        $input_data['grant_status'] = 1;

        $input_data['gm_id'] = LotteryRecord::where('activities_id' , $input_data['activities_id'])->value('gm_id');
        $is_show = strstr($request->url(),'lottery/record/show'); // 判断路由地址
        if(!$is_show)
        {
            unset($input_data['activities_id']);
            $input_data['user_account_id'] = $this->user->id;
            $lists = $repository->search($input_data);
        }
        else
        {
            $input_data['per_page'] = 50;
            $lists = $repository->listShow($input_data);
        }

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        $lists = $lists->toArray();
        foreach ($lists['data'] as $k => $info) {
            $lists['data'][$k]['grant_time'] = date('Y-m-d H:i',strtotime($info['grant_time']));
            if ($info['user_account_name'] != '匿名') {
                $lists['data'][$k]['user_account_name'] = substr_replace($info['user_account_name'], '****',3, 4);
            }
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 奖项信息
     *
     * @Author RJie
     * @return \Illuminate\Http\JsonResponse
     */
    public function lotteryAll(Request $request)
    {
        $input_data = $request->all();
        if (!isset($input_data['gm_id']))
        {
            $input_data['gm_id'] = $this->GMID;
        }
        $detail = Lottery::where('parent_id',0)->where('status',1)->where('use_type',0)->where('gm_id' , $input_data['gm_id'])->first();
        if($detail) {
            $all = Lottery::where('status', 1)->where('parent_id', $detail->id)->where('use_type', 0)->get();
            foreach($all as $key => $val){
                $base64_image = '';
                $image_file = $val->image;
                if($image_file) {
                    $image_info = getimagesize($image_file);
                    $image_data = "" . chunk_split(base64_encode(file_get_contents($image_file)));
                    $base64_image = 'data:' . $image_info['mime'] . ';base64,' . $image_data;
                }
                $all[$key]['base64_image'] = $base64_image;
            }
        }else{
            $all = [];
        }
        return $this->resSuccess($all);
    }

    public function lotteryDetail(Request $request)
    {
        $input_data = $request->all();
        if (!isset($input_data['gm_id']))
        {
            $input_data['gm_id'] = $this->GMID;
        }
        $detail = Lottery::where('parent_id',0)->where('status',1)->where('use_type',0)->where('gm_id',$input_data['gm_id'])->first();

        if(!$detail)
        {
            return $this->resFailed([],'无效活动');
        }
        else
        {
            $check_data['valid_start_at'] = $detail->valid_start_at;
            $check_data['valid_end_at'] = $detail->valid_end_at;
            $res = $this->_checkLottery($check_data);
            if(!$res)
            {
                return $this->resFailed([],'无效活动');
            }
        }
        return $this->resSuccess($detail);
    }

    public function getLotteryAll($id = 0)
    {
        if(intval($id) <= 0){
            return $this->resFailed(414);
        }
        $all = Lottery::where('status',1)->where('parent_id','!=','0')->where('use_type',0)->get();
        return $this->resSuccess($all);
    }

    /**
     * 根据抽奖活动ID查询活动信息
     *
     * @Author RJie
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLotteryDetail($id = 0)
    {
        if(intval($id) <= 0){
            return $this->resFailed(414);
        }

        try
        {
            $lottery = Lottery::find($id);
            if(empty($lottery)){
                return $this->resFailed(700);
            }

            // 查询奖项信息
            $res = Lottery::where('parent_id',$id)->where('status',1)->get();

            $lottery['lottery_detail'] = $res;
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess($lottery);
    }



    /**
     * 判断活动有效性
     * @Author Huiho
     * @return int
     * @throws \Exception
     */
    private function _checkLottery($param)
    {
        if(!isset($param['valid_start_at']))
        {
            throw new \LogicException('无效活动,活动开始时间不能为空');
        }
        if(!isset($param['valid_end_at']))
        {
            throw new \LogicException('无效活动,活动结束时间不能为空');
        }

        //开始时间
        $start = strtotime($param['valid_start_at']);
        $end = strtotime($param['valid_end_at']);
        //当前时间
        $now = time();

        if($now <= $start)
        {
            throw new \LogicException('活动未开启,敬请期待');
        }
        elseif($now >= $end)
        {
            throw new \LogicException('活动已结束');
        }

        return true;

    }


}
