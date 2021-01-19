<?php
/**
 * @Filename        SecKillController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\SecKillStoreRequest;
use ShopEM\Http\Requests\Shop\SecKillWaitingRequest;
use ShopEM\Models\Goods;
use ShopEM\Models\SecKillApplie;
use ShopEM\Models\SecKillGood;
use ShopEM\Models\GoodsSku;
use Illuminate\Support\Facades\Redis;
use ShopEM\Repositories\GoodsRepository;
use ShopEM\Services\GoodsService;


class SecKillController extends BaseController
{


    public function test(SecKillWaitingRequest $request){
        $data = $request->only('sku_id', 'activity_id');
        $user_id = $this->user->id;
        $record_key = "seckill_".$data['sku_id']."_good_record_".$data['activity_id'];
        Redis::decr($record_key);
        $user_queue_key = "seckill_" . $data['sku_id'] . "_user_" . $data['activity_id'];//当前商品队列的用户情况
        //记录时间
        Redis::hSet($user_queue_key, $user_id, date('Y-m-d H:i:s',time()));
        $this->SecKillCart($data, $user_id);
    }

    /**
     *  展示期数,尚未开始和正在进行的
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetPeriods(Request $request)
    {
        // $nowTime = date('Y-m-d H:i:s', time());
        /*$applyInfo = SecKillApplie::where('end_time', '>=', $nowTime)->where('apply_end_time', '<',
            $nowTime)->orderBy('start_time', 'asc')->orderBy('id', 'desc')->select('id',
            'activity_name', 'apply_end_time', 'start_time', 'end_time')->get();*/
        // $model = new SecKillApplie;
        // $model = $model->where('end_time', '>=', $nowTime);
        // $model = $model->where('gm_id', '=', $this->GMID);
        // $applyInfo = $model->orderBy('start_time', 'asc')->orderBy('id', 'desc')->select('id',
        //     'activity_name', 'apply_end_time', 'start_time', 'end_time')->get();
        // $applyInfoArr = [];
        // if (count($applyInfo) > 0) {
        //     $applyInfoArr = $applyInfo->toArray();
        // }
        // $return['sec_kill_list'] = $applyInfoArr;

        $gm_id = $this->GMID;
        $return['sec_kill_list'] = cacheRemember('cache_seckill_applie_gmid_'.$gm_id, now()->addMinutes(10), function () use ($gm_id){
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

        if (!empty($return['sec_kill_list'])) 
        {
            foreach ($return['sec_kill_list'] as &$value) 
            {
                //由于缓存原因会导致状态不能及时，处理
                if (strtotime($value['start_time']) <= time() && $value['validstatus_sign']['sign'] != '1') {
                    $value['validstatus'] = '抢购中';
                    $value['validstatus_sign'] = ['sign'=>'1','sign_text'=>'抢购中'];
                }
                unset($value);
            }
        }

        return $this->resSuccess($return);
    }


    /**
     * 秒杀展示商品列表
     *
     * @Author hfh_wind
     * @return int
     */
    public function Sec_kill_goods_list(Request $request)
    {

        $seckill_ap_id = $request->id;

        if (empty($seckill_ap_id)) {

            return $this->resFailed(414, '缺少参数!');
        }
        // $now_activiy_goods_list = SecKillGood::where('seckill_ap_id', '=', $seckill_ap_id)->where('verify_status', '=',
        //     '2')->orderBy('sort', 'desc')->get();
        // $now_activiy_goods_list_arr = [];
        // if (count($now_activiy_goods_list) > 0) {
        //     $now_activiy_goods_list_arr = $now_activiy_goods_list->toArray();
        // }
        
        $return['activiy_goods_list'] = cacheRemember('cache_seckill_ap_id' . $seckill_ap_id, now()->addMinutes(10), function () use ($seckill_ap_id){
            $result = SecKillGood::where('seckill_ap_id', '=', $seckill_ap_id)->where('verify_status', '=',
                '2')->orderBy('sort', 'desc')->get();
            if (count($result) > 0) {
                $result = $result->toArray();
            } else {
                $result = [];
            }
            return $result;
        });

        if (!empty($return['activiy_goods_list'])) 
        {
            foreach ($return['activiy_goods_list'] as &$goods) 
            {
                $spec_array = $this->getGoodsSpecListByGoodsId($goods['sku_id']);
                // $goods_marketprice = DB::table('goods_skus')->where('id', $goods['sku_id'])->value('goods_marketprice');
                // $goods_marketprice = $spec_array['goods_marketprice'];
                $goods['goods_price'] = $goods['goods_marketprice'] = $spec_array['goods_marketprice']??0;
                $goods['sold_out'] = $this->getSoldOut($goods);
                $goods['percent'] = $this->getPercent($goods);
            }
            unset($goods);
        }

        return $this->resSuccess($return);
    }


    /**
     * 处理秒杀商品,进入抢购
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function SecKillStore(SecKillStoreRequest $request)
    {
        $data = $request->only('sku_id', 'quantity', 'activity_id');

        $redis = new Redis();
        $data['user_id'] = $this->user->id;
        $data['quantity'] = 1;
        $secKillPrice = SecKillGood::where([
            'sku_id'        => $data['sku_id'],
            'seckill_ap_id' => $data['activity_id']
        ])->first();

        if (empty($secKillPrice)) {
            return $this->resFailed(700, "秒杀商品异常");
        }



        $seckill_ap_id = $data['activity_id'];
        $sku_id = $data['sku_id'];

        //是否下单此商品
        $order_key = "seckill_" . $sku_id . "_buy_record_" . $seckill_ap_id."_u_id_".$data['user_id'];//标识已购买
        $buy_sign= $redis::get($order_key);
        if ($buy_sign) {
            return $this->resFailed(700, "已抢购过此商品,请查看订单!");
        }


        $record_key = "seckill_" . $sku_id . "_good_record_" . $seckill_ap_id;//用来对比购买的库存

        $record_stock = $redis::get($record_key);
        if ($record_stock == $secKillPrice['seckills_stock']) {
            return $this->resFailed(700, "不好意思呢，已经被抢完了");
        }


        $user_queue_key = "goods_" . $sku_id . "_user_" . $seckill_ap_id;//当前商品队列的用户情况
        //如果会员没有购买过就进入
        $user_redis = $redis::hGet($user_queue_key, $data['user_id']);
        //如果队列有用户信息且无订单信息的情况就是此商品已经生成订单
        if ($user_redis) {
            //是否有记录秒杀的订单
//            $seckill_order_key = "seckill_order_" . $data['user_id'];
            $seckill_order_key  = "seckill_" . $data['sku_id'] . "_buy_record_" . $seckill_ap_id."_u_id_".$data['user_id'];
            $checkOrder = $redis::get($seckill_order_key);
            if (!$checkOrder) {
                return $this->resFailed(700, "已抢购过此商品!");
            }
        }


        //秒杀活动
        $seckill = new \ShopEM\Services\SecKillService();

        //获得购买资格,生成订单
        $seckillData['user_id'] = $data['user_id'];
        $seckillData['goods_id'] = $secKillPrice['goods_id'];
        $seckillData['sku_id'] = $data['sku_id'];
        $seckillData['quantity'] = $data['quantity'];
        $seckillData['seckill_ap_id'] = $data['activity_id'];
        $seckills_stock = $secKillPrice['seckills_stock'];

        $seckill->RedisWatch($seckillData, $seckills_stock);

        return $this->resSuccess([], "抢购中,请等待!");
    }

    /**
     * 秒杀等待页面
     * @Author hfh_wind
     * @return int
     */
    public function SecKillWaiting(SecKillWaitingRequest $request)
    {
        $data = $request->only('sku_id', 'activity_id');
        $sku_id = $data['sku_id'];
        $seckill_ap_id = $data['activity_id'];
        $user_id = $this->user->id;
        $user_queue_key = "seckill_" . $sku_id . "_user_" . $seckill_ap_id;//当前商品队列的用户情况

        $redis = new Redis();
        $user_redis = $redis::hGet($user_queue_key, $user_id);


        if (!empty($user_redis)) {
            //是否有记录秒杀的订单
            $seckill_order_key  = "seckill_" . $data['sku_id'] . "_buy_record_" . $seckill_ap_id."_u_id_".$user_id;

            $checkOrder = $redis::get($seckill_order_key);
            if (!$checkOrder) {
                $return['pass'] = 1;
                $this->SecKillCart($data, $user_id);
            } else {
                return $this->resFailed(700, "已抢购过此商品!");
            }
            return $this->resSuccess($return, "抢购成功!");
        } else {
            $return['pass'] = 0;
            return $this->resSuccess($return, "抱歉抢购失败!");
        }
    }

    /**
     * 秒杀商品加入购物车
     * @Author hfh_wind
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function SecKillCart($data, $user_id)
    {
        $data['quantity'] = 1;

        /*$goodsSaleAble = GoodsService::saleAble($data['sku_id'], $user_id, $data['quantity']);

        if ($goodsSaleAble['code'] === 0) {
            throw new \Exception($goodsSaleAble['message']);
        }

        $goods = $goodsSaleAble['data_sku'];*/

        //GoodsSku::find($data['sku_id']);
        $goods = $goods = $this->getGoodsSpecListByGoodsId($data['sku_id']);

        $data_goods = '';
        if ($goods) {
            //$data_goods = Goods::find($goods->goods_id);
            $goods_id = $goods->goods_id;
            $data_goods = cacheRemember('cache_detail_goods_id_' . $goods_id, now()->addMinutes(10), function () use ($goods_id){
                return Goods::find($goods_id);
            });
        }

        if (empty($data_goods)) {
            throw new \Exception('商品不存在');
        }

        $transport_id = $data_goods->transport_id;

        $data['user_id'] = $user_id;
        $data['goods_id'] = $goods->goods_id;
        $data['sku_id'] = $goods->id;
        $data['shop_id'] = $goods->shop_id;
        $data['goods_name'] = $goods->goods_name;
        $data['goods_info'] = $goods->goods_info;
        $data['goods_image'] = $goods->goods_image;
        $data['transport_id'] = $transport_id;
        $data['gm_id'] = $goods->gm_id;

        $secKillPrice = cacheRemember('cache_seckillgood_sku_id_' . $data['sku_id'] . '_activity_id_'.$data['activity_id'], now()->addMinutes(10), function () use ($data){
            return SecKillGood::where([
                'sku_id'        => $data['sku_id'],
                'seckill_ap_id' => $data['activity_id']
            ])->first();
        });
        // $secKillPrice = SecKillGood::where([
        //     'sku_id'        => $data['sku_id'],
        //     'seckill_ap_id' => $data['activity_id']
        // ])->first();

        //标识活动信息
        $data['params'] = 'seckill';
        $data['activity_id'] = $data['activity_id'];
        $goods->goods_price = $secKillPrice['seckill_price'];


        $data['goods_info'] = [];
        $data['goods_price'] = $goods->goods_price;
        $data['goods_info']['id'] = $goods->id;
        $data['goods_info']['goods_name'] = $goods->goods_name;
        $data['goods_info']['goods_info'] = $goods->goods_info;
        $data['goods_info']['shop_id'] = $goods->shop_id;
        $data['goods_info']['gc_id'] = $goods->gc_id;
        $data['goods_info']['goods_serial'] = $goods->goods_serial;
        $data['goods_info']['goods_stock'] = $goods->goods_stock;
        $data['goods_info']['goods_marketprice'] = $goods->goods_marketprice ?? 0;

        //当前sku信息
        $sku_info = [];
        $spec_name_array = empty($goods->spec_name) ? null : unserialize($goods->spec_name);
        if ($spec_name_array && is_array($spec_name_array)) {
            $goods_spec_array = array_values($goods->goods_spec);
            foreach ($spec_name_array as $k => $spec_name) {
                $goods_spec = isset($goods_spec_array[$k]) ? ':' . $goods_spec_array[$k] : '';
                $sku_info[] = $spec_name . $goods_spec;
            }
        }
        $data['sku_info'] = implode(' ', $sku_info);

        //记录redis
        $key = md5($user_id . 'cart_fastbuy');
        $data['is_checked'] = '1';

        $params = json_encode($data);

        Redis::set($key, $params);

        return true;
    }

    /**
     * 获得商品规格数组
     * @Author hfh_wind
     * @param $id 商品id
     * @return mixed
     */
    public function getGoodsSpecListByGoodsId($id)
    {
        $spec_array = cacheRemember('cache_goods_sku_id_' . $id, now()->addMinutes(10), function () use ($id){
            return GoodsSku::where(['id' => $id])->first();
        });
        return $spec_array;
    }

    /**
     * 秒杀商品详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request, GoodsRepository $repository)
    {
        //这里的id是sku_id
        $id = intval($request->goods_id);
        $sku_id = intval($request->sku_id);
        $seckill_ap_id = intval($request->activity_id);
        if ($id <= 0) {
            return $this->resFailed(414, '参数错误!');
        }

        //$detail_goods = $repository->detail($id);
        //
        $detail_goods = cacheRemember('cache_detail_goods_id_' . $id, now()->addMinutes(10), function () use ($id){
            return Goods::find($id);
        });

        if ($detail_goods['shop']['shop_state'] != 1) {
            return $this->resFailed(700, '店铺未开启!');
        }

        //如果商品有规格
        $spec_array = $this->getGoodsSpecListByGoodsId($sku_id);

        if (empty($spec_array)) {
            return $this->resFailed(700, '商品sku不存在!');
        }

        $redis=new Redis();

        //秒杀活动
        $seckill = new \ShopEM\Services\SecKillService();
        $res = $seckill->SecKillGoods($sku_id, $seckill_ap_id);

        if (isset($res['is_sec_kill'])) {
            $detail['is_sec_kill'] = 1;
            $detail['goods_stock'] = $res['goods_stock'];
        } else {
            $detail['is_sec_kill'] = 0;
            $detail['goods_stock'] = 0;
        }

       /* $goods_buy_key = "seckill_" . $sku_id . '_good_record_' . $seckill_ap_id;//商品购买库存
        $goods_buy_record = $redis::get($goods_buy_key);*/

        //改成资格人数来判断是否售完
        $user_queue_key = "seckill_" . $sku_id  . "_user_" . $seckill_ap_id;//当前商品队列的用户情况
        $record=Redis::hlen($user_queue_key);
        if ($record >= $res['goods_stock']) {
            $detail['is_sec_kill'] = 4; //售完
        }


        //商品基础信息
        $detail['shop_id'] = $spec_array['shop_id'];
        $detail['title'] = $res['sec_kill_info']['title'];
        $detail['goods_price'] = $spec_array['goods_marketprice'];
        $detail['seckill_price'] = $res['sec_kill_info']['seckill_price'];
        $detail['goods_image'] = $spec_array['goods_image'];
        $detail['goods_name'] = $res['sec_kill_info']['goods_name'];
        $detail['start_time'] = $res['sec_kill_info']['start_time'];
        $detail['end_time'] = $res['sec_kill_info']['end_time'];
        $detail['image_list'] = $detail_goods['image_list'];
        $detail['goods_info'] = $detail_goods['goods_info'];
        $detail['goods_body'] = $detail_goods['goods_body'];
        $detail['seckill_wait_time'] = 3;

        return $this->resSuccess($detail);
    }
    /**
     * 是否卖完
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getSoldOut($goods)
    {

        $record_key = "seckill_" . $goods['sku_id'] . "_good_record_" . $goods['seckill_ap_id'];//用来对比购买的库存
        $record = Redis::get($record_key) ? Redis::get($record_key) : 0;

        $soldOut = (($record >= $goods['seckills_stock']) && ($goods['seckills_stock'] > 0)) ? 'yes' : 'no';

        return $soldOut;
    }

    /**
     * 百分比
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getPercent($goods)
    {

       // $record_key = "seckill_" . $this->sku_id . "_good_record_" . $this->seckill_ap_id;//用来对比购买的库存
       // $record=Redis::get($record_key)?Redis::get($record_key):0;

        $user_queue_key = "seckill_" . $goods['sku_id'] . "_user_" . $goods['seckill_ap_id'];//当前商品队列的用户情况

        $record = Redis::hlen($user_queue_key);

        $percent = $record / $goods['seckills_stock'];
        $percent = intval(sprintf("%.2f", round($percent, 2)) * 100);
        return $percent;
    }   


}
