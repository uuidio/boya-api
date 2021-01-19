<?php
/**
 * @Filename        GroupService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use ShopEM\Models\Group;
use ShopEM\Models\GroupsUserJoin;
use ShopEM\Models\GroupsUserOrder;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradeRefunds;
use ShopEM\Models\WxUserinfo;


class GroupService
{
    //act_id 是支付单和活动id拼接成
    /**
     *  判断是否已开过团
     *
     * @Author hfh_wind
     * @param $user_id
     * @param $act_id
     * @return bool
     */
    public function checkGroup($act_id)
    {
        /* redis 队列 */
//        $redis = Redis::connection('default');
        $all_group_member = Redis::hGetAll($act_id);
        if (!empty($all_group_member)) {    //判断是否已开过团
//            $group_member = Redis::get($act_id);
//            $people_arr = explode(',', $group_member);
            return key($all_group_member);            //返回之前保存的团长openid（第一个）
        } else {
            return false;            //返回false，之前未有过
        }
    }

    /**
     *  开团
     *
     * @Author hfh_wind
     * @param $user_id
     * @param $act_id
     * @param $mediaID
     * @return mixed
     */
    public function startGroup($user_id, $act_id, $hours)
    {        //保存成团信息
        /* redis 队列 */
//        Redis::setex($act_id, 3600 * $hours, $user_id);  //记录会员id
        //改进
        Redis::hSet($act_id, $user_id, "我是团长,会员id-" . $user_id);
        //设置过期时间,3600 * $hours 小时
        Redis::expire($act_id, 3600 * $hours);

        return $user_id;
    }


    /**
     *  加团
     *
     * @Author hfh_wind
     * @param $p_user_id 团长id
     * @param $act_id 活动编码
     * @param $user_id 入团的会员id
     * @param $group_size 组团人数
     * @param $hours 开团等待时间
     * @return int
     */
    public function joinTuan($p_user_id, $act_id, $user_id, $group_size, $hours)
    {   //保存参团人员信息
        /* redis 队列 */

        if ($p_user_id == $user_id) {
            //防止本人跟团
            return $return = ["status" => '4', 'msg' => '这是您的团,不能参与!'];
        }
        $group_member = Redis::hGetAll($act_id);

        if (empty($group_member)) {
            //防止本人跟团
            return $return = ["status" => '0', 'msg' => '所选的团不存在!'];
        }

//        dd($redis->setex($act_id, 3600 * $hours, '1'));
//        $people_arr = explode(',', $group_member);
        $peo_num = count($group_member);
        if ($peo_num < $group_size) {          //第一个为团长信息
            //判断是否已经在团里
            if (array_key_exists($user_id, $group_member)) {
                return $return = ["status" => '3', 'msg' => '已进过此团!'];
            }
            return $return = ["status" => '1', 'msg' => '进团成功!'];
        } else {
//            return 2;                 //此团已满
            return $return = ["status" => '2', 'msg' => '此团已满!'];
        }
    }


    /**
     * 跟团查询入团资格
     * @Author hfh_wind
     * @param $user_id
     * @param $group_id
     * @return array
     */
    public function checkGroupValidity($user_id, $group_id)
    {
        //五分钟有效期
        $set_group_bn_key = "group_" . $group_id . '_' . $user_id;
        $effective = Redis::get($set_group_bn_key);
        if ($effective) {
            $group_member = Redis::hGet($effective, $user_id);
            if (empty($group_member)) {
                Redis::del($set_group_bn_key);
                return $return = ["status" => '0', 'msg' => '团信息已经失效,请重新加入!'];
            }
        }
    }


    /**
     *  参团时间
     *
     * @Author hfh_wind
     * @param $user_id
     * @param $act_id
     * @param $time
     */
    public function addTime($user_id, $act_id, $time)
    {
        /* redis 队列 */
        $now_time = date('Y-m-d H:i:s');
        $join_time = Redis::get($act_id . $user_id . 'time');
        if ($join_time) {
            $join_time .= ',' . $now_time;
            Redis::setex($act_id . $user_id . 'time', $time, $join_time);
        } else {
            Redis::setex($act_id . $user_id . 'time', $time, $now_time);
        }

    }

    /**
     *  团长开团记录信息(必须要支付成功才能成团)
     *
     * @Author hfh_wind
     * @param $user_id
     * @param $payment_id
     */
    public function GroupOrder($user_id, $payment_id)
    {
        $key_group = $user_id . 'group' . $payment_id;
        $group_info = Redis::get($key_group);

        //团长
        if ($group_info) {
            $group_info = json_decode($group_info, true);
            $act_id = $group_info['act_bn'];
            $hours = $group_info['group_info']['group_validhours'];
            //检查是否入团,如果入团可拿到团长id
            $res = $this->checkGroup($act_id);

            //没有说明,可以开团
            if (!$res) {
                try {
                    //如果没有开过团,则作为团长开团
                    $this->startGroup($user_id, $act_id, $hours);

                    $groupSave['user_id'] = $user_id;
                    $groupSave['groups_bn'] = $act_id;
                    $groupSave['groups_id'] = $group_info['group_info']['id'];
                    $groupSave['goods_name'] = $group_info['group_info']['goods_name'];
                    $groupSave['goods_id'] = $group_info['group_info']['goods_id'];
                    $groupSave['sku_id'] = $group_info['group_info']['sku_id'];
                    $groupSave['group_number'] = $group_info['group_info']['group_size'];
                    $groupSave['tid'] = $group_info['tid'];
                    $groupSave['start_time'] = date('Y-m-d H:i:s', time());
                    $groupSave['end_time'] = date('Y-m-d H:i:s', strtotime("+" . $hours . " hour"));

                    GroupsUserOrder::create($groupSave);

                    $groupJoinSave['user_id'] = $user_id;
                    $wx = WxUserinfo::where('user_id', '=', $user_id)->first();
                    if (isset($wx['headimgurl'])) {
                        $groupJoinSave['wechat_head_img'] = $wx['headimgurl'];
                        $groupJoinSave['open_id'] = !empty($wx['open_id']) ? $wx['open_id'] : 0;
                    }
                    $groupJoinSave['goods_name'] = $group_info['group_info']['goods_name'];
                    $groupJoinSave['goods_price'] = $group_info['group_info']['price'];
                    $groupJoinSave['group_price'] = $group_info['group_info']['group_price'];
                    $groupJoinSave['groups_id'] = $group_info['group_info']['id'];
                    $groupJoinSave['groups_bn'] = $act_id;
                    $groupJoinSave['goods_id'] = $group_info['group_info']['goods_id'];
                    $groupJoinSave['sku_id'] = $group_info['group_info']['sku_id'];
                    $groupJoinSave['payment_id'] = $payment_id;
                    $groupJoinSave['tid'] = $group_info['tid'];
                    $groupJoinSave['is_header'] = '1';//团长
                    $groupJoinSave['status'] = '1';//团长

                    GroupsUserJoin::create($groupJoinSave);

                } catch (\Exception $e) {
                    testLog($e->getMessage());
                    throw new \Exception('记录团购失败!');
                }
            }

        }
        //处理
        $update_join = Redis::get($payment_id);
//dd($update_join);
        //团员入团
        if ($update_join) {
            try {
                $key_groupjoin = $user_id . 'groupjoin' . $payment_id;
                $group_join_info = Redis::get($key_groupjoin);
                $group_join_info = json_decode($group_join_info, true);

                $group_size = $group_join_info['group_info']['group_size'];
                $hours = $group_join_info['group_info']['group_validhours'];
                $act_id = $group_join_info['groups_bn'];
                $main_user_id = $group_join_info['main_user_id'];

                //改为支付后入团
                Redis::hSet($act_id, $user_id, "会员id-" . $user_id);
                $this->addTime($user_id, $act_id, $hours);    //保存对应的入团时间 

                //记录跟团信息
//                $this->joinTuan($main_user_id, $act_id, $user_id, $group_size, $hours);

//                $group_member = Redis::get($act_id);
//                $people_arr = explode(',', $group_member);
//                $peo_num = count($people_arr);
                //查询团的人数
                $peo_num = Redis::hlen($act_id);

                //如果满团了,更新表状态
                if ($peo_num == $group_size) {
                    GroupsUserOrder::where('groups_bn', '=', $act_id)->update(['status' => '2']);
                    //如果拼团完成删除redis里的值
                    Redis::del($act_id);
                }
                //判断团是否还有效
                $group_order = GroupsUserOrder::where('groups_bn', '=', $act_id)->whereIn('status', [1,2])->first();
                if ($group_order) {
                    GroupsUserJoin::where('payment_id', '=', $payment_id)->update(['status' => '1']);
                } else {
                    //如果团已失效，直接进行取消订单操作
                    $join = GroupsUserJoin::where('payment_id', '=', $payment_id)->first();
                    if ($join) {
                        $tradeService = new TradeService();
                        $this->clearGroupInfo($join->tid);
                        $tradeService->setCancelId($user_id)
                            ->tradeCancelCreate($join->tid, '团购失败系统取消', '');
                    }
                }
                Redis::del($payment_id);
            } catch (\Exception $e) {
                testLog($e->getMessage());
                throw new \Exception('记录团购失败!');
            }
        }
    }

    /**
     *  团员加团未支付也生成记录
     *
     * @Author hfh_wind
     * @param $user_id
     * @param $group_info
     */
    public function createGroupJoin($user_id, $group_info, $act_id)
    {
        $hours = $group_info['group_info']['group_validhours'];

//        if ($res == '1') {
        $groupSave['user_id'] = $user_id;
        $wx = WxUserinfo::where('user_id', '=', $user_id)->first();
        if (isset($wx['headimgurl'])) {
            $groupSave['wechat_head_img'] = $wx['headimgurl'];
            $groupSave['open_id'] = !empty($wx['open_id']) ? $wx['open_id'] : 0;
        }
        $groupSave['goods_name'] = $group_info['group_info']['goods_name'];
        $groupSave['goods_price'] = $group_info['group_info']['price'];
        $groupSave['group_price'] = $group_info['group_info']['group_price'];
        $groupSave['groups_id'] = $group_info['activity_id'];
        $groupSave['groups_bn'] = $act_id;
        $groupSave['goods_id'] = $group_info['group_info']['goods_id'];
        $groupSave['sku_id'] = $group_info['group_info']['sku_id'];
        $groupSave['payment_id'] = $group_info['payment_id'];
        $groupSave['tid'] = $group_info['tid'];

        GroupsUserJoin::create($groupSave);
        //回调的时候,如果是团员那么入团
        Redis::setex($group_info['payment_id'], 3600 * $hours, $user_id); //记录标识

    }


    /**
     * 团圆团购信息和和恢复商品库存
     * @Author hfh_wind
     */
    public function clearGroupInfo($tid, $status = 3)
    {   //团购进行中的订单
        $trade = GroupsUserJoin::where(['tid' => $tid])->first();
        $redis = new Redis();

        if (!empty($trade)) {

            $stock_key = $trade['sku_id'] . '_group_sale_stock_' . $trade['groups_id'];
            //如果是团长取消(即付款后)商家审核的时候执行
            if ($trade['is_header'] == 1) {

                $refund_info = TradeRefunds::where('tid', '=', $tid)->select('refund_bn')->first();

                //申请退款
                GroupsUserOrder::where('tid', '=', $tid)->update(['status' => $status]);

                //成功申请退款
                GroupsUserJoin::where(['tid' => $tid])->update([
                    'status'    => '2',
                    'refund_bn' => $refund_info['refund_bn']
                ]);
                //已卖减少1
                $redis::decr($stock_key);//减少1
                //删除缓存
                $redis::del($trade['groups_bn']);

            } elseif ($trade['status'] == 0) { //跟团团圆未付款
                //已卖减少1
                $check_group_sale_stock = Redis::get($stock_key);
                if ($check_group_sale_stock) {
                    Redis::decr($stock_key);//减少1
                }
            } elseif ($trade['status'] == 1) {  //跟团团圆已付款

                //成功申请退款
                $groups_bn = $trade['groups_bn'];
                //删除跟团位置
                Redis::hDel($groups_bn, $trade['user_id']);

                $refund_info = TradeRefunds::where('tid', '=', $tid)->select('refund_bn')->first();

                //成功申请退款
                GroupsUserJoin::where(['tid' => $tid])->update([
                    'status'    => '2',
                    'refund_bn' => $refund_info['refund_bn']
                ]);

                $check_group_sale_stock = Redis::get($stock_key);
                if ($check_group_sale_stock) {
                    Redis::decr($stock_key);//增加1
                }
            }
        } else {
            $tradeOrderinfo = TradeOrder::where(['tid' => $tid, 'status' => 'WAIT_BUYER_PAY'])->first();
            if ($tradeOrderinfo['activity_type'] == "is_group") {

                $order_stock_key = $tradeOrderinfo['sku_id'] . '_group_sale_stock_' . $tradeOrderinfo['activity_sign'];
                //已卖减少1
                $order_group_sale_stock = Redis::get($order_stock_key);

                if ($order_group_sale_stock > 0) {
                    Redis::decr($order_stock_key);//减少1
                }
            }
        }

    }


    /**
     *  计划任务取消团购未达成的订单
     *
     * @Author hfh_wind
     * @throws \Exception
     */
    public function clearGroupInfoTask()
    {
        $now_time = date('Y-m-d H:i:s');
        //进行中的订单
        $order = GroupsUserOrder::where('end_time', '<', $now_time)->whereIn('status', ['1', '3'])->get();
        //处理团购活动,如果删除缓存

        if (count($order) > 0) {
            $order = $order->toArray();
            foreach ($order as $key => $value) {
                //获取该团的所有订单
                $groupUser = GroupsUserJoin::where(['groups_bn'=>$value['groups_bn']])->select('tid')->get();
                foreach ($groupUser as $trade) {
                    $this->clearGroupInfo($trade['tid']);
                    $count = DB::table('trade_cancels')->where(['tid' => $trade['tid']])->count();
                    if (!$count) {
                        $tradeDataInfo = Trade::where('tid', '=', $trade['tid'])->first();
                        if(empty($tradeDataInfo)){
                            continue;
                        }
                        $cancelReason = '团购失败系统取消';
                        $tradeService = new TradeService();
                        //未付款 可直接取消 货到付款并且不是消费者申请取消订单则可以直接取消
                        if ($tradeDataInfo->status == 'WAIT_BUYER_PAY' || ($tradeDataInfo->pay_type == 'offline' && $tradeService->getCancelFromType() != 'buyer')) {
                            $tradeService->__noPayTradeCancel($tradeDataInfo, $cancelReason);
                        } else//已付款或者为货到付款的订单需要申请退款
                        {
                            $tradeService->__payTradeCancel($tradeDataInfo, $cancelReason,  '', '');
                        }
                    }
                }
            }
        }
    }


    /**
     *  计划任务取消团购未达成的订单
     *
     * @Author hfh_wind
     * @throws \Exception
     */
    public function clearGroupGoods()
    {
        $now_time = date('Y-m-d H:i:s');
        //过期的商品或者手动关闭的is_show=2
        $group = Group::where('end_time', '<', $now_time)->orWhere('is_show', '=', '2')->get();
        //处理团购活动,如果删除缓存
        if (count($group) > 0) {
            $order = $group->toArray();
            foreach ($order as $key => $value) {
                $stock_key = $value['sku_id'] . '_group_sale_stock_' . $value['id'];
                //删除缓存
                Redis::del($stock_key);
            }
        }
    }

    /**
     *  清除占位置但不付款的
     *
     * @Author hfh_wind
     * @throws \Exception
     */
    public function clearGroupUser($param)
    {
        $user_id=$param['user_id'];
        $groups_bn=$param['groups_bn'];
        //删除缓存
        Redis::hDel($groups_bn,$user_id);
    }


    /**
     *  判断当前商品是否正在参加活动
     *
     * @Author hfh_wind
     * @throws \Exception
     */
    public function actingGroup($goods_id)
    {
        $now_time = date('Y-m-d H:i:s');

        $order = Group::where('end_time', '>=', $now_time)->where('goods_id', '=', $goods_id)->get();
        $res = 0;
        if (count($order) > 0) {
            $res = 1;
        }
        return $res;
    }

}


