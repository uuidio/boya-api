<?php
/**
 * @Filename        SecKillService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use ShopEM\Models\GoodsSku;
use ShopEM\Models\SecKillApplie;
use ShopEM\Models\SecKillAppliesRegister;
use ShopEM\Models\SecKillGood;
use ShopEM\Models\SecKillOrder;
use ShopEM\Models\SecKillStockLog;
use ShopEM\Models\TradeOrder;
use ShopEM\Jobs\HandleSecKill;


class SecKillService
{


    /**
     * 检测商品是否在进行的秒杀活动
     * @Author hfh_wind
     */
    public function SecKillGoods($sku_id, $seckill_ap_id)
    {

        $nowTime = date('Y-m-d H:i:s', time());
        $now_activiy = SecKillGood::where('seckill_ap_id',
            $seckill_ap_id)->where('sku_id',$sku_id)->first();


        $goods_queue_key = "seckill_" . $sku_id . "_good_" . $seckill_ap_id;//当前商品的库存队列

        $goods_stock = Redis::get($goods_queue_key);//当前商品的库存队列

        if ($now_activiy['start_time'] <= $nowTime && $now_activiy['end_time'] >= $nowTime && $now_activiy['verify_status'] == 2) {
            $detail['is_sec_kill'] = '1';
        }

        $detail['goods_stock'] = $goods_stock; //秒杀库存
        $detail['sec_kill_info'] = $now_activiy;

        return $detail;
    }


    /**
     *  秒杀商品加入队列
     * @Author hfh_wind
     * @param $seckillData
     * @param $seckills_stock
     * @return array
     */
    public function RedisWatch($seckillData, $seckills_stock)
    {
        $user_id = $seckillData['user_id'];
        $sku_id = $seckillData['sku_id'];
        $seckill_ap_id = $seckillData['seckill_ap_id'];

        $user_queue_key = "seckill_" . $sku_id . "_user_" . $seckill_ap_id;//用户购买商品情况

        $redis=new Redis();

        //如果不在1分钟点击时间内无需加入队列
        $record_time_click=$user_id.'click'. $sku_id;
        $check_click=$redis::get($record_time_click);

        //有记录就需要等待60秒
        if(!$check_click){
            $redis::setex($record_time_click,60,date('Y-m-d H:i:s',time()));
        }else{
            return  true ;
        }

        $redis::watch($user_queue_key); //监听库存

        $len = $redis::hlen($user_queue_key);

        $rob_total = $seckills_stock; //抢购数量
        if ($len < $rob_total) {
            $redis::multi();
            //记录时间
            $redis::hSet($user_queue_key, $user_id, date('Y-m-d H:i:s',time()));
            $rob_result = $redis::exec();

            //当前成功的请求
            if ($rob_result[0] == 1) {

                $this->CreateSeckillOrder($seckillData);

                //剩余
//                $rob_now = $rob_total - $len - 1;
            } else {

                $return = ["status" => '-1', 'msg' => '超过秒杀数量,请重试！'];
                return $return;
            }
        } else {
            $return = ["status" => '-1', 'msg' => '卖光了,请重试！'];
            return $return;
        }
    }


    /**
     * redis 事物
     * @Author hfh_wind
     */
    public function RedisWatchTest()
    {
        Redis::watch("mywatchlist");
        $len = Redis::hlen("mywatchlist");
        $rob_total = 100; //抢购数量
        if ($len < $rob_total) {
            Redis::multi();
            Redis::hSet("mywatchlist", "user_id_" . mt_rand(1, 999999), time());
            $rob_result = Redis::exec();
            testLog($len);
            if ($rob_result) {
                $mywatchlist = Redis::hGetAll("mywatchlist");
                echo '抢购成功' . PHP_EOL;
                echo '剩余数量：' . ($rob_total - $len - 1) . PHP_EOL;
                echo '用户列表：' . PHP_EOL;
                print_r($mywatchlist);
                exit;
            } else {
                exit('手气不好，再抢购！');
            }
        } else {
            exit('已卖光');
        }
    }


    /**
     *  抢购商品前处理当前会员是否进入队列
     * @Author hfh_wind
     */
    public function goods_number_queue($params)
    {
        $user_id = $params['user_id'];
        $sku_id = $params['sku_id'];
        //清掉指定的缓存,测试用
//        $this->clearRedis($sku_id);
        //处理用户点击立即购买,符合条件的数据进入购买的排队队列（如果当前用户没在当前产品用户的队列就进入排队并且减少一个库存队列，如果在就抛出不处理)
        if (!$user_id) {
            return true;
        }

        //按照sku 存盘
        $user_queue_key = "goods_" . $sku_id . "_user_" . $params['activity_id'];//当前商品队列的用户情况
        $goods_number_key = "goods_" . $sku_id . '-' . $params['activity_id'];//当前商品的库存队列

        /* 进入队列 */
        $goods_number_queue = Redis::get($goods_number_key);//当前商品的库存队列

        if ($goods_number_queue == 0) {
            $return = ["status" => '-1', 'msg' => '已经超过秒杀数量,请重试！'];
            return $return;
        }


        if ($goods_number_queue >= $params['quantity'] && ($goods_number_queue - $params['quantity']) >= 0) {
            //如果会员没有购买过就进入
            $user_redis = Redis::hGet($user_queue_key, $user_id);
//            dd($goods_number_queue);
            //个人限制购买数量
            $stock_limit_key = "goods_" . $sku_id . "_limit_num_" . $params['activity_id'];
            $stock_limit = Redis::get($stock_limit_key);

            if (!$user_redis) {

                if ($params['quantity'] > $stock_limit) {
                    $return = ["status" => '-1', 'msg' => '超过秒杀数量,请重试！'];
                    return $return;
                }
            } else {

                $user = json_decode($user_redis, true);
                //可购买数=限制数量-已购买的数量
                $buy = $stock_limit - $user['num'];

                if ($params['quantity'] > $buy) {
                    $return = ["status" => '-1', 'msg' => '超过秒杀数量,请重试！'];
                    return $return;
                }
            }

        } else {
            //队列里sku已经没有库存或者库存不足,意思是抢购完了.
            $return = ["status" => '-1', 'msg' => '已经抢购超过限量！'];
            return $return;
        }
    }


    /**
     *  生成秒杀订单
     *
     * @Author hfh_wind
     * @param $seckillData  user_id,goods_id,sku_id,seckill_ap_id
     */
    public function CreateSeckillOrder($seckillData)
    {
        $redis=new Redis();

        $resDate = SecKillOrder::create($seckillData);

        $handleSecKill = "HandleSecKill_" . $resDate->id;

        $redis::set($handleSecKill, json_encode($resDate));
        //处理回库存
        HandleSecKill::dispatch($resDate->id);

        //拿到当前秒杀订单缓存,付款后更新秒杀订单信息
        $seckill_order_key="seckill_order_".$seckillData['user_id'];

        $redis::set($seckill_order_key, $resDate->id);

        return $resDate;
    }

    /**
     * 指定时间内还没生成订单的秒杀商品恢复秒杀库存
     *
     * @Author hfh_wind
     */
    public function HandleSecKill($kill_orders_id)
    {
        $handleSecKill = "HandleSecKill_" . $kill_orders_id;
        $redisInfo = Redis::get($handleSecKill);
        $redisInfo = json_decode($redisInfo, true);

        //抢到资格然后没有,没生成订单
        $count = SecKillOrder::where(['id' => $redisInfo['id'], 'state' => 0])->count();
        //如果是尚未生成订单那么清除该用户的秒杀资格(即进到结算页面又退回去);
        if ($count) {
            $map['user_id'] = $redisInfo['user_id'];
            $map['sku_id'] = $redisInfo['sku_id'];
            $map['quantity'] = $redisInfo['quantity'];
            $map['seckill_ap_id'] = $redisInfo['seckill_ap_id'];
            //抢到资格然后没有,没生成订单标识1
            $this->clearSecKillRedis($map,1);
            //改为失效
            SecKillOrder::where(['id' => $redisInfo['id']])->update(['state' => '-1']);
        }
        return true;
    }


    /**
     *  处理活动期间,售后的订单
     *
     * @Author hfh_wind
     */
    public function resetSecKillOrder($param)
    {
        $tid = $param['tid'];
        $tradeOrder = TradeOrder::where('activity_type', 'seckill')->select('sku_id', 'quantity',
            'activity_sign')->where('tid', $tid)->first();
        $nowTime = date('Y-m-d H:i:s', time());
        if (!empty($tradeOrder)) {
//            foreach ($tradeOrder as $key => $value) {
            $now_activiy = SecKillApplie::where('start_time', '<=', $nowTime)->where('end_time', '>=',
                $nowTime)->where(['id' => $tradeOrder['activity_sign']])->first();
            if ($now_activiy) {
                $map['user_id'] = $param['user_id'];
                $map['sku_id'] = $tradeOrder['sku_id'];
                $map['quantity'] = $tradeOrder['quantity'];
                $map['seckill_ap_id'] = $tradeOrder['activity_sign'];
                $this->clearSecKillRedis($map);
            }
//            }
            //更改为失效
            SecKillOrder::where(['tid' => $tid])->update(['state' => '-1']);
            return true;
        } else {
            return true;
        }
    }


    /**
     *  秒杀恢复商品库存,清除redis 数据
     *
     * @Author hfh_wind   sku_id,user_id
     */
    public function clearSecKillRedis($param,$status='')
    {
        $sku_id = $param['sku_id'];
        $user_id = $param['user_id'];
        $seckill_ap_id = $param['seckill_ap_id'];

        $redis= new Redis();

//        $cart_key = md5($user_id . 'cart_fastbuy');
//        $redis::del($cart_key); //清除购物车

        $goods_buy_key = "seckill_" . $sku_id . '_good_record_' . $seckill_ap_id;//当前已售商品库存

        //$goods_number_key = "seckill_" . $sku_id . '_good_' . $seckill_ap_id;//活动商品库存

        $order = "seckill_" . $sku_id . "_buy_record_" . $seckill_ap_id."_u_id_".$user_id;//订单标识,一个会员只能下一单

        $goods_buy_record = $redis::get($goods_buy_key);//当前已售商品库存

        //$goods_number = $redis::get($goods_number_key);//活动商品库存

        //如果会员尚未扣减库存,先去掉会员信息
        $redis::hDel("seckill_" . $sku_id . "_user_" . $seckill_ap_id, $user_id);//清除用户信息

        $redis::del($order);

        if ($goods_buy_record == 0) {
            return true;
        }

        //减少已购买库存记录
        if(!$status){
            $redis::decr($goods_buy_key);
        }
//        $redis->hincrby("{$goods_number_key}", $param['quantity']);
    }


    /**
     * 删除指定活动缓存
     * @Author hfh_wind
     */
    public function cleaningGoodsRedis()
    {
        $nowTime = date('Y-m-d H:i:s', (time() - 1800)); // 半小时
        $out_activiy = SecKillApplie::where('end_time', '<',
            $nowTime)->where('enabled', '<>', '0')->where('check_sign', '=', 1)->get();

        if (empty($out_activiy)) {
            return true;
        }

        $out_activiy = $out_activiy->toArray();

        $redis=new Redis();
        foreach ($out_activiy as $key => $redis_v) {

            $sec_kill_goods = SecKillGood::where(['seckill_ap_id' => $redis_v['id']])->get();
            if (count($sec_kill_goods) > 0) {
                $sec_kill_goods = $sec_kill_goods->toArray();
                foreach ($sec_kill_goods as $key => $value) {
                    $seckill_ap_id = $value['seckill_ap_id'];
                    $sku_id = $value['sku_id'];

                    $goods_buy_key = "seckill_" . $sku_id . '_good_record_' . $seckill_ap_id;//当前已售商品库存
                    $goods_number_key = "seckill_" . $sku_id . '_good_' . $seckill_ap_id;//活动商品库存

                    $goods_buy_record = $redis::get($goods_buy_key);//当前已售商品库存
                    $goods_buy_record = $goods_buy_record ?: 0;

                    $goods_number = $redis::get($goods_number_key);//活动商品库存

                    $goods_stock = $goods_number - $goods_buy_record;
                    if ($goods_stock > 0) {
                        //回退剩余库存
                        $this->secKillStock($seckill_ap_id, $value, $goods_stock, 'inc', '秒杀活动结束，回退剩余库存');
                    }

      /*              $redis::del("seckill_" . $value['sku_id'] . "_user_" . $seckill_ap_id);//商品队列用户数删除
                    $redis::del("seckill_" . $value['sku_id'] . "_good_" . $seckill_ap_id);//商品队列库存删除
                    $redis::del("seckill_" . $value['sku_id'] . "_good_record_" . $seckill_ap_id);//当前已售商品库存删除
                    //个人限制购买数量
                    $redis::del("seckill_" . $value['sku_id'] . "_limit_num_" . $seckill_ap_id);
*/
                }
            }
            SecKillApplie::where('id', '=', $redis_v['id'])->where('check_sign', 1)->update(['check_sign' => 2]);
        }
        $return = ['status' => true, 'msg' => 'Redis队列已释放'];
        return $return;
    }



    /**
     *  判断当前商品是否正在参加活动
     *
     * @Author hfh_wind
     * @throws \Exception
     */
    public function actingSecKill($goods_id)
    {

        $nowTime = date('Y-m-d H:i:s', time());
        $now_activiy = SecKillGood::where('end_time', '>=',
            $nowTime)->where('goods_id', '=', $goods_id)->whereNotIn('verify_status', [1,3])->get();
        $res = 0;
        if (count($now_activiy) > 0) {
            $res = 1;
        }
        return $res;
    }

    /**
     * 取消释放商家申请秒杀活动,但尚未通过的商品
     * @Author hfh_wind
     */
    public function cleaningApplyGoods()
    {
        $nowTime = date('Y-m-d H:i:s', time());
        //如果活动已经开始尚未审核的商家申请作废.
        $activiy = SecKillApplie::where('apply_end_time', '<=', $nowTime)->where('check_sign', '=', 0)->get();

        foreach ($activiy as $key => $value) {
            $apply_id = $value['id'];
            //尚未审核的申请
            SecKillAppliesRegister::where([
                'seckill_ap_id' => $apply_id,
                'verify_status' => 0
            ])->update(['valid_status' => '0', 'refuse_reason' => '系统关闭']);
            //将商品释放
            SecKillGood::where(['seckill_ap_id' => $apply_id, 'verify_status' => 0])->update([
                'activity_tag'  => '作废',
                'verify_status' => 3,//下架
                'end_time'      => null
            ]);
            SecKillApplie::where('id', '=', $apply_id)->update(['check_sign' => 1]);
        }
    }

    public function secKillStock($seckill_ap_id, $sku, $num, $type, $note)
    {
        $change = 0;
        if ($type == 'dec') {
            GoodsSku::where('id', $sku['sku_id'])->decrement('goods_stock', $num);
            $change = 0 - $num;
        } elseif ($type == 'inc') {
            GoodsSku::where('id', $sku['sku_id'])->increment('goods_stock', $num);
            $change = $num;
        }
        SecKillStockLog::create([
            'sku_id' => $sku['sku_id'],
            'seckill_ap_id' => $seckill_ap_id,
            'goods_id' => $sku['goods_id'],
            'shop_id' => $sku['shop_id'],
            'goods_stock' => $num,
            'type' => $type,
            'note' => $note,
        ]);

        //记录库存日志
        $sku_info = DB::table('goods_skus')->where(['id' => $sku['sku_id'], 'goods_id' => $sku['goods_id']])->select('goods_stock')->first();
        $goods_stock = $sku_info->goods_stock ?? 0;
        $arrParams['goods_stock'] = $goods_stock;
        $arrParams['change'] = $change;
        $arrParams['type'] = 'seckill';
        $arrParams['note'] = $note;
        $arrParams['shop_id'] = $sku['shop_id'];
        $arrParams['sku_id'] = $sku['sku_id'];
        $arrParams['goods_id'] = $sku['goods_id'];
        (new GoodsService())->GoodsStockLogs($arrParams);
    }


    /**
     * 计数器(主要用于订单操作)
     * 有限时间窗口内的数量是否超过限制即可
     * @Author hfh_wind
     * @param $userId
     * @param $action 动作
     * @param $period 时间  n*1000  n 代表秒
     * @param $maxCount 允许次数
     * @return bool
     */
    public function isActionAllowed($userId, $action, $period, $maxCount)
    {
        $redis =new Redis();
        $key = sprintf('hist:%s:%s', $userId, $action);
        $now = $this->msectime();   # 毫秒时间戳
        $redis::pipeline();//使用管道提升性能
        $redis::multi();
        $redis::zadd($key, $now, $now); //value 和 score 都使用毫秒时间戳
        $redis::zremrangebyscore($key, 0, $now - $period); //移除时间窗口之前的行为记录，剩下的都是时间窗口内的
        $redis::zcard($key);  //获取窗口内的行为数量
        $redis::expire($key, $period + 1);  //多加一秒过期时间
        $replies = $redis::exec();

        return $replies[2] <= $maxCount;
    }

    /**
     * 返回当前的毫秒时间戳
     * @Author hfh_wind
     * @return float
     */
    public function msectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }

    //更新秒杀活动缓存
    public function updateSeckillApplieCache($gm_id) {
        Cache::forget('cache_seckill_applie_gmid_'.$gm_id);
        cacheRemember('cache_seckill_applie_gmid_'.$gm_id, now()->addMinutes(10), function () use ($gm_id){
            $nowTime = date('Y-m-d H:i:s', time());
            $result = SecKillApplie::where('end_time', '>=', $nowTime)->where('gm_id', '=', $gm_id)->orderBy('start_time', 'asc')->orderBy('id',
                'desc')->select('id','activity_name', 'apply_end_time', 'start_time', 'end_time')->get();
            if (count($result) > 0) {
                $result = $result->toArray();
            } else {
                $result = [];
            }
            return $result;
        });
    }

    //更新秒杀活动商品列表的缓存
    public function updateSeckillApIdCache($id) {
        Cache::forget('cache_seckill_ap_id' . $id);
        cacheRemember('cache_seckill_ap_id' . $id, now()->addMinutes(10), function () use ($id){
            $result = SecKillGood::where('seckill_ap_id', '=', $id)->where('verify_status', '=',
                '2')->orderBy('sort', 'desc')->get();
            if (count($result) > 0) {
                $result = $result->toArray();
            } else {
                $result = [];
            }
            return $result;
        });
    }



}