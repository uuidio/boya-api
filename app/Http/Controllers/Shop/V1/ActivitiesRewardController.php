<?php
/**
 * @Filename        ActivitiesRewardController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\ActivitiesTransmitCreateTradeRequest;
use ShopEM\Models\ActivitiesRewardGoods;
use ShopEM\Models\ActivitiesRewards;
use ShopEM\Models\ActivitiesRewardsSendLogs;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\ActivityBargainsDetails;
use ShopEM\Models\Lottery;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\UserAddress;
use ShopEM\Models\WxUserinfo;
use ShopEM\Services\TradeService;
use ShopEM\Repositories\ActivitiesRewardsSendLogsRepository;

class ActivitiesRewardController extends BaseController
{


    /**
     * 会员获奖列表
     * @Author hfh_wind
     * @param Request $request
     * @param ActivitiesRewardsSendLogsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function RewardUserList(Request $request, ActivitiesRewardsSendLogsRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = $input_data['per_page'] ?? config('app.per_page');
        $input_data['user_id'] = $this->user->id;
//        if (!isset($input_data['gm_id']))
//        {
//            $input_data['gm_id'] = $this->GMID;
//        };
        $lists = $repository->Search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 会员获奖明细
     * @Author hfh_wind
     * @param Request $request
     * @param ActivitiesRewardsSendLogsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function RewardUserDetail(Request $request)
    {
        if (!$request['id'])
        {
            return $this->resFailed(414, '参数错误!');
        }
        $user_id = $this->user->id;

//        if (!isset($request['gm_id']))
//        {
//            $gm_id = $this->GMID;
//        };

//        $info = ActivitiesRewardsSendLogs::where(['id' => $request['id'], 'user_id' => $user_id,'gm_id' => $gm_id])->first();
        $info = ActivitiesRewardsSendLogs::where(['id' => $request['id'], 'user_id' => $user_id])->first();

        if (empty($info))
        {
            return $this->resFailed(700, '奖品不存在!');
        }
        return $this->resSuccess($info);
    }


    /**
     * 会员领奖(生成订单)
     * @Author hfh_wind
     * @return int
     */
    public function RewardCreateTrade(ActivitiesTransmitCreateTradeRequest $request)
    {

        $request = $request->all();

        $user_id = $this->user->id;
        $wx = WxUserinfo::select('nickname')->where('user_id',$user_id)->first();

        $pick_type = request('pick_type',0);
        if ($pick_type == 0)
        {
            if ((!isset($request['addr_id']) || !$request['addr_id'])) {
                return $this->resFailed(414, '收货地址无效');
            } else {
                $address = UserAddress::where('user_id', $user_id)->where('id', $request['addr_id'])->first();
                //快递地址
                if (empty($address))
                {
                    return $this->resFailed(414, '收货地址无效');
                }
            }
        }

       // if (!isset($request['gm_id']))
       // {
       //     $gm_id = $this->GMID;
       // };

        DB::beginTransaction();
        try {

            $user_reward = ActivitiesRewardsSendLogs::where([
                'id'      => $request['rewards_send_id'],
                'user_id' => $user_id,
                //'gm_id'   => $gm_id
            ])->first();


            if (empty($user_reward)) {
                throw new \Exception("暂无获奖信息");
                // return $this->resFailed(700, '暂无获奖信息!');
            }

            if ($user_reward['is_redeem'] != 0) {
                throw new \Exception("已经领取请勿重复操作");
                // return $this->resFailed(700, '已经领取请勿重复操作!');
            }

            //处理砍价商品
            if ($user_reward['type'] == 'kanjia') {

                $detail = ActivityBargainsDetails::where('id', $user_reward['activities_id'])->first();
                //如果砍价是商品
                if ($detail['type'] ==1) {
                    throw new \Exception("砍价商品不能兑换");
                    // return $this->resFailed(700, '砍价商品不能兑换!');
                }
            } else {
                $rewardInfo = ActivitiesRewards::find($user_reward['activities_reward_id']);

                if (empty($rewardInfo) || $rewardInfo['is_use'] == 0 || $rewardInfo['goods_stock'] == 0) {
                    throw new \Exception("奖品失效");
                    // return $this->resFailed(700, '奖品失效!');
                }
                $rewardGoods = ActivitiesRewardGoods::find($rewardInfo['activities_reward_goods_id']);
            }


           // $activitiesTransmit=ActivitiesTransmit::find($request['activities_reward_id']);
            $activity_sign = $user_reward['type'];
            $activity_sign_id = $user_reward['activities_id'];


            $service = new TradeService();

            $tid = $service::createId('tid');
            $lottery_info = Lottery::where('id' , $activity_sign_id)->first();
            // 订单主表数据
            $tradeData = [
                'tid'                   => $tid,
                'shop_id'               => 0,
                'user_id'               => $user_id,
                'amount'                => 0,
                'total_fee'             => 0,
                'points_fee'            => 0,
                'post_fee'              => 0,
                'status'                => 'WAIT_SELLER_SEND_GOODS',
                'ip'                    => request()->getClientIp(),
                'buyer_message'         => '',
                'activity_sign'         => $activity_sign,
                'activity_sign_id'      => $activity_sign_id,
                'receiver_name'         => ($pick_type > 0)?$wx->nickname:$address['name'],
                'receiver_province'     => ($pick_type > 0)?'奖品自提':$address['province'],
                'receiver_city'         => ($pick_type > 0)?'奖品自提':$address['city'],
                'receiver_county'       => ($pick_type > 0)?'奖品自提':$address['county'],
                'receiver_address'      => ($pick_type > 0)?'奖品自提':$address['address'],
                'receiver_tel'          => ($pick_type > 0)?$this->user->mobile:$address['tel'],
                'pick_type'             =>  $pick_type,
                'receiver_housing_name' => ($pick_type > 0)?'奖品自提':$address['housing_name'],
                'receiver_housing_id'   => ($pick_type > 0)?0:$address['housing_id'],
                'gm_id'                 => $lottery_info->gm_id,
            ];
            if ($pick_type > 0) {
                $tradeData['receiver_zip'] = 'none';
            } else {
                $tradeData['receiver_zip'] = ($address['postal_code']) ? $address['postal_code'] : 'none';
            }

            Trade::create($tradeData);

            $tradeOrderData = [];
            $tradeOrderData['oid'] = $service::createId('oid');
            $tradeOrderData['tid'] = $tid;
            $tradeOrderData['status'] = 'WAIT_SELLER_SEND_GOODS';
            $tradeOrderData['shop_id'] = $rewardGoods['shop_id'];
            $tradeOrderData['user_id'] = $user_id;
            $tradeOrderData['goods_id'] = $rewardGoods['goods_id'];
            $tradeOrderData['sku_id'] = $rewardGoods['sku_id'];
            $tradeOrderData['goods_price'] = $rewardGoods['goods_price'];
            $tradeOrderData['quantity'] = $user_reward['quantity'];
            $tradeOrderData['goods_name'] = $rewardGoods['goods_name'];
            $tradeOrderData['goods_image'] = $rewardGoods['goods_image'];
            $tradeOrderData['gc_id'] = $rewardGoods['gc_id'];
            $tradeOrderData['activity_sign'] = $activity_sign_id;
            $tradeOrderData['activity_type'] = $activity_sign;
            TradeOrder::create($tradeOrderData);


            //扣减库存
            $rewardInfo->decrement('goods_stock', $user_reward['quantity']);

            //更新会员领奖表
            $user_reward->update(['tid' => $tid, 'is_redeem' => 1]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(700, $e->getMessage());
        }

        return $this->resSuccess([], '领取成功!');
    }


}
