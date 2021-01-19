<?php
/**
 * @Filename GroupController.php
 *  团购
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Support\Facades\Redis;
use ShopEM\Http\Controllers\Seller\BaseController;
use Illuminate\Http\Request;
use ShopEM\Models\Group;
use ShopEM\Models\GroupsMains;
use ShopEM\Models\GroupsUserOrder;
use ShopEM\Models\PointActivityGoods;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Requests\Seller\GroupApplySaveRequest;
use ShopEM\Repositories\GroupGoodRepository;
use ShopEM\Repositories\GroupListRepository;

class GroupController extends BaseController
{



    /**
     *  活动报名主商品
     *
     * @Author hfh_wind
     * @param Request $request
     * @param SecKillGoodRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function GroupList(Request $request, GroupListRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = isset($input_data['per_page']) ? $input_data['per_page'] : config('app.per_page');
        $input_data['shop_id'] = $this->shop->id;
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
     *  活动报名明细商品（子商品）
     *
     * @Author hfh_wind
     * @param Request $request
     * @param SecKillGoodRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function GroupGoodList(Request $request, GroupGoodRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['shop_id'] = $this->shop->id;
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
     * 团购活动详情
     *
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function RegisteredDetail(Request $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414, '参数错误!');
        }

       /* $register = Group::find($id);

        if (empty($register)) {
            return $this->resFailed(700, '数据为空!');
        }*/

        $return['Group'] = GroupsMains::where('id', $id)->where('shop_id', $this->shop->id)->first();
        if (empty($return['Group'])) {
            return $this->resFailed(700, '数据为空!');
        }

        $return['GroupGoods'] = Group::where('main_id', $id)->get();

        return $this->resSuccess($return);
    }


    /**
     * 团购活动删除
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function GroupApplyDelete(Request $request)
    {
        $activityId = intval($request->id);
        if ($activityId <= 0) {
            return $this->resFailed(414, '参数错误!');
        }

        // 获取活动规则信息
        $time = date('Y-m-d H:i:s', time());
        $activity = GroupsMains::where(['id' => $activityId])->where('shop_id', $this->shop->id)->first();

        if (empty($activity)) {
            return $this->resFailed(414, '活动不存在!');
        }
        if ($activity['start_time'] < $time && $time < $activity['end_time']) {
            return $this->resFailed(704, '活动进行中请勿删除!');
        }
        try {

            GroupsMains::where('id', '=', $activityId)->delete();
            Group::where('main_id', '=', $activityId)->delete();

        } catch (\LogicException $e) {

            throw new \Exception('删除失败!' . $e->getMessage());
        }


        return $this->resSuccess([], "删除成功!");
    }


    /**
     * 团购活动强制删除
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function GroupApplyDeleteForce(Request $request)
    {
        $activityId = intval($request->id);
        if ($activityId <= 0) {
            return $this->resFailed(414, '参数错误!');
        }

        // 获取活动规则信息
        $activity = GroupsMains::where(['id' => $activityId])->where('shop_id', $this->shop->id)->first();

        if (empty($activity)) {
            return $this->resFailed(414, '活动不存在!');
        }
        DB::beginTransaction();
        try {

            GroupsMains::where('id', '=', $activityId)->delete();
            $lists = Group::where(['main_id' => $activityId])->get();
            foreach ($lists as $group) {
                //把进行中的拼团订单改为失败
                GroupsUserOrder::where('groups_id', $group->id)->where('status', 1)->update(['status' => 0]);
            }
            Group::where('main_id', '=', $activityId)->delete();
            DB::commit();
        } catch (\LogicException $e) {
            DB::rollBack();
            throw new \Exception('强制删除失败!' . $e->getMessage());
        }


        return $this->resSuccess([], "强制删除成功!");
    }


    /**
     * 保存提交数据
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function RegisteredApplySave(GroupApplySaveRequest $request)
    {
        $data = $request->all();
        //判断重复提交
        $goods_info_datas = $request->goods_info;
        $goods_info_datas = is_array($goods_info_datas) ? $goods_info_datas : json_decode($goods_info_datas, true);
        $time = date('Y-m-d H:i:s', time());

//        if ($request->start_time < $time) {
//            return $this->resFailed(704, '活动开始时间不能小于当前时间!');
//        }
        if ($data['end_time'] < $time) {
            return $this->resFailed(704, '活动结束时间不能小于当前时间!');
        }
        if ($data['start_time'] >= $data['end_time']) {
            return $this->resFailed(704, '活动开始时间不能大于等于活动结束时间!');
        }


        //秒杀活动判断
        $check_sec = new \ShopEM\Services\SecKillService();

        $check_sec = $check_sec->actingSecKill($data['goods_id']);

        if ($check_sec) {
            return $this->resFailed(704, '正在参加秒杀活动请勿添加该商品!');
        }

        //积分商品判断
        $check_point = PointActivityGoods::where('goods_id', $data['goods_id'])->first();
        if ($check_point) {
            return $this->resFailed(704, '正在参加积分活动请勿添加该商品!');
        }

        //店家营销活动的判断
        $actService = new \ShopEM\Services\Marketing\Activity();
        if ($actService->actingAct($data['goods_id'])) {
            return $this->resFailed(704, '正在参加营销活动请勿添加该商品!');
        }

        // 获取商家已经活动报名的商品信息
        $res = Group::where('end_time', '>=', $time)->where('goods_id', '=',
            $data['goods_id'])->count();

        if ($res) {
            return $this->resFailed(704, '该商品正在参加活动请勿重复!');
        }


        $shop_id = $this->shop->id;

        foreach ($goods_info_datas as $goods_info) {
            if(!isset($goods_info['group_price'])   ||  $goods_info['group_price'] <0){
                return $this->resFailed(700, '团购价格异常，请从新确认!');
            }
            //不允许拼团库存少于开团人数
            $sku = DB::table('goods_skus')->where('id', $goods_info['sku_id'])->select('goods_stock')->first();
            $goods_stock = $sku->goods_stock ?? 0;
            if ($goods_stock < $data['group_size']) {
                return $this->resFailed(704, '库存少于开团人数,请重新添加!');
            }
        }


        DB::beginTransaction();
        try {

            // 活动报名保存商品
            $insert = [
                'shop_id'          => $shop_id,
                'goods_id'         => $data['goods_id'],
                'gc_id_3'          => $data['gc_id_3'],
                'goods_name'       => $data['goods_name'],
                'price'            => $data['goods_price'],
                'group_price'      => $data['group_price'],
                'goods_image'      => $data['goods_image'],
                'start_time'       => $data['start_time'],
                'end_time'         => $data['end_time'],
                'group_size'       => $data['group_size'],
                'group_validhours' => $data['group_validhours'],
                'group_desc'       => $data['group_desc']??'',
                'sort'             => $data['sort'] ? $data['sort'] : 0,
                'rewards'          => $data['rewards']??0, //返利金额
                'profit_sharing'   => $data['profit_sharing']??0,//分成金额
                'gm_id'            => $this->GMID,
            ];
            $main_res = GroupsMains::create($insert);


            foreach ($goods_info_datas as $goods_info) {
                // 活动报名保存商品
                $insert = [
                    'shop_id'          => $shop_id,
                    'goods_id'         => $goods_info['goods_id'],
                    'main_id'          => $main_res['id'],
                    'sku_id'           => $goods_info['sku_id'],
                    'gc_id_3'          => $goods_info['gc_id_3'],
                    'goods_name'       => $goods_info['goods_name'],
                    'price'            => $data['goods_price'],
                    'group_price'      => $goods_info['group_price'],
//                    'group_stock'      => $goods_info['group_stock'] ?? 0,
                    'goods_image'      => $goods_info['goods_image'],
                    'spec_sign'        => $goods_info['spec_sign'],
                    'start_time'       => $data['start_time'],
                    'end_time'         => $data['end_time'],
                    'group_size'       => $data['group_size'],
                    'group_validhours' => $data['group_validhours'],
                    'group_desc'       => $data['group_desc']??'',
                    'sort'             => $data['sort'] ? $data['sort'] : 0,
                    'rewards'          => $data['rewards']??0, //返利金额
                    'profit_sharing'   => $data['profit_sharing']??0,//分成金额
                    'gm_id'            => $this->GMID,
                ];

                $group_info = Group::create($insert);

                //开始时间和结束时间秒数差
                $group_goods_time = $this->timeDiffSecond($group_info['start_time'], $group_info['end_time']);

                $group_stock_key = $goods_info['sku_id'] . "_group_stock_" . $group_info['id'];
                //按照活动来设置团购的生存时间
                Redis::setex($group_stock_key, $group_goods_time, $group_info['group_stock']);
            }

            DB::commit();

        } catch (\LogicException $e) {
            DB::rollBack();
            throw new \Exception('添加活动失败!' . $e->getMessage());
        }

        return $this->resSuccess([], '添加活动成功!');

    }


    /**
     * 计算时间差,返回秒
     * @Author hfh_wind
     * @param $begin_time
     * @param $end_time
     * @return int
     */
    public function timeDiffSecond($begin_time, $end_time)
    {
        $starttime = strtotime($begin_time);
        $endtime = strtotime($end_time);

        $timediff = $endtime-$starttime;

        return $timediff;
    }


}
