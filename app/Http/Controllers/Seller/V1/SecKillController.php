<?php
/**
 * @Filename SecKillController.php
 *  秒杀
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Http\Controllers\Seller\BaseController;
use Illuminate\Http\Request;
use ShopEM\Models\Goods;
use ShopEM\Models\GoodsSku;
use ShopEM\Models\PointActivityGoods;
use ShopEM\Models\SecKillApplie;
use ShopEM\Models\SecKillAppliesRegister;
use ShopEM\Models\SecKillGood;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Requests\Seller\RegisteredApplySaveRequest;
use ShopEM\Models\SecKillStockLog;
use ShopEM\Models\SpecialActivity;
use ShopEM\Models\SpecialActivityItem;
use ShopEM\Repositories\GoodsSkuRepository;
use ShopEM\Repositories\GoodsRepository;
use ShopEM\Repositories\SecKillGoodRepository;
use ShopEM\Repositories\SecKillAppliesRepository;
use ShopEM\Repositories\SecKillRegisterListRepository;

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
        $shop_id = $this->shop->id;

        foreach ($lists as $key => $value) {
            $res = SecKillAppliesRegister::where(['seckill_ap_id' => $value['id'], 'shop_id' => $shop_id])->first();
            if ($res) {
                $lists[$key]['is_shop_apply'] = '0';
                $lists[$key]['register_id'] = $res->id;
            } else {
                $lists[$key]['is_shop_apply'] = '1';
                $lists[$key]['register_id'] = 0;
            }
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 活动报名列表
     * @Author hfh_wind
     * @return int
     */
    public function SecKillRegisterList(Request $request, SecKillRegisterListRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page') ? config('app.per_page') : '10';
        $input_data['is_delete'] = 0;//未删除
        $input_data['shop_id'] = $this->shop->id;
        $input_data['gm_id'] = $this->GMID;

        $lists = $repository->search($input_data);


        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     *  多规格商品
     *
     * @Author hfh_wind
     * @param Request $request
     * @param SecKillGoodRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodList(Request $request, GoodsSkuRepository $repository)
    {
        $data = $request->all();

        $data['shop_id'] = $this->shop->id;

        if (isset($data['is_group']) && $data['is_group']) {
            unset($data['is_group']);
            $time = date('Y-m-d H:i:s', time());
            $not_in_id = \ShopEM\Models\Group::where('shop_id', $data['shop_id'])->where('start_time', '<=', $time)->where('end_time', '>=', $time)->get()->pluck('sku_id');
            if ($not_in_id) {
                $data['not_in_id'] = $not_in_id;
            }
        }

        $lists = $repository->listItems($data);

        foreach ($lists as $key => &$value) {
            $goodConnectInfo = (new GoodsRepository)->goodConnectInfo($value['goods_id']);
            $goods_tab = array_merge($goodConnectInfo['activity'], $goodConnectInfo['promotion']);

            $value['goods_tab'] = implode(',', $goods_tab);
        }


        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
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
        if (!isset($request->seckill_ap_id) || empty($request->seckill_ap_id)) {
            return $this->resFailed(414, '参数错误!');
        }

        $input_data['seckill_ap_id'] = $request->seckill_ap_id;
        $input_data['per_page'] = config('app.per_page');
        $input_data['shop_id'] = $this->shop->id;

        $lists = $repository->search($input_data);


        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 已报名的活动详情
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

        $register = SecKillAppliesRegister::find($id);

        if (empty($register)) {
            return $this->resFailed(700, '报名数据为空!');
        }
        $shop_id = $this->shop->id;
        $detail['secKillApplie'] = SecKillApplie::where(['id' => $register->seckill_ap_id])->get();
        $seckillgood = SecKillGood::where(['seckill_ap_id' => $register->seckill_ap_id, 'shop_id' => $shop_id])->get()->toArray();
        foreach ($seckillgood as $key => &$value) {
            $value['goods_stock'] = GoodsSku::where('id', $value['sku_id'])->value('goods_stock');
        }
        $detail['seckillgood'] = $seckillgood;

        return $this->resSuccess($detail);
    }


    /**
     * 活动报名页面
     * @Author hfh_wind
     * @return mixed
     */
    public function RegisteredApply(Request $request)
    {
        $activityId = intval($request->id);
        if ($activityId <= 0) {
            return $this->resFailed(414, '参数错误!');
        }
        $pagedata = [];
        // 获取活动规则信息
        $activity = SecKillApplie::where(['id' => $activityId])->first();

        if (!empty($activity)) {
            $pagedata['activity'] = $activity;
        }
        $shop_id = $this->shop->id;
        $res = SecKillAppliesRegister::where(['seckill_ap_id' => $activityId, 'shop_id' => $shop_id, 'verify_status' => 2])->count();
        if ($res) {
            return $this->resFailed(701, '店铺已经提交申请!');
        }

        return $this->resSuccess($pagedata);
    }


    /**
     * 保存提交数据
     * @Author hfh_wind
     * @return mixed
     */
    public function RegisteredApplySave(RegisteredApplySaveRequest $request)
    {
        $seckill_ap_id = $request->seckill_ap_id;
        //判断重复提交
        $goods_info = $request->goods_info;
        $goods_info = is_array($goods_info) ? $goods_info : json_decode($goods_info, true);

        $apply_goods_ids = array_column($goods_info, 'goods_id');

        $apply_sku_ids = array_column($goods_info, 'sku_id');

        $shop_id = $this->shop->id;
        $register = SecKillAppliesRegister::where(['seckill_ap_id' => $seckill_ap_id, 'shop_id' => $shop_id])->count();
        if ($register) {
            return $this->resFailed(701, '该活动已经申请,请勿重复申请!');
        }

        //查询活动信息
        $secKillApplie = SecKillApplie::where('id', '=', $seckill_ap_id)->first()->toArray();
        $time = date('Y-m-d H:i:s', time());
        if ($secKillApplie['apply_begin_time'] > $time) {
            return $this->resFailed(701, '尚未开始报名!');
        }
        if ($secKillApplie['is_apply'] == 0) {
            return $this->resFailed(701, '活动报名已结束!');
        }

        foreach ($goods_info as $apply_sku_key => $apply_sku_value) {
            $goods_sku = GoodsSku::where(['id' => $apply_sku_value['sku_id'], 'shop_id' => $shop_id, 'goods_id' => $apply_sku_value['goods_id']])->select('goods_stock')->first();
            if (!$goods_sku) {
                return $this->resFailed(704, '添加的商品' . $apply_sku_value['goods_name'] . '不存在!');
            }
            if ($apply_sku_value['seckills_stock'] > $goods_sku['goods_stock']) {
                return $this->resFailed(704, '添加的商品' . $apply_sku_value['goods_name'] . '已经超过商品库存,请重新设置!');
            }
        }


        /*     //店铺参与商品数量限制判断
             if (count($goods_info) > $secKillApplie['enroll_limit']) {
                 return $this->resFailed(704, '添加的商品数量已经超过此活动限制!');
             }*/

        //是否有参加过还没结束的秒杀活动
        $sec_kill_activity = SecKillGood::where([
//            'seckill_ap_id' => $seckill_ap_id,
            'shop_id' => $shop_id,
        ])->where('verify_status', '<>', '1')->where('end_time', '>', $time)->get();

        if (count($sec_kill_activity) > 0) {
            $sec_kill_activity = $sec_kill_activity->toArray();
            $sec_check_ids = array_column($sec_kill_activity, 'sku_id', 'goods_name'); //以goods_name为key,sku_id为值取出数组列.
            foreach ($sec_check_ids as $key => $value) {
                if (in_array($value, $apply_sku_ids)) {
                    $msg = 'sku_id为' . $value . '--名称为' . $key . '商品已经报过名了，活动结束前或者商品申请驳回前不可以报名！';
                    return $this->resFailed([], $msg);
                }
            }
        }
        // 获取商家已经活动报名的商品信息
        $res = SpecialActivity::where('star_apply', '<=', $time)->where('end_time', '>=', $time)->get();
        $itemList = [];
        if (count($res) > 0) {
            $res = $res->toArray();
            $act_ids = array_column($res, 'id');
            if ($act_ids) {
                $itemList = SpecialActivityItem::where('shop_id', '=', $shop_id)->whereIn('act_id', $act_ids)->get();
            }
        }

        // 去重已经参加的活动商品
        $notItems = [];
        if (!empty($itemList)) {
            $itemList = $itemList->toArray();
            $notItems = array_column($itemList, 'goods_id');
        }
        foreach ($apply_goods_ids as $key) {

            if (in_array($key, $notItems)) {
                return $this->resFailed(704, '已有商品参加别的促销活动!请勿添加此活动');
            }

            //积分商品判断
            $check_point = PointActivityGoods::where('goods_id', $key)->first();
            if ($check_point) {
                return $this->resFailed(704, '正在参加积分活动请勿添加该商品!');
            }

            //团购活动判断
            $check_gro = new \ShopEM\Services\GroupService();

            $check_gro = $check_gro->actingGroup($key);

            if ($check_gro) {
                return $this->resFailed(704, '正在参加团购活动请勿添加该商品!');
            }

            //店家营销活动的判断
            $actService = new \ShopEM\Services\Marketing\Activity();
            if ($actService->actingAct($key)) {
                return $this->resFailed(704, '正在参加营销活动请勿添加该商品!');
            }
        }


        DB::beginTransaction();
        try {
            $applies_registers_arr['shop_id'] = $shop_id;
            $applies_registers_arr['seckill_ap_id'] = $seckill_ap_id;
            SecKillAppliesRegister::create($applies_registers_arr);

            // 活动报名保存商品
            foreach ($goods_info as $key => $value) {
                $insert = [
                    'seckill_ap_id'  => $seckill_ap_id,
                    'shop_id'        => $shop_id,
                    'title'          => $value['title'],
                    'goods_id'       => $value['goods_id'],
                    'sku_id'         => $value['sku_id'],
                    'goods_name'     => $value['goods_name'],
                    'goods_price'    => $value['goods_price'],
                    'seckill_price'  => $value['seckill_price'],
                    'seckills_stock' => $value['seckills_stock'],
                    'spec_sign'      => isset($value['spec_sign']) ? $value['spec_sign'] : '',
                    'stock_limit'    => isset($value['stock_limit']) ? $value['stock_limit'] : 0,
                    'goods_image'    => $value['goods_image'],
                    'start_time'     => $secKillApplie['start_time'],
                    'end_time'       => $secKillApplie['end_time'],
                    'sort'           => $value['sort'] ? $value['sort'] : 0,
                    'rewards'        => $value['rewards'] ?? 0, //返利金额
                    'profit_sharing' => $value['profit_sharing'] ?? 0,//分成金额
                    'gm_id'          => $this->GMID,
                ];

                //扣除秒杀库存
                $value['shop_id'] = $shop_id;
                (new \ShopEM\Services\SecKillService)->secKillStock($seckill_ap_id, $value, $value['seckills_stock'], 'dec', '报名秒杀活动，扣除秒杀库存');

                SecKillGood::create($insert);
            }
            DB::commit();
        } catch (\LogicException $e) {
            DB::rollBack();
            throw new \Exception('报名失败!' . $e->getMessage());
        }

        return $this->resSuccess([], '保存成功!');

    }


    /**
     * 编辑提交数据
     * @Author hfh_wind
     * @return mixed
     */
    public function RegisteredApplyEdit(RegisteredApplySaveRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414, '参数错误!');
        }

        $appliesRegister = SecKillAppliesRegister::where('id', $id)->first();
        if (empty($appliesRegister)) {
            return $this->resFailed(700, '申请数据不存在!');
        }

        $seckill_ap_id = $request->seckill_ap_id;
        //判断重复提交
        $goods_info = $request->goods_info;
        $goods_info = is_array($goods_info) ? $goods_info : json_decode($goods_info, true);

        foreach ($goods_info as $key => $value) {
            if ($value['goods_price'] <= 0) {
                $msg = '名称为' . $value['goods_name'] . '商品的秒杀价格不能小于0';
                return $this->resFailed(700, $msg);
            }
        }

        $apply_goods_ids = array_column($goods_info, 'goods_id');

        $apply_sku_ids = array_column($goods_info, 'sku_id');

        //查询活动信息
        $secKillApplie = SecKillApplie::where('id', '=', $seckill_ap_id)->first()->toArray();

        if ($secKillApplie['is_apply'] == 0) {
            return $this->resFailed(701, '活动报名已结束!');
        }

        $time = date('Y-m-d H:i:s', time());
        $shop_id = $this->shop->id;
        //是否有参加过还没结束的秒杀活动
        $sec_kill_activity = SecKillGood::where([
            // 'seckill_ap_id' => $seckill_ap_id,
            'shop_id' => $shop_id,
        ])->where('verify_status', '=', '2')->where('end_time', '>', $time)->get();

        if (count($sec_kill_activity) > 0) {
            $sec_kill_activity = $sec_kill_activity->toArray();
            $sec_check_ids = array_column($sec_kill_activity, 'sku_id', 'goods_name'); //以goods_name为key,sku_id为值取出数组列.
            foreach ($sec_check_ids as $key => $value) {
                if (in_array($value, $apply_sku_ids)) {
                    $msg = 'sku_id为' . $value . '--名称为' . $key . '商品已经报过名了，活动结束前不可以报名！';
                    return $this->resFailed([], $msg);
                }
            }
        }
        // 获取商家已经活动报名的商品信息
        $res = SpecialActivity::where('star_apply', '<=', $time)->where('end_time', '>=', $time)->get();
        $itemList = [];
        if (count($res) > 0) {
            $res = $res->toArray();
            $act_ids = array_column($res, 'id');
            if ($act_ids) {
                $itemList = SpecialActivityItem::where('shop_id', '=', $shop_id)->whereIn('act_id', $act_ids)->get();
            }
        }

        // 去重已经参加的活动商品
        $notItems = [];
        if (!empty($itemList)) {
            $itemList = $itemList->toArray();
            $notItems = array_column($itemList, 'goods_id');
        }
        foreach ($apply_goods_ids as $key) {
            if (in_array($key, $notItems)) {
                return $this->resFailed(704, '已有商品参加别的促销活动!请勿添加此活动');
            }

            //积分商品判断
            $check_point = PointActivityGoods::where('goods_id', $key)->first();
            if ($check_point) {
                return $this->resFailed(704, '正在参加积分活动请勿添加该商品!');
            }

            //团购活动判断
            $check_gro = new \ShopEM\Services\GroupService();

            $check_gro = $check_gro->actingGroup($key);

            if ($check_gro) {
                return $this->resFailed(704, '正在参加团购活动请勿添加该商品!');
            }

            //店家营销活动的判断
            $actService = new \ShopEM\Services\Marketing\Activity();
            if ($actService->actingAct($key)) {
                return $this->resFailed(704, '正在参加营销活动请勿添加该商品!');
            }
        }

        DB::beginTransaction();
        try {
            //先回退原先提交的数据
            $deleteFilter = ['seckill_ap_id' => $seckill_ap_id, 'shop_id' => $shop_id];
            $deleteSecKillGood = SecKillGood::where($deleteFilter)->where('verify_status', '!=', 2)->get();
            foreach ($deleteSecKillGood as $key => $value) {
                //重新报名回退
                (new \ShopEM\Services\SecKillService)->secKillStock($seckill_ap_id, $value, $value['seckills_stock'], 'inc', '重新报名秒杀活动，先回退原秒杀库存');
            }

            foreach ($goods_info as $apply_sku_key => $apply_sku_value) {
                if ($apply_sku_value['seckill_price'] <= 0) {
                    throw new \Exception('添加的商品' . $apply_sku_value['goods_name'] . '秒杀价格不能小于0!');
                }
                $goods_sku = GoodsSku::where(['id' => $apply_sku_value['sku_id'], 'shop_id' => $shop_id, 'goods_id' => $apply_sku_value['goods_id']])->select('goods_stock')->first();
                if (!$goods_sku) {
                    throw new \Exception('添加的商品' . $apply_sku_value['goods_name'] . '已经不存在!');
                }
                if ($apply_sku_value['seckills_stock'] > $goods_sku['goods_stock']) {
                    throw new \Exception('添加的商品' . $apply_sku_value['goods_name'] . '已经超过商品库存,请重新设置!');
                }
            }
            //重新待审核
            $applies_registers_arr['shop_id'] = $shop_id;
            $applies_registers_arr['seckill_ap_id'] = $seckill_ap_id;
            SecKillAppliesRegister::where($applies_registers_arr)->update(['verify_status' => 0]);


            //先删除原先提交的数据
            // SecKillGood::where(['seckill_ap_id' => $seckill_ap_id,'shop_id'=>$shop_id])->where('verify_status','!=',2)->delete();
            // SecKillGood::where(['seckill_ap_id' => $seckill_ap_id,'shop_id'=>$shop_id,'verify_status'=>1])->delete();
            SecKillGood::where($deleteFilter)->where('verify_status', '!=', 2)->delete();


            // 活动报名保存商品
            foreach ($goods_info as $key => $value) {

                $insert = [
                    'seckill_ap_id'  => $seckill_ap_id,
                    'shop_id'        => $shop_id,
                    'title'          => $value['title'],
                    'goods_id'       => $value['goods_id'],
                    'sku_id'         => $value['sku_id'],
                    'goods_name'     => $value['goods_name'],
                    'goods_price'    => $value['goods_price'],
                    'seckill_price'  => $value['seckill_price'],
                    'seckills_stock' => $value['seckills_stock'],
                    'spec_sign'      => isset($value['spec_sign']) ? $value['spec_sign'] : '',
                    'stock_limit'    => isset($value['stock_limit']) ? $value['stock_limit'] : 0,
                    'goods_image'    => $value['goods_image'],
                    'start_time'     => $secKillApplie['start_time'],
                    'end_time'       => $secKillApplie['end_time'],
                    'sort'           => $value['sort'] ? $value['sort'] : 0,
                    'rewards'        => $value['rewards'] ?? 0, //返利金额
                    'profit_sharing' => $value['profit_sharing'] ?? 0,//分成金额
                    'gm_id'          => $this->GMID,
                ];
                SecKillGood::create($insert);

                //扣除秒杀库存
                $value['shop_id'] = $shop_id;
                (new \ShopEM\Services\SecKillService)->secKillStock($seckill_ap_id, $value, $value['seckills_stock'], 'dec', '报名秒杀活动，扣除秒杀库存');
            }
            DB::commit();
        } catch (\LogicException $e) {
            DB::rollBack();
            throw new \Exception('报名失败!' . $e->getMessage());
        }

        return $this->resSuccess([], '保存成功!');

    }


}
