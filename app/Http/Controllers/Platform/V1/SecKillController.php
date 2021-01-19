<?php
/**
 * @Filename ShopController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author         hfh
 */


namespace ShopEM\Http\Controllers\Platform\V1;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use ShopEM\Http\Controllers\Platform\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\SecKillGood;
use ShopEM\Models\SecKillApplie;
use Illuminate\Support\Facades\Validator;
use ShopEM\Http\Requests\Platform\SeckillRequest;
use ShopEM\Models\SecKillAppliesRegister;
use ShopEM\Repositories\SecKillGoodRepository;
use ShopEM\Repositories\SecKillAppliesRepository;
use ShopEM\Repositories\SecKillRegisterListRepository;
use Illuminate\Http\Exceptions\HttpResponseException;
use ShopEM\Http\Requests\Platform\SeckillRegisterApproveRequest;
use ShopEM\Services\SecKillService;

class SecKillController extends BaseController
{
    /**
     *  秒杀活动列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function SecKillAppliesLists(Request $request, SecKillAppliesRepository $repository)
    {

        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['gm_id'] = $this->GMID;

        $lists = $repository->search($input_data);

        if (empty($lists)) {
            $lists = [];
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     *  保存秒杀活动
     *
     * @Author hfh_wind
     * @param SeckillRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function CreateSeckillApplies(SeckillRequest $request)
    {
        $data = $request->only('activity_name', 'activity_tag', 'activity_desc', 'apply_begin_time', 'apply_end_time',
            'release_time',
            'start_time', 'end_time', 'enroll_limit', 'limit_cat', 'shoptype', 'enabled', 'remind_way');

        $msg_text = "创建秒杀活动" . $data['activity_name'];
        //验证数据
        $this->__checkAddPost($data);

        try {
            $data['gm_id'] = $this->GMID;
            $res = SecKillApplie::create($data);

            if (!$res) {
                return $this->resFailed(701, '创建失败!');
            }

        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        (new SecKillService)->updateSeckillApplieCache($data['gm_id']);
        return $this->resSuccess([], '创建成功!');
    }


    /**
     *  更新秒杀活动
     *
     * @Author hfh_wind
     * @param SeckillRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function UpdateSeckillApplies(Request $request, SeckillRequest $seckillRequest)
    {
        $data = $seckillRequest->only('activity_name', 'activity_tag', 'activity_desc', 'apply_begin_time',
            'apply_end_time',
            'release_time',
            'start_time', 'end_time', 'enroll_limit', 'limit_cat', 'shoptype', 'enabled', 'remind_way');

        $id = $request->id;
        if ($id <= 0) {
            return $this->resFailed(414, 'id必填!');
        }
        //验证数据
        $info = $this->__checkEditPost($id, $data);

        $msg_text = "更新秒杀活动" . $info['activity_name'];
        try {

            $res = SecKillApplie::where(['id' => $id])->update($data);

            if (!$res) {
                return $this->resFailed(701, '更新失败!');
            }

        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
       
        (new SecKillService)->updateSeckillApplieCache($this->GMID);
        return $this->resSuccess([], '更新成功!');
    }


    /**
     *  平台活动活动详情
     *
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function ListDetailSeckillApplies(Request $request)
    {
        $id = $request->id;
        if ($id <= 0) {
            return $this->resFailed(414, '参数错误!');
        }
        $secKillApplie = SecKillApplie::find($id);
        
        if ($secKillApplie->gm_id != $this->GMID) 
        {
            return $this->resFailed(700);
        }

        $detail['secKillApplie'] = $secKillApplie;
        //参加活动的商品
        $secKillGood = SecKillGood::where(['seckill_ap_id' => $id])->get();
        $detail['secKillGood'] = [];
        if (count($secKillGood) > 0) {
            $detail['secKillGood'] = $secKillGood;
        }

        return $this->resSuccess($detail);
    }


    /**
     *  参加活动的商品列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @param SecKillGoodRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function SecKillAppliesGoodList(Request $request, SecKillGoodRepository $repository)
    {

        $input_data['seckill_ap_id'] = $request->id;
        $input_data['per_page'] = config('app.per_page') ? config('app.per_page') : 10;
        $input_data['gm_id'] = $this->GMID;
        $lists = $repository->search($input_data);


        return $this->resSuccess([
//            'SecKillInfo' => $register,
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 将审核状态改为下架
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function ShelvesGood(Request $request)
    {
        $id = $request->id;
        $verify_status = $request->verify_status;
        if ($id <= 0) {
            return $this->resFailed(414, '参数错误!');
        }

        $msg_text = $verify_status == '3' ? '下架' : '上架';

        $secKillmod = SecKillGood::find($id);

        if (empty($secKillmod)) {
            return $this->resFailed(700, '查询数据为空!');
        }

        if ($secKillmod['verify_status'] == '0') {
            return $this->resFailed(700, '该商品还未审核,不能下架!');
        }

        try {
            //将审核状态改为下架
            $secKillmod->update(['verify_status' => $verify_status]);

        } catch (\Exception $e) {
            //日志
            $this->adminlog("秒杀商品" . $msg_text . $secKillmod['goods_name'], 0);

            throw new \Exception('下架失败!' . $e->getMessage());
        }

        //日志
        $this->adminlog("秒杀商品" . $msg_text . $secKillmod['goods_name'], 1);
        (new SecKillService)->updateSeckillApIdCache($secKillmod['seckill_ap_id']);

        return $this->resSuccess([], '下架成功!');
    }


    /**
     * 报名详情
     *
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function DetailSeckillApplies(Request $request)
    {
        $id = $request->id;
        if ($id <= 0) {
            return $this->resFailed(414, '参数错误!');
        }

        $detail['register'] = SecKillAppliesRegister::find($id);

        if (empty($detail['register'])) {
            return $this->resFailed(700, '查询数据为空!');
        }
        $seckill_ap_id = $detail['register']['seckill_ap_id'];
        $detail['secKillApplie'] = SecKillApplie::where(['id' => $seckill_ap_id])->first();
        //参加活动的商品
        $secKillGood = SecKillGood::where(['seckill_ap_id' => $seckill_ap_id])->get();
        $detail['secKillGood'] = [];
        if (count($secKillGood) > 0) {
            $detail['secKillGood'] = $secKillGood;
        }

        return $this->resSuccess($detail);
    }

    /**
     *  删除秒杀活动
     *
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function DeleteSeckillApplies(Request $request)
    {   
        $id = $request->id;
        if ($id <= 0) {
            return $this->resFailed(414, '参数错误!');
        }
        $apply = SecKillApplie::find($id);
        if (empty($apply)) {
            return $this->resFailed(701, '没有此活动');
        }
        $now = time();
        $star = strtotime($apply->start_time);
        $stop = strtotime($apply->end_time);
        if ($now > $star && $now < $stop) {
            return $this->resFailed(701, '该活动进行中，不能删除!');
        }
        $msg_text = "删除秒杀活动-" . $apply['id'] . "-" . $apply['activity_name'];
        DB::beginTransaction();
        try {
            SecKillApplie::destroy($id);
            SecKillGood::where(['seckill_ap_id' => $id])->update(['spec_sign' => '废除活动', 'end_time' => null]);
            SecKillAppliesRegister::where(['seckill_ap_id' => $id])->update(['is_delete'=>1]);

            $now_activiy = SecKillGood::where(['seckill_ap_id' => $id])->get();
            foreach ($now_activiy as $key => $redis_v) {
                $sku_id = $redis_v['sku_id'];
                $seckill_ap_id = $redis_v['seckill_ap_id'];
                $goods_queue_key = "seckill_" . $sku_id . "_good_" . $seckill_ap_id;//当前商品的库存队列
                $stock_limit_key = "seckill_" . $sku_id . "_limit_num_" . $seckill_ap_id; //购买限制数量,废弃
                $user_queue_key = "seckill_" . $sku_id . "_user_" . $seckill_ap_id;//当前商品队列的用户情况
                Redis::del($goods_queue_key);
                Redis::del($stock_limit_key);
                Redis::del($user_queue_key);

                //拒绝活动后回退商品库存
                (new SecKillService)->secKillStock($seckill_ap_id, $redis_v, $redis_v['seckills_stock'], 'inc', '活动被删除，回退商品库存');

                /*$seckill_good_key = 'seckill_good_' . $sku_id; //秒杀商品的缓存key
                Cache::forget($seckill_good_key);*/
            }


            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            $this->adminlog($msg_text, 0);
            throw new \Exception('删除失败!' . $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        (new SecKillService)->updateSeckillApplieCache($this->GMID);
        return $this->resSuccess([], '删除成功!');
    }


    /**
     *  强制删除秒杀活动(活动中也可以)
     *
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function DeleteForceSeckillApplies(Request $request)
    {
        $id = $request->id;
        if ($id <= 0) {
            return $this->resFailed(414, '参数错误!');
        }
        $apply = SecKillApplie::find($id);
        if (empty($apply)) {
            return $this->resFailed(701, '没有此活动');
        }

        $msg_text = "删除秒杀活动-" . $apply['id'] . "-" . $apply['activity_name'];
        DB::beginTransaction();
        try {
            SecKillApplie::destroy($id);
           // SecKillGood::where(['seckill_ap_id' => $id])->update(['spec_sign'=>'废除活动','end_time'=>0]);
           // SecKillAppliesRegister::where(['seckill_ap_id' => $id])->delete();
            $now_activiy = SecKillGood::where(['seckill_ap_id' => $id])->get();
            foreach ($now_activiy as $key => $redis_v) {
                $sku_id = $redis_v['sku_id'];
                $seckill_ap_id = $redis_v['seckill_ap_id'];
                $goods_queue_key = "seckill_" . $sku_id . "_good_" . $seckill_ap_id;//当前商品的库存队列
                $stock_limit_key = "seckill_" . $sku_id . "_limit_num_" . $seckill_ap_id; //购买限制数量,废弃
                $user_queue_key = "seckill_" . $sku_id . "_user_" . $seckill_ap_id;//当前商品队列的用户情况
                Redis::del($goods_queue_key);
                Redis::del($stock_limit_key);
                Redis::del($user_queue_key);

                $goods_buy_key = "seckill_" . $sku_id . '_good_record_' . $seckill_ap_id;//当前已售商品库存
                $goods_buy_record = Redis::get($goods_buy_key);//当前已售商品库存
                $goods_buy_record = $goods_buy_record ?: 0;
                $goods_stock = $redis_v['seckills_stock'] - $goods_buy_record;

                //强制拒绝活动后回退商品库存
                (new SecKillService)->secKillStock($seckill_ap_id, $redis_v, $goods_stock, 'inc', '活动被强制删除，回退商品库存');

                /*$seckill_good_key = 'seckill_good_' . $sku_id; //秒杀商品的缓存key
                Cache::forget($seckill_good_key);*/
            }
            SecKillGood::where(['seckill_ap_id' => $id])->delete();
            SecKillAppliesRegister::where(['seckill_ap_id' => $id])->update(['is_delete'=>1]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            $this->adminlog($msg_text, 0);
            throw new \Exception('删除失败!' . $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        (new SecKillService)->updateSeckillApplieCache($this->GMID);
        return $this->resSuccess([], '删除成功!');
    }


    /**
     * 活动报名审核列表
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */
    public function SecKillRegisterList(Request $request, SecKillRegisterListRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page') ? config('app.per_page') : 10;
        $input_data['gm_id'] = $this->GMID;
        $input_data['is_delete'] = 0;

        $lists = $repository->search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 审核活动
     *
     * @Author hfh_wind
     * @param $params
     * @return \Illuminate\Http\JsonResponse
     * @throws
     * @throws Exception
     */
    public function RegisterApprove(SeckillRegisterApproveRequest $request)
    {
        $data = $request->only('registers_id', 'shop_id', 'status', 'reason');

        $filter = ['id' => $data['registers_id'], 'shop_id' => $data['shop_id']];

        $registerInfo = SecKillAppliesRegister::where($filter)->first();

        if (empty($registerInfo)) {
            return $this->resFailed(701, '审核数据为空!');
        }
        $seckill_ap_id = $registerInfo->seckill_ap_id;

        $activityInfo = SecKillApplie::where(['id' => $seckill_ap_id])->where('gm_id',$this->GMID)->select('start_time', 'end_time', 'apply_begin_time')->first()->toArray();

        $nowTime = date('Y-m-d H:i:s', time());

        if ($activityInfo['apply_begin_time'] > $nowTime) {
            return $this->resFailed(701, '尚未开始报名,无法审核');
        }

        // 审批通过
        if ($data['status'] == '2') {
            if ($registerInfo['verify_status'] == '2') {
                return $this->resFailed(701, '活动已经通过了,请勿重复审核！');
            }

            $filter = ['seckill_ap_id' => $seckill_ap_id, 'shop_id' => $data['shop_id']];

            DB::beginTransaction();
            try {
                if ($nowTime > $activityInfo['start_time']) {
                    return $this->resFailed(701, '活动时间已经开始，不可以对其活动进行操作！');
                }

                if (!SecKillAppliesRegister::where($filter)->update(['verify_status' => '2'])) {
                    return $this->resFailed(701, '活动审核失败!');
                }
                if (!SecKillGood::where($filter)->update(['verify_status' => '2'])) {
                    return $this->resFailed(701, '活动商品审核失败!');
                }

                //库存写入缓存
                $now_activiy = SecKillGood::where($filter)->get();
                foreach ($now_activiy as $key => $redis_v) {
                    $sku_id = $redis_v['sku_id'];
                    $seckill_ap_id = $redis_v['seckill_ap_id'];
                    $goods_queue_key = "seckill_" . $sku_id . "_good_" . $seckill_ap_id;//当前商品的库存队列
                    $stock_limit_key = "seckill_" . $sku_id . "_limit_num_" . $seckill_ap_id; //购买限制数量,废弃

                    /*//把秒杀商品加到缓存里
                    $seckill_good_key = 'seckill_good_' . $sku_id; //秒杀商品的缓存key
                    $expiresAt = \Carbon\Carbon::now()->diffInSeconds($activityInfo['end_time']); //有效期为当前时间到秒杀结束时间的秒数
                    Cache::put($seckill_good_key, $activityInfo['end_time'], $expiresAt);*/

                    $secKills_stock = $redis_v['seckills_stock'];//秒杀库存

                    $stock_limit = $redis_v['stock_limit'];
                    Redis::set($stock_limit_key, $stock_limit);//个人限制购买数量

                    $gnRedis = Redis::get($goods_queue_key);//当前商品的库存队列
                    /* 设置等值商品库存记录 */
                    if (!$gnRedis) {
                        Redis::set($goods_queue_key, $secKills_stock);
                    }
                }


                DB::commit();

            } catch (\Exception  $e) {
                DB::rollBack();
                //日志
                $this->adminlog("审核通过商家" . $registerInfo['shop_name'] . "提交秒杀商品", 0);
                throw new \Exception('审批通过失败!' . $e->getMessage());
            }
            //日志
            $this->adminlog("审核通过商家" . $registerInfo['shop_name'] . "提交秒杀商品", 1);
            (new SecKillService)->updateSeckillApIdCache($seckill_ap_id);

            return $this->resSuccess([], '审核成功!');
        }

        // 审批驳回
        if ($data['status'] == '1') {
            if ($registerInfo['verify_status'] == '1') {
                return $this->resFailed(701, '活动已经通过了,请勿重复审核！');
            }
            $filter = ['seckill_ap_id' => $seckill_ap_id, 'shop_id' => $data['shop_id']];

            DB::beginTransaction();
            try {
                if ($nowTime > $activityInfo['start_time']) {
                    return $this->resFailed(701, '发布时间已过，不可以对其活动进行操作！');
                }
                if (!SecKillAppliesRegister::where($filter)->update([
                    'verify_status' => '1',
                    'refuse_reason' => isset($data['reason']) ? $data['reason'] : ''
                ])
                ) {
                    return $this->resFailed(701, '活动审核失败!');
                }
                if (!SecKillGood::where($filter)->update(['verify_status' => '1'])) {
                    return $this->resFailed(701, '活动商品审核失败!');
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                //日志
                $this->adminlog("驳回商家" . $registerInfo['shop_name'] . "提交秒杀商品", 0);
                throw new \Exception('审批驳回失败!' . $e->getMessage());
            }
            //日志
            $this->adminlog("驳回商家" . $registerInfo['shop_name'] . "提交秒杀商品", 1);
            return $this->resSuccess([], '审核成功!');
        }

    }


    /**
     *  添加活动验证信息
     *
     * @Author hfh_wind
     * @param $ruledata
     * @param null $msg
     * @return bool
     */
    private function __checkAddPost($ruledata, &$msg = null)
    {

        $rules = [
            'time'             => date('Y-m-d H:i:s', time()),
            'apply_begin_time' => 'after:time',
            'apply_end_time'   => 'after:apply_begin_time',
            'start_time'       => 'after:apply_end_time',
            'end_time'         => 'after:start_time',
        ];
        $messages = [
            'apply_begin_time.after' => '活动报名的开始时间必须大于当前时间',
            'apply_end_time.after'   => '活动报名结束时间必须大于报名的开始时间',
            'start_time.after'       => '活动开始时间必须大于活动报名结束时间',
            'end_time.after'         => '活动生效结束时间必须大于活动开始时间',
        ];
        $validator = Validator::make($ruledata, $rules, $messages);

        $error = $validator->errors()->all();
        if ($error) {
            throw new HttpResponseException(response()->json([
                'errorcode' => '414',
                'message'   => $error,
                'result'    => []
            ], 200));
        } else {
            return true;
        }
    }


    /**
     *  添加活动验证信息
     *
     * @Author hfh_wind
     * @param $ruledata
     * @param null $msg
     * @return bool
     */
    private function __checkEditPost($id, $ruledata, &$msg = null)
    {

        $activityInfo = SecKillApplie::where(['id' => $id])->select('start_time')->frist();

        $nowTime = time();

        if ($nowTime > $activityInfo['start_time']) {
            $this->resFailed([], '活动时间已经开始，不可以对其活动进行操作！');
        }

        $rules = [
            'time'             => date('Y-m-d H:i:s', time()),
            'apply_begin_time' => 'after:time',
            'apply_end_time'   => 'after:apply_begin_time',
            'start_time'       => 'after:apply_end_time',
            'end_time'         => 'after:start_time',
        ];
        $messages = [
            'apply_begin_time.after' => '活动报名的开始时间必须大于当前时间',
            'apply_end_time.after'   => '活动报名结束时间必须大于报名的开始时间',
            'start_time.after'       => '活动开始时间必须大于活动报名结束时间',
            'end_time.after'         => '活动生效结束时间必须大于活动开始时间',
        ];
        $validator = Validator::make($ruledata, $rules, $messages);

        $error = $validator->errors()->all();
        if ($error) {
            throw new HttpResponseException(response()->json([
                'errorcode' => '414',
                'message'   => $error,
                'result'    => []
            ], 200));
        } else {
            return $activityInfo;
        }
    }

    /**
     *  活动报名商品
     *
     * @Author hfh_wind
     * @param Request $request
     * @param SecKillGoodRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function SecKillGoodList(Request $request, SecKillGoodRepository $repository)
    {
        $input_data = $request->all();
        if (!isset($request->id) && empty($request->id)) {
            return $this->resFailed(414, '参数错误!');
        }

        $register = SecKillAppliesRegister::find($request->id);

        if (empty($register)) {
            return $this->resFailed(700, '查询数据为空!');
        }
        unset($input_data['id']);

        $input_data['seckill_ap_id'] = $register->seckill_ap_id;
        $input_data['shop_id'] = $register->shop_id;
        $input_data['per_page'] = config('app.per_page') ? config('app.per_page') : 10;
        $input_data['gm_id'] = $this->GMID;

        $lists = $repository->search($input_data);


        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


}
