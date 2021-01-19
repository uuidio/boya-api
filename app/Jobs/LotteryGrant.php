<?php

namespace ShopEM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\ActivitiesRewards;
use ShopEM\Models\ActivitiesRewardsSendLogs;
use ShopEM\Models\ActivityTickets;
use ShopEM\Models\ActivitiesRewardGoods;
use ShopEM\Models\Lottery;
use ShopEM\Models\LotteryRecord;
use ShopEM\Models\UserPointErrorLog;
use ShopEM\Models\UserTicket;
use ShopEM\Services\Marketing\Coupon;

class LotteryGrant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->queue = 'lotteryGrant';

        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;

        $user_id = $data['user_id'];  // 会员ID
        $lottery_id = $data['lottery_id'];  // 奖项ID
        $lottery_record_id = $data['lottery_record_id'];  // 抽奖记录

        // 获取奖项信息
        $detail = Lottery::find($lottery_id);
        if (empty($detail)) {
            UserPointErrorLog::create([
                'user_id' => $user_id,
                'behavior_type' => 'obtain',
                'tid' => '0',
                'point' => $detail->prize??0,
                'message' => '抽奖：活动不存在',
                'gm_id' => $detail->gm_id
            ]);
            return false;
        }

        // 判断奖项是否领完
        //if ($detail->remnant_num <= 0 && $lottery_id != 1) {
        $record = LotteryRecord::find($lottery_record_id);
        if ($detail->remnant_num <= 0 || !$record)
        {
            UserPointErrorLog::create([
                'user_id' => $user_id,
                'behavior_type' => 'obtain',
                'tid' => '0',
                'point' => $detail->prize??0,
                'message' => '抽奖：奖项不存在',
                'gm_id' => $detail->gm_id
            ]);
            return false;
        }

        // 判断奖品是否发放
        if ($record->grant_status == 1) {
            UserPointErrorLog::create([
                'user_id' => $user_id,
                'behavior_type' => 'choujiang',
                'tid' => '0',
                'point' => $detail->prize??0,
                'message' => '抽奖：奖品已发放',
                'gm_id' => $record->gm_id
            ]);
            return false;
        }

        // 发放奖品
        DB::beginTransaction();
        try {
            if ($detail->type == 0)
            {
                // 未中奖(谢谢惠顾)
                $grant_data = [
                    'grant_status' => 1,
                    'grant_time' => date('Y-m-d H:i:s'),
                    'prize' => '谢谢惠顾',
                ];
                $record->update($grant_data);
                // 变更奖品数量
//                $detail->update([
//                    'remnant_num' => $detail->remnant_num - 1
//                ]);
            }
//            elseif ($detail->type == 1) {  // 发放积分
//                $params = [
//                    'num' => $detail->prize,  // 会员积分
//                    'user_id' => $user_id,  // 会员ID
//                    'tid' => '0',
//                    'remark' => '抽奖发放中奖积分',
//                    'behavior' => '抽奖送积分',
//                    //'type' => 'obtain',  // 添加
//                    'type' => 'obtain',  // 添加
//                    'log_type' => 'luckDraw',  // 添加
//                ];
//                $point = $yitiangroup_service->updateUserYitianPoint($params);
//                if ($point !== false)
//                {
//                    // 发放记录
//                    $grant_data = [
//                        'grant_status' => 1,
//                        'grant_time' => date('Y-m-d H:i:s'),
//                        'prize' => $detail->prize,
//                    ];
//                    $record->update($grant_data);
//
//                    // 变更奖品数量
//                    $detail->update([
//                        'remnant_num' => $detail->remnant_num - 1
//                    ]);
//                }
//                else
//                {
//                    // 积分未发放
//                    UserPointErrorLog::create([
//                        'user_id' => $user_id,
//                        'tid' => '0',
//                        'behavior_type' => 'obtain',
//                        'point' => $detail->prize??0,
//                        'message' => '抽奖：CRM积分未发放'
//                    ]);
//                    return false;
//                }
//            }
            if ($detail->type == 2)
            {
                // 获取电影票
                $movie = ActivityTickets::where(['status' => 0,
                    'user_id' => 0,
                    'ticket_type' => $detail->ticket_type
                ])->first();
                // 发放记录
                $grant_data = [
                    'grant_status' => 1,
                    'grant_time' => date('Y-m-d H:i:s'),
                    'prize' => $movie->ticket_code,
                ];
                $record->update($grant_data);

                // 更改电影票信息
                $movie->update([
                    'user_id' => $user_id,
                    'status' => 1,
                    'type' => 'choujiang',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                // 写入票卷
                $ticket_data = [
                    'ticket_name' => $detail->name,
                    'ticket_code' => $movie->ticket_code,
                    'ticket_source' => 'choujiang',
                    'ticket_image' => $detail->image,
                    'user_id' => $user_id,
                    'ticket_price' => 50,
                    'dead_line' => '2020-02-08~2020-02-23',
                    'desc' => $detail->desc,
                    'ticket_number' => $movie->ticket_number,
                    'type' => $detail->ticket_type,
                    'gm_id' => $detail->gm_id,
                ];
                UserTicket::create($ticket_data);

                // 变更奖品数量
                $detail->update([
                    'remnant_num' => $detail->remnant_num - 1
                ]);
            }
            if ($detail->type == 3)
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

                // 活动关联实物商品信息
                $rewards = ActivitiesRewards::where(['activities_id' => $lottery_id])->where(['gm_id' => $detail->gm_id])->first();

                // 查询商品信息
                $goods = ActivitiesRewardGoods::find($rewards->activities_reward_goods_id);
                if ($goods) {
                    $rewardsDatas['goods_name'] = $goods->goods_name;
                    $rewardsDatas['goods_image'] = $goods->goods_image;
                }

                // 查询活动信息
                if ($detail->parent_id != 0)
                {
                    $activity = Lottery::find($detail->parent_id);
                    $rewardsDatas['activities_name'] = $activity->name;
                }

                //实物商品
                $rewardsDatas['activities_id'] = $lottery_id;
                $rewardsDatas['activities_reward_id'] = $rewards['id'];
                $rewardsDatas['user_id'] = $user_id;
                $rewardsDatas['type'] = 'choujiang';
                $rewardsDatas['quantity'] = $record->number;
                $rewardsDatas['gm_id'] = $record->gm_id;
                ActivitiesRewardsSendLogs::create($rewardsDatas);
            }
            if ($detail->type == 4)
            {
                // 发放优惠劵
                $prize = $detail->prize; // 优惠劵ID
                $coupon = new Coupon();
                $coupon_data = [
                    'coupon_id' => $prize,
                    'user_id' => $user_id,
                ];
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
                $result = $coupon->send($coupon_data);
                if($result)
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
                }

            }
            DB::commit();
            return true;
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            UserPointErrorLog::create([
                'user_id' => $user_id,
                'tid' => '0',
                'behavior_type' => 'obtain',
                'point' => $detail->prize??0,
                'message' => '抽奖：' . $e->getMessage(),
                'gm_id' => $detail->gm_id,
            ]);
            return false;
        }
    }
}
