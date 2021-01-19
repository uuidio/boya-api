<?php

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\LotteryRequest;
use ShopEM\Http\Requests\Platform\LotteryReleaseRequest;
use ShopEM\Models\ActivitiesRewardsSendLogs;
use ShopEM\Models\ActivityTickets;
use ShopEM\Models\ActivitiesRewardGoods;
use ShopEM\Models\Lottery;
use ShopEM\Models\LotteryRecord;
use ShopEM\Models\UserTicket;
use ShopEM\Models\ActivitiesRewards;
use ShopEM\Repositories\LotteryRepository;
use ShopEM\Services\QrCode;
use ShopEM\Services\WeChatMini\CreateQrService;
use ShopEM\Services\Marketing\Coupon as CouponAgain;


class LotteryController extends BaseController
{
    /**
     * 抽奖活动列表
     *
     * @Author RJie
     * @param Request $request
     * @param LotteryRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function activityLotteryList(Request $request, LotteryRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['parent_id'] = 0;
        $input_data['gm_id'] = $this->GMID;

        $lists = $repository->activitySearch($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->activityListShowFields(),
        ]);
    }

    /**
     * 抽奖活动添加
     *
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activityLotteryCreate(LotteryReleaseRequest $request)
    {
        $data = $request->only('name', 'status', 'desc', 'use_type', 'luck_draw_num', 'integral' ,'valid_start_at' , 'valid_end_at','is_grade_limit' , 'grade_limit');

        $data['gm_id'] = $this->GMID;
        // $data['delivery_type'] = isset($data['delivery_type'])?json_encode($data['delivery_type']):'';
        $check_data['valid_start_at'] = $data['valid_start_at'];
        $check_data['valid_end_at'] = $data['valid_end_at'];
        try {
            $res = $this->_checkLottery($check_data);
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
            
        }
        if(!$res)
        {
            return $this->resFailed(702, '活动创建失败');
        }

        if(isset($data['is_grade_limit']))
        {
            if($data['is_grade_limit'] == 1)
            {
                if(!isset($data['grade_limit']))
                {
                    return $this->resFailed(702, '必须选择一种等级设置');
                }
                if (empty($data['grade_limit'])||!is_array($data['grade_limit']))
                {
                    return $this->resFailed(702, '必须选择一种等级设置');
                }
                if (!empty($data['grade_limit'])) {
                    
                    $data['grade_limit'] =  implode(',', $data['grade_limit']);
                }
//                foreach ($data['grade_limit'] as $key => $value)
//                {
//                    $grade_limit[$key][$value['card_code']] = $value['number'];
//                }
//                $data['grade_limit']  = json_encode($grade_limit);

            }
            else
            {
                $data['grade_limit'] = '';
            }
        }

        DB::beginTransaction();
        try
        {
            // 添加活动信息
            $add = Lottery::create($data);

            // 修改其他活动信息
            if ($data['status'] == 1 && $data['use_type'] == 0) {
                Lottery::where('parent_id', 0)->where('id', '!=', $add->id)->where('use_type', 0)->where('gm_id' , $data['gm_id'] )->update(['status' => 0]);
            }

            //追加一个谢谢参与的奖项
            $create_data['type'] = '0';
            $create_data['probability'] = '100';
            $create_data['status'] = '1';
            $create_data['name'] = '谢谢参与';
            $create_data['parent_id'] = $add->id;
            $create_data['use_type'] = $add->use_type;
            $create_data['is_show'] = '1';
            $create_data['gm_id'] = $data['gm_id'];
            Lottery::where('gm_id' , $data['gm_id'] )->create($create_data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 抽奖活动删除
     *
     * @Author RJie
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function activityLotteryDelete($id = 0)
    {
        if (intval($id) <= 0)
        {
            return $this->resFailed(414);
        }

        DB::beginTransaction();
        try {
            Lottery::destroy($id);

            Lottery::where('parent_id', $id)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 抽奖活动修改
     *
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activityLotteryUpdate(LotteryReleaseRequest $request)
    {
        $id = $request->input('id');
        if (intval($id) <= 0) {
            return $this->resFailed(414);
        }

        $data = $request->only('name', 'status', 'desc', 'use_type', 'luck_draw_num', 'integral' ,'valid_start_at' , 'valid_end_at','is_grade_limit' , 'grade_limit');

        $gm_id = $this->GMID;
        // $data['delivery_type'] = isset($data['delivery_type'])?json_encode($data['delivery_type']):'';
        
        $check_data['valid_start_at'] = $data['valid_start_at'];
        $check_data['valid_end_at'] = $data['valid_end_at'];
        try {
            $res = $this->_checkLottery($check_data);
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
            
        }
        if(!$res)
        {
            return $this->resFailed(702, '活动创建失败');
        }

        if(isset($data['is_grade_limit']))
        {
            if($data['is_grade_limit'] == 1)
            {
                if(!isset($data['grade_limit']))
                {
                    return $this->resFailed(702, '必须选择一种等级设置');
                }

                if (empty($data['grade_limit'])||!is_array($data['grade_limit']))
                {
                    return $this->resFailed(702, '必须选择一种等级设置');
                }
                if (!empty($data['grade_limit'])) {
                    $data['grade_limit'] =  implode(',', $data['grade_limit']);
                }
//                foreach ($data['grade_limit'] as $key => $value)
//                {
//                    $grade_limit[$key][$value['card_code']] = $value['number'];
//                }
//                $data['grade_limit']  = json_encode($grade_limit);

            }
            else
            {
                $data['grade_limit'] = '';
            }
        }
        DB::beginTransaction();
        try {
            $lottery = Lottery::find($id);
            if (empty($lottery)) {
                return $this->resFailed(700);
            }
            $lottery->update($data);

            // 修改活动状态
            if ($data['status'] == 1) {
                $up_status = 0;
                if ($lottery->use_type == 0)
                {
                    Lottery::where('parent_id', 0)->where('id', '!=', $id)->where('use_type', 0)->where('gm_id' , $gm_id )->update(['status' => $up_status]);
                }
            }
//            else {
//                $up_status = 1;
//            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 抽奖活动详情
     *
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activityLotteryDetail(Request $request)
    {
        $id = $request->input('id', 1);

        $lottery = Lottery::find($id);

        if (empty($lottery)) {
            return $this->resFailed(700);
        }
        if ($lottery->gm_id != $this->GMID) 
        {
            return $this->resFailed(700);
        }

        return $this->resSuccess($lottery);
    }

    /**
     * 设置抽奖规则
     *
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activityLotteryDesc(Request $request)
    {
        $id = $request->input('id', 1);
        $data = $request->only('desc');

        try {
            $lottery = Lottery::find($id);
            if (empty($lottery)) {
                return $this->resFailed(700);
            }
            $lottery->update($data);
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }
        return $this->resSuccess();
    }


    /**
     * 奖项列表
     *
     * @Author RJie
     * @param Request $request
     * @param LotteryRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request, LotteryRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['parent_id'] = $request->input('parent_id', 1);
        $input_data['gm_id'] = $this->GMID;

        $lists = $repository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 创建奖项信息
     * @Author RJie
     * @param LotteryRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(LotteryRequest $request)
    {
//        $activities_reward_goods_id = $request['activities_reward_goods_id']??0;
        $data = $request->only('name', 'type', 'image', 'prize', 'number', 'probability', 'status', 'desc','is_show', 'parent_id', 'ticket_type', 'activities_reward_goods_id','delivery_type');

        $msg_text = "创建奖项" . $data['name'];
        DB::beginTransaction();
        try {
            $data['gm_id'] = $this->GMID;
            $data['delivery_type'] = isset($data['delivery_type'])?json_encode($data['delivery_type']):'';

            if ($data['type'] == 0)
            {
                $data['number'] = 0; // 谢谢惠顾不需要奖品数量
            }

            $data['remnant_num'] = $data['number']; // 同步奖项数量

            $exists_probability = Lottery::where('parent_id' ,$data['parent_id'])->sum('probability');
            $merge_probability = intval($exists_probability) + intval($data['probability']);
            if($merge_probability > 100)
            {
                throw new \Exception("合计概率超过100%，请重新设置");
                
                // return $this->resFailed(702, '');
            }

            $info = Lottery::create($data);
            $activities_reward_goods_id = $data['activities_reward_goods_id'] ?? 0;
            //type ---0:谢谢惠顾，1：积分，2：电影票 3:实物奖品
            if ($data['type'] == 3)
            {
                if (empty($activities_reward_goods_id)) {
                    throw new \Exception("关联活动商品不能为空");
                    // return $this->resFailed(702, '关联活动商品不能为空!');
                }

                $rewardsData['activities_reward_goods_id'] = $data['activities_reward_goods_id'];
                $rewardsData['activities_id'] = $info['id'];
                $rewardsData['type'] = 'choujiang';
                $rewardsData['goods_stock'] = $data['number'];
                $rewardsData['gm_id'] = $data['gm_id'];

                ActivitiesRewards::create($rewardsData);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(702, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess();
    }

    /**
     * 奖项删除
     * @Author RJie
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if (intval($id) <= 0) {
            return $this->resFailed(414);
        }

        DB::beginTransaction();
        try {

            $res = Lottery::find($id);
            if (empty($res)) {
                return $this->resFailed(701);
            }

            //如果还有用户尚未兑换不允许操作
            if ($res['type'] == 3) {
                $res = ActivitiesRewardsSendLogs::where(['activities_reward_id' => $id, 'is_redeem' => 0])->get();
                if (count($res) > 0) {
                    return $this->resFailed(700, "还有未兑换奖品的用户请勿删除!");
                }
                ActivitiesRewards::where('activities_id', $id)->delete();
            }

            Lottery::destroy($id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 奖项修改
     *
     * @Author RJie
     * @param LotteryRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(LotteryRequest $request)
    {
        $id = $request->input('id');
        if (intval($id) <= 0) {
            return $this->resSuccess(414);
        }

        $data = $request->only('name', 'image', 'type', 'prize', 'remnant_num', 'probability', 'status','is_show', 'desc', 'parent_id', 'ticket_type', 'activities_reward_goods_id','delivery_type');
        $data['delivery_type'] = isset($data['delivery_type'])?json_encode($data['delivery_type']):'';

        $msg_text = "修改奖项" . $data['name'];
        //$activities_reward_goods_id = $data['activities_reward_goods_id'] ?? 0;
        DB::beginTransaction();
        try {
            $lottery = Lottery::find($id);
            if (empty($lottery)) {
                return $this->resFailed(701);
            }
            if($lottery->type != $data['type'])
            {
                return $this->resFailed(702, '不能改变奖项类型');
            }
            $old_remnant_num = $lottery->remnant_num;

            $exists_probability = Lottery::where('parent_id' ,$lottery->parent_id)->where('id', '!=' , $id)->sum('probability');
            $merge_probability = intval($exists_probability) + intval($data['probability']);

            if($merge_probability > 100)
            {
                return $this->resFailed(702, '合计概率超过100%，请重新设置');
            }

            if ($data['type'] == 0)
            {
                $data['number'] = 0; // 谢谢惠顾不需要奖品数量
            }else{
                $valid_start_at = Lottery::where('id',$lottery->parent_id)->value('valid_start_at');
                if (strtotime($valid_start_at) <= time()) 
                {
                    if ($data['remnant_num'] < $old_remnant_num) {
                        return $this->resFailed(702, '活动开始后剩余库存修改不可小于当前剩余库存');
                    }
                }
                $data['number'] = $lottery->number + ($data['remnant_num'] - $old_remnant_num);
            }

            //如果是实物商品修改时候做个判断
            $check = ActivitiesRewards::where('activities_id', $id)->first();

            if ($data['type'] == 3 && $check->activities_reward_goods_id != $data['activities_reward_goods_id'])
            {
                return $this->resFailed(702, '不能改变关联商品');
            }

            $res = ActivitiesRewardsSendLogs::where(['activities_reward_id' => $id, 'is_redeem' => 0])->get();
            if (count($res) > 0) {
                return $this->resFailed(700, "还有未兑换奖品的用户请勿操作!");
            }
            if ($check['type'] == 3 && $data['type'] == 3 && isset($data['number'])) {
                $rewardsData['activities_id'] = $id;
                $rewardsData['type'] = 'choujiang';
                $rewardsData['goods_stock'] = $data['number'];
                ActivitiesRewards::where('activities_id', $id)->update($rewardsData);
            }
            
            $lottery->update($data);

            //type ---0:谢谢惠顾，1：积分，2：电影票 3:实物奖品
//            if ($check['type'] == 3 && $data['type'] == 3 && isset($data['number'])) {
//
//                $rewardsData['goods_stock'] = $data['number'];
//                ActivitiesRewards::where('activities_id', $id)->update($rewardsData);
//
//            }
//            elseif ($data['type'] == 3 && $res['activities_reward_goods_id'] != $activities_reward_goods_id) {
//
//                if (empty($activities_reward_goods_id)) {
//                    return $this->resFailed(702, '关联活动商品不能为空!');
//                }
//                //如果还有用户尚未兑换,不允许修改商品类型操作
//                $res = ActivitiesRewardsSendLogs::where(['activities_reward_id' => $id, 'is_redeem' => 0])->get();
//                if (count($res) > 0) {
//                    return $this->resFailed(700, "还有未兑换奖品的用户请勿操作!");
//                }
//
//                $rewardsData['activities_reward_goods_id'] = $activities_reward_goods_id;
////                $rewardsData['activities_id'] = $id;
//                $rewardsData['type'] = 'choujiang';
//                $rewardsData['goods_stock'] = $data['number'];
//                ActivitiesRewards::where('activities_id', $id)->update($rewardsData);
//            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess();
    }

    /**
     * 奖项详情
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $id = $request->input('id', 1);

        $lottery = Lottery::find($id);
        
        if (empty($lottery)) {
            return $this->resFailed(700);
        }
        // $lottery['delivery_type'] = json_decode($lottery['delivery_type'],true);
        
        if ($lottery->gm_id != $this->GMID) 
        {
            return $this->resFailed(700);
        }
        return $this->resSuccess($lottery);
    }

    /**
     * 奖项状态
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function selectStatus(Request $request)
    {
        $id = $request->input('id', 1);
        try {
            $lottery = Lottery::find($id);
            if ($lottery) {
                $status = $lottery->status;
            } else {
                return $this->resFailed(700);
            }
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess($status);
    }

    /**
     * 批量修改状态
     *
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setStatus(Request $request)
    {
        $status = $request->input('status');
        $id = $request->input('id', 1);

        if ($status == 1) {
            $up_status = 0;
        } else {
            $up_status = 1;
        }
        $gm_id = $this->GMID;

        DB::beginTransaction();
        try {
            // 查询抽奖活动信息
            $lottery = Lottery::find($id);
            if (empty($lottery)) {
                return $this->resFailed(700);
            }
            Lottery::where('id', $id)->update(['status' => $status]);

            if ($status == 1)
            {
                if ($lottery->use_type == 0) {
                    Lottery::where('id', '!=', $id)->where('use_type', 0)->where('parent_id',
                        0)->where('gm_id', $gm_id)->update(['status' => $up_status]);
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * 抽奖奖品补发
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reissued(Request $request)
    {
        $id = $request->input('id');
        if (intval($id) <= 0) {
            return $this->resFailed(414);
        }
        $gm_id = $this->GMID;

        try {
            // 查询奖品发放情况
            $record = LotteryRecord::find($id);
            if (empty($record)) {
                return $this->resFailed(700);
            }
            // 判断是否已发放
            if ($record->grant_status != 0) {
                return $this->resFailed(901, '奖品已发放');
            }

            // 查询奖项信息
            $lottery = Lottery::find($record->lottery_id);
            if (empty($lottery)) {
                return $this->resFailed(700);
            }

            // 判断奖项是否领完
            if ($lottery->remnant_num <= 0) {
                return $this->resFailed(901, '奖项已领完');
            }
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }

        //$userCrmConnect = new UserCrmConnect();  // 变更会员积分类
        $yitiangroup_service = new \ShopEM\Services\YitianGroupServices($gm_id);
        DB::beginTransaction();
        try {
            if ($lottery->type == 0) {  // 未中奖(谢谢惠顾)
                $grant_data = [
                    'grant_status' => 2,
                    'grant_time' => date('Y-m-d H:i:s'),
                    'prize' => '谢谢惠顾',
                ];
                $record->update($grant_data);
                // 变更奖品数量
                $lottery->update([
                    'remnant_num' => $lottery->remnant_num - 1
                ]);
            }
            elseif ($lottery->type == 1)
            {  // 发放积分
                $params = [
                    'num' => $lottery->prize,  // 会员积分
                    'user_id' => $record->user_account_id,  // 会员ID
                    'tid' => '0',
                    'remark' => '抽奖发放中奖积分',
                    'behavior' => '抽奖送积分',
                    'type' => 'obtain',  // 添加
                    'log_type' => 'luckDraw',  // 类型
                ];
//                $point = $userCrmConnect->updateUserCrmPoint($params);
                $point = $yitiangroup_service->updateUserYitianPoint($params);
                if ($point !== false) {
                    // 发放记录
                    $grant_data = [
                        'grant_status' => 2,
                        'grant_time' => date('Y-m-d H:i:s'),
                        'prize' => $lottery->prize,
                    ];
                    $record->update($grant_data);

                    // 变更奖品数量
                    $lottery->update([
                        'remnant_num' => $lottery->remnant_num - 1
                    ]);
                } else {
                    return $this->resFailed(902, '积分发放失败');
                }
            }
            elseif ($lottery->type == 2) {
                // 获取兑换票劵
                $movie = ActivityTickets::where([
                    'status' => 0,
                    'user_id' => 0,
                    'ticket_type' => $lottery->ticket_type
                ])->first();
                if (empty($movie)) {
                    return $this->resFailed(901, '电影票已发放完!');
                }
                // 发放记录
                $grant_data = [
                    'grant_status' => 2,
                    'grant_time' => date('Y-m-d H:i:s'),
                    'prize' => $movie->ticket_code,
                ];
                $record->update($grant_data);

                // 更改电影票信息
                $movie->update([
                    'user_id' => $record->user_account_id,
                    'status' => 1,
                    'type' => 'choujiang',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                // 写入票卷
                $ticket_data = [
                    'ticket_name' => $lottery->name,
                    'ticket_code' => $movie->ticket_code,
                    'ticket_source' => 'choujiang',
                    'ticket_image' => $lottery->image,
                    'user_id' => $record->user_account_id,
                    'ticket_price' => 50,
                    'dead_line' => '2020-02-08~2020-02-23',
                    'desc' => $lottery->desc,
                    'type' => $lottery->ticket_type,
                    'gm_id' => $lottery->gm_id,
                ];
                UserTicket::create($ticket_data);
                // 变更奖品数量
                $lottery->update([
                    'remnant_num' => $lottery->remnant_num - 1
                ]);
            }
            elseif ($lottery->type == 3) {  // 发放实物
                // 发放记录
                $grant_data = [
                    'grant_status' => 1,
                    'grant_time' => date('Y-m-d H:i:s'),
                    'prize' => $lottery->prize,
                ];
                $record->update($grant_data);

                // 变更奖品数量
                $lottery->update([
                    'remnant_num' => $lottery->remnant_num - 1
                ]);

                $rewards = ActivitiesRewards::where(['activities_id' => $record->lottery_id])->first();

                // 查询商品信息
                $goods = ActivitiesRewardGoods::find($rewards['activities_reward_goods_id']);
                if ($goods) {
                    $rewardsDatas['goods_name'] = $goods->goods_name;
                    $rewardsDatas['goods_image'] = $goods->goods_image;
                }
                // 查询活动信息
                if ($lottery->parent_id != 0) {
                    $activity = Lottery::find($lottery->parent_id);
                    $rewardsDatas['activities_name'] = $activity->name;
                }

                //实物商品
                $rewardsDatas['activities_id'] = $record->lottery_id;
                $rewardsDatas['activities_reward_id'] = $rewards['id'];
                $rewardsDatas['user_id'] = $record->user_account_id;
                $rewardsDatas['type'] = 'choujiang';
                $rewardsDatas['quantity'] = $record['number'];
                $rewardsDatas['gm_id'] = $record->gm_id;
                ActivitiesRewardsSendLogs::create($rewardsDatas);
            }
            elseif ($lottery->type == 4)
            {
                // 发放优惠劵
                $prize = $lottery->prize; // 优惠劵ID
                $coupon = new CouponAgain();
                $coupon_data = [
                    'coupon_id' => $prize,
                    'user_id' => $record->user_account_id,
                ];
                $result = $coupon->send($coupon_data);

                if(!$result)
                {
                    return $this->resFailed([],'发放失败!');
                }
                else
                {
                    // 发放记录
                    $grant_data = [
                        'grant_status' => 1,
                        'grant_time' => date('Y-m-d H:i:s'),
                        'prize' => $lottery->prize,
                    ];
                    $record->update($grant_data);

                    // 变更奖品数量
                    $lottery->update([
                        'remnant_num' => $lottery->remnant_num - 1
                    ]);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }


    /**
     * 抽奖生成活动二维码
     * @Author hfh_wind
     * @return int
     * @throws \Exception
     */
    public function ActivityLotteryCreateWxMiniQr(Request $request)
    {
        $id = $request['id'] ?? 0;
        $gm_id=$this->GMID;
        if (empty($id)) {
            return $this->resFailed(414, '参数错误!');
        }

        $info = Lottery::where(['id' => $id, 'parent_id' => 0])->first();

        if (empty($info)) {
            return $this->resFailed(700, '找不到抽奖活动数据!');
        }

        //小程序二维码(待定)
        $service = new CreateQrService();
        $scene = "t=c&id=" . $id;
        $page = "pagesB/activity/draw";
        //$page = "pagesA/activity/draw";
        $res = $service->GetWxQr($scene, $page,$gm_id);

        $update['wx_mini_qr'] = $res;
        //如果是需要挂公众号的菜单则需要生成一个地址
//        if (isset($info['wx_mini_page'])) {
        $update['wx_mini_page'] = "pagesB/activity/draw?t=c&id=" . $id;
        //$update['wx_mini_page'] = "pagesA/activity/draw?t=c&id=" . $id;
//        }

        $info->update($update);

        return $this->resSuccess([], '生成成功!');
    }

    /**
     * 生成抽奖活动二维码
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activityQrCode(Request $request)
    {
        $id = $request->input('lottery_id');
        $qr_url = $request->input('qrcode_url');
        if (intval($id) <= 0 || !$qr_url) {
            return $this->resFailed(414);
        }

        try {
            $lottery = Lottery::find($id);
            if (empty($lottery)) {
                return $this->resFailed(700);
            }
            // 获取请求域名
            $url_root = $request->root();

            if ($lottery->qr_code) {
                // 判断文件是否存在
                if (file_exists(public_path($lottery->qr_code))) {
                    return $this->resSuccess($url_root . '/' . $lottery->qr_code);
                }
            }

//            $url = 'http://www.baidu.com';
            $qrcode = new QrCode();
            $qr = $qrcode->create($qr_url);
            if ($qr) {
                // 记录二维码路径
                $lottery->update([
                    'qr_code' => $qr
                ]);
            }
            return $this->resSuccess($url_root . '/' . $qr);

        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }
    }

    /**
     * 判断活动有效性
     * @Author Huiho
     * @return int
     * @throws \Exception
     */
    private function _checkLottery($param)
    {
//        if(!isset($param['valid_start_at']))
//        {
//            throw new \LogicException('活动开始时间不能为空');
//        }
//        if(!isset($param['valid_end_at']))
//        {
//            throw new \LogicException('活动结束时间不能为空');
//        }

        //开始时间
        $start = strtotime($param['valid_start_at']);
        $end = strtotime($param['valid_end_at']);
        //当前时间
        $now = time();

        if($now >= $start)
        {
            throw new \LogicException('活动开始时间要大于当前时间');
        }
        elseif($now >= $end)
        {
            throw new \LogicException('活动结束时间要大于当前时间');
        }
        elseif($start == $end)
        {
            throw new \LogicException('活动结束时间不能等于开始时间');
        }

            return true;

    }


}
