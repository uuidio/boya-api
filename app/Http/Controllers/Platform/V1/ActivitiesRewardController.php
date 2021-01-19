<?php
/**
 * @Filename    ActivitiesTransmitController.php
 *
 * @Copyright   Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License     Licensed <http://www.shopem.cn/licenses/>
 * @authors     hfh
 * @date        2019-03-19 15:16:03
 * @version     V1.0
 */
namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\ActivitiesRewardGoods;
use ShopEM\Http\Requests\Seller\DeliveryTradeRequest;
use ShopEM\Http\Requests\Platform\ActivitiesRewardGoodsCreateRequest;
use ShopEM\Http\Requests\Platform\ActivitiesRewardCreateRequest;
use ShopEM\Models\ActivitiesRewards;
use ShopEM\Models\ActivitiesRewardsSendLogs;
use ShopEM\Repositories\ActivitiesRewardGoodsRepository;
use ShopEM\Repositories\ActivitiesRewardRepository;
use ShopEM\Repositories\ActivitiesRewardsSendLogsRepository;

class ActivitiesRewardController extends BaseController
{

    /**
     * 活动奖品列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @param ActivitiesRewardGoodsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesRewardGoodsList(Request $request, ActivitiesRewardGoodsRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] =$input_data['per_page']??config('app.per_page');
        $input_data['gm_id'] = $this->GMID;

        $lists = $repository->Search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 活动实物奖品添加
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesRewardGoodsCreate(ActivitiesRewardGoodsCreateRequest $request)
    {
        $data = $request->only('goods_name', 'goods_info','shop_id','goods_id','sku_id','gc_id','brand_id','goods_price','goods_serial','goods_barcode','spec_name','goods_image','goods_body','is_use');


        DB::beginTransaction();
        try {
            $data['gm_id'] = $this->GMID;
            // 添加活动信息
            ActivitiesRewardGoods::create($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }


    /**
     * 活动实物奖品修改
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesRewardGoodsUpdate(ActivitiesRewardGoodsCreateRequest $request)
    {
        $id = $request['id']??0;
        if (intval($id) <= 0) {
            return $this->resFailed(414,'参数错误!');
        }

        $data = $request->only('goods_name', 'goods_info','shop_id','goods_id','sku_id','gc_id','brand_id','goods_price','goods_serial','goods_barcode','spec_name','goods_image','goods_body','is_use');


        DB::beginTransaction();
        try {
            $info = ActivitiesRewardGoods::find($id);
            if(empty($info)){
                return $this->resFailed(700,'修改数据不存在!');
            }

            $info->update($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }


    /**
     * 实物奖品详情
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesRewardGoodsDetail(Request $request)
    {
        $id = $request['id']??0;

        $info = ActivitiesRewardGoods::find($id);
        if(empty($info)){
            return $this->resFailed(700,'数据不存在!');
        }

        return $this->resSuccess($info);
    }

    /**
     * 活动实物奖品删除
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesRewardGoodsDelete(Request $request)
    {
        $id=$request['id']??0;
        if (intval($id) <= 0) {
            return $this->resFailed(414,'参数错误!');
        }

        DB::beginTransaction();
        try {
            ActivitiesRewardGoods::destroy($id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }




    /**
     * 活动关联的奖品列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @param ActivitiesRewardRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesRewardList(Request $request, ActivitiesRewardRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] =$input_data['per_page']??config('app.per_page');
        $input_data['gm_id'] = $this->GMID;

        $lists = $repository->Search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 活动关联实物奖品配置添加
     * @Author hfh_wind
     * @param ActivitiesRewardCreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesRewardCreate(ActivitiesRewardCreateRequest $request)
    {
        $data = $request->only('activities_reward_goods_id','activities_id','type','goods_stock','is_use');

        DB::beginTransaction();
        try {
            $data['gm_id'] = $this->GMID;
            // 添加活动信息
            ActivitiesRewards::create($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }


    /**
     * 活动关联实物奖品配置修改
     * @Author hfh_wind
     * @param ActivitiesRewardCreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesRewardUpdate(ActivitiesRewardCreateRequest $request)
    {
        $id = $request['id']??0;
        if (intval($id) <= 0) {
            return $this->resFailed(414,'参数错误!');
        }

        $data = $request->only('activities_reward_goods_id','activities_id','type','goods_stock','is_use');

        DB::beginTransaction();
        try {
            $info = ActivitiesRewards::find($id);
            if(empty($info)){
                return $this->resFailed(700,'修改数据不存在!');
            }

            $info->update($data);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }


    /**
     * 活动关联实物奖品配置详情
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesRewardDetail(Request $request)
    {
        $id = $request['id']??0;

        $activities_id=$request['activities_id']??0;

        if($id){
            $info = ActivitiesRewards::find($id);
            if(empty($info)){
                return $this->resFailed(700,'数据不存在!');
            }
        }elseif($activities_id){
            $info = ActivitiesRewards::where('activities_id',$activities_id);
            if(empty($info)){
                return $this->resFailed(700,'数据不存在!');
            }
        }

        return $this->resSuccess($info);
    }

    /**
     * 活动关联实物奖品配置删除
     *
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesRewardDelete(Request $request)
    {
        $id=$request['id']??0;
        if (intval($id) <= 0) {
            return $this->resFailed(414,'参数错误!');
        }

        DB::beginTransaction();
        try {
            ActivitiesRewards::destroy($id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }





    /**
     * 获奖列表
     * @Author hfh_wind
     * @param Request $request
     * @param ActivitiesRewardsSendLogsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesRewardsSendLogs(Request $request,ActivitiesRewardsSendLogsRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] =$input_data['per_page']??config('app.per_page');
        $input_data['gm_id'] = $this->GMID;

        $lists = $repository->Search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 获奖列表信息下载
     * @Author hfh_wind
     * @param Request $request
     * @param ActivitiesRewardsSendLogsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function ActivitiesRewardsSendLogsDown(Request $request,ActivitiesRewardsSendLogsRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] =$input_data['per_page']??config('app.per_page');
        $input_data['gm_id'] = $this->GMID;

        $lists = $repository->Search($input_data,1);

        //获取下载表头
        $title=$repository->listFields();
        $return['trade']['tHeader']= array_column($title,'title'); //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['trade']['list']= $lists; //表头

        return $this->resSuccess($return);
    }




    /**
     * 对指定订单进行发货，交易发货
     *
     * @Author hfh_wind
     * @param $params
     * @return mixed
     */
    public function DeliveryTrade(DeliveryTradeRequest $params)
    {
        $data = $params->only('tid', 'corp_code', 'logi_no', 'ziti_memo', 'memo', 'shop_id', 'seller_id');

        $tid = $data['tid'];
        $corpCode = $data['corp_code'];
        $logiNo = $data['logi_no'];
        $zitiMemo = !empty($data['ziti_memo']) ? $data['ziti_memo'] : '';
        $memo = !empty($data['memo']) ? $data['memo'] : '';
        $shopUserData = [
            'shop_id'   => 0,
            'seller_id' => 0,
        ];
        //  unset($params);
        $doDelivery = new \ShopEM\Services\TradeService();
        $res=$doDelivery->doDelivery($tid, $corpCode, $logiNo, $shopUserData, $zitiMemo, $memo);

        if($res){
            ActivitiesRewardsSendLogs::where('tid',$tid)->update(['is_redeem'=>2]);
        }

        return $this->resSuccess([], '发货成功!');
    }


    /**
     * [pickUp 提货操作]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function pickUp(Request $request)
    {
        if (!$request->filled('tid') || !$request->filled('code')) {
            return $this->resFailed(414, '参数不全');
        }
        $data = $request->only('tid', 'code');
        DB::beginTransaction();
        try {
            $tradeModel = new \ShopEM\Models\Trade; 
            $trade = $tradeModel::where('tid', $data['tid'])->where('shop_id',0)->where('gm_id', $this->GMID)->where('pick_code', $data['code'])->first();
            if (!$trade) {
                return $this->resFailed(500, '提货码错误');
            }
            if ($trade->pick_statue) {
                return $this->resFailed(500, '该订单已提货');
            }

            switch ($trade->status) {
                case 'TRADE_FINISHED':
                    return $this->resFailed(500, '该订单已完成');
                    break;
                case 'TRADE_CLOSED':
                    return $this->resFailed(500, '该订单已关闭');
                    break;
                case 'TRADE_CLOSED_BY_SYSTEM':
                    return $this->resFailed(500, '该订单已被系统关闭');
                    break;
                case 'WAIT_BUYER_PAY':
                    return $this->resFailed(500, '该订单未付款');
                    break;
            }
            $endtime = now()->toDateTimeString();
            $trade->pick_statue = 1;
            $trade->status = 'TRADE_FINISHED';
            $trade->end_time = $endtime;
            $trade->confirm_at = $endtime;
            $trade->save();
            $tid = $trade->tid;

            \ShopEM\Models\TradeOrder::where('tid', $tid)->update([
                'status' => 'TRADE_FINISHED',
                'end_time' => $endtime,
                'confirm_at' => $endtime
            ]);
            
            ActivitiesRewardsSendLogs::where('tid',$tid)->update(['is_redeem'=>2]);
            //收货后的操作
            $tradeService = new \ShopEM\Services\TradeService;
            $tradeService->gainPonit($tid);
            $tradeService->confirmTradeEvent($trade);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(414, $e->getMessage());
        }
        return $this->resSuccess();
    }
}