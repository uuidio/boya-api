<?php
/**
 * @Filename        CouponController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\CouponRequest;
use ShopEM\Http\Requests\Platform\PushCouponRequest;
use ShopEM\Jobs\InvalidateCoupon;
use ShopEM\Repositories\CouponRepository;
use ShopEM\Repositories\CouponOffRepository;
use ShopEM\Repositories\CouponUseRepository;
use Illuminate\Http\Request;
use ShopEM\Models\Coupon;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CouponController extends BaseController
{
    /**
     * 优惠券列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param CouponRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request)
    {
        $repository = new CouponRepository();
        $data = $request->all();
        $data['gm_id'] = $this->GMID;
        $lists = $repository->listItems($data, 10);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        foreach ($lists as $key => $value) {
            $lists[$key]['get_time'] = [$value['get_star'], $value['get_end']];
            $lists[$key]['use_time'] = [$value['start_at'], $value['end_at']];
        }
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->PlatformListShowFields(),
        ]);
    }

    /**
     * 保存优惠券
     *
     * @Author moocde <mo@mocode.cn>
     * @param CouponRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveData(CouponRequest $request)
    {
        $data = $request->only('id', 'shop_id', 'name', 'desc', 'issue_num', 'user_num', 'scenes', 'type', 'discount',
            'denominations',
            'origin_condition', 'max_discount_fee', 'is_single', 'is_bind_goods', 'is_bind_shop', 'limit_shop',
            'limit_goods', 'get_star', 'fullminus_act_enabled', 'discount_act_enabled', 'group_act_enabled', 'seckill_act_enabled', 'spread_goods_enabled',
            'get_end', 'start_at', 'end_at', 'reason', 'channel', 'limit_classes', 'is_bind_classes', 'is_hand_push');
        try {
            $data['shop_id'] = 0;
            $data['status'] = 'SUCCESS';//平台创建的优惠券不需要审核
            //检查保存的优惠券信息
            Coupon::checkSaveCoupon($data);

            if (isset($data['id']) && $data['id']) {
                $id = $data['id'];
                unset($data['id']);
                $coupon = Coupon::find($id);
                if (!$coupon) {
                    return $this->resFailed(701, '没有此优惠券');
                }
                if ($coupon->shop_id != 0) {
                    return $this->resFailed(701, '无法编辑商家券');
                }
                $now = time();
                $star = strtotime($coupon->start_at);
                $stop = strtotime($coupon->end_at);
                if ($now > $star && $now < $stop) {
                    return $this->resFailed(701, '该优惠券已生效，不能更改');
                }
                if (isset($data['limit_shop']) && $data['limit_shop']) {
                    $coupon->limit_shop = $data['limit_shop'];
                    $coupon->save();
                    unset($data['limit_shop']);
                }
                if (isset($data['limit_goods']) && $data['limit_goods']) {
                    $coupon->limit_goods = $data['limit_goods'];
                    $coupon->save();
                    unset($data['limit_goods']);
                }
                //绑定分类
                if (isset($data['limit_classes']) && $data['limit_classes']) {
                    $coupon->limit_classes = $data['limit_classes'];
                    $coupon->save();
                    unset($data['limit_classes']);
                }

                Coupon::where('id', $id)->update($data);

                $msg_text="更新优惠劵-".$coupon['id']."-".$coupon['name'];
            } else {
                $msg_text="创建优惠劵".$data['name'];
                $data['gm_id'] = $this->GMID;
                $data['is_distribute'] = 1;//创建优惠券默认是不派发

                Coupon::create($data);
            }

        } catch (\Exception $e) {
            //日志
//            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess();
    }


    /**
     * 优惠券详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = Coupon::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }
        if ($detail->gm_id != $this->GMID)
        {
            return $this->resFailed(700);
        }
        $detail = $detail->toArray();

        if ($detail['limit_shop']) {
            $ids = explode(',', $detail['limit_shop']);
            $model = new \ShopEM\Models\Shop();
            $detail['limit_shop'] = $model->whereIn('id', $ids)->get()->keyBy('id');
        }

        if ($detail['limit_classes']) {
            $ids = explode(',', $detail['limit_classes']);
            $model = new \ShopEM\Models\GoodsClass();
            $detail['limit_classes'] = $model->whereIn('id', $ids)->get()->keyBy('id');
        }
        if($detail['desc'] == null || $detail == ''){
            $detail['desc'] = '暂无优惠券详情';
        }
        return $this->resSuccess($detail);
    }

    /**
     * 删除优惠券
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return $this->resFailed(701, '没有此优惠券');
        }
        $now = time();
        $star = strtotime($coupon->start_at);
        $stop = strtotime($coupon->end_at);
        if ($now > $star && $now < $stop) {
            return $this->resFailed(701, '该优惠券已生效，不能删除');
        }
        $msg_text="删除优惠劵-".$coupon['id']."-".$coupon['name'];
        try {
            Coupon::destroy($id);
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess();
    }

    /**
     * 推送优惠券
     * @Author djw
     * @param CouponRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pushCoupon(PushCouponRequest $request)
    {
        $user = \ShopEM\Models\UserAccount::where('mobile', $request->mobile)->first();
        if (!$user) {
            return $this->resFailed(701, '用户不存在');
        }
        $data = [
            'coupon_id' => $request->coupon_id,
            'user_id'   => $user['id'],
        ];

        $coupon = Coupon::where('id', $data['coupon_id'])->where('is_hand_push', 1)->first();
        if (!$coupon) {
            return $this->resFailed(414, '无效优惠券');
        }
        if ($coupon->issue_num <= $coupon->rec_num) {
            return $this->resFailed(414, '优惠券库存已空');
        }
        if ($coupon->is_distribute <= 0) {
            return $this->resFailed(414, '优惠券已下架');
        }
        if (!isInTime($coupon->start_at, $coupon->end_at)) {
            return $this->resFailed(414, '不在可使用时间内');
        }
        $userCoupon = \ShopEM\Models\CouponStockOnline::where($data)->get();
        if (count($userCoupon) >= $coupon->user_num) {
            return $this->resFailed(414, '每个会员只能领取' . $coupon->user_num . '张');
        }
        $msg_text="推送优惠劵-".$coupon['id']."-".$coupon['name']."-给会员-".$user['mobile'];
        try {

            if ($coupon->issue_num > 0) {
                $coupon->rec_num = $coupon->rec_num + 1;
                $coupon->save();
            }
            $data['coupon_code'] = $this->getCode($user['id']);
            $data['operator'] = 2;
            $data['gm_id'] = $this->GMID;
            $data['scenes'] = $coupon->scenes;
            $res = \ShopEM\Models\CouponStockOnline::create($data);
            InvalidateCoupon::dispatch($res->id)->delay(now()->parse($coupon->end_at));
            //改版，线上线下可通用 nlx
            if (in_array($data['scenes'], [2,3])) {
                $head = getRandStr(4);
                $store['bn'] = $this->getBn($head,$coupon->shop_id,$coupon->gm_id);
                $store['coupon_id'] = $coupon->id;
                $store['coupon_code'] = $data['coupon_code'];
                $store['status'] = 1;
                \ShopEM\Models\CouponStock::create($store);
            }
            // if ($request->filled('bn')) {
            //     $stock = \ShopEM\Models\CouponStock::where('bn', $request->bn)->first();
            //     if ($stock) {
            //         $stock->status = 2;
            //         $stock->save();
            //     }
            // }
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(700, '推送失败');
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess('推送成功');
    }

    /**
     * [getBn 获取线下优惠券唯一码]
     * @Author mssjxzw
     * @param  [type]  $head [description]
     * @return [type]        [description]
     */
    private function getBn($head,$shop_id,$gm_id)
    {
        $date = date('Ymd');
        $cache_key = 'coupon_num_'.$shop_id.$gm_id.'_'.$date;
        $cache_day = Carbon::now()->addDay(1);
        $num = Cache::remember($cache_key, $cache_day, function () {
            return 0;
        });
        $num = $num+1;
        Cache::put($cache_key, $num, $cache_day);

        $str = $head.date('Y').$shop_id.$gm_id.$num.date('md');
        return strtoupper($str);
    }
    /**
     * 审核优惠券
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        $data = $request->only('result', 'coupon_id', 'reject_reason');
        if (!array_key_exists('result', $data) || !array_key_exists('coupon_id', $data)) {
            return $this->resFailed(414, '参数不全');
        }
        if (!in_array($data['result'], ['SUCCESS', 'FAILS'])) {
            return $this->resFailed(414, '参数错误');
        }
        $coupon_id = $data['coupon_id'];
        $coupon = Coupon::where('id', $coupon_id)->where('status', 'WAIT')->select('id','name')->first();
        if (!$coupon) {
            return $this->resFailed(414, '优惠券不存在或已被审核');
        }

        $params['status'] = $data['result'];
        //驳回的时候才需要驳回原因
        if ($params['status'] == 'FAILS') {
            $params['reason'] = isset($data['reject_reason']) ? $data['reject_reason'] : null;
        }
        $msg_text="审核优惠劵-".$coupon['id']."-".$coupon['name'];
        try {
            $coupon->update($params);

        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(702, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess('审核成功');
    }

    /**
     * [batchCheck 批量审核]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function batchCheck(Request $request)
    {
        $data = $request->only('result', 'coupon_ids', 'reject_reason');
        if (!array_key_exists('result', $data) || !array_key_exists('coupon_ids', $data)) {
            return $this->resFailed(414, '参数不全');
        }
        if (!in_array($data['result'], ['SUCCESS', 'FAILS'])) {
            return $this->resFailed(414, '参数错误');
        }
        $coupon_ids = $data['coupon_ids'];
        foreach ($coupon_ids as $key => $value)
        {
            $coupon = Coupon::where('id', $value)->where('status', 'WAIT')->count();
            if (!$coupon) {
                return $this->resFailed(414, 'ID:'.$value.'-优惠券不存在或已被审核');
            }
        }

        $params['status'] = $data['result'];
        //驳回的时候才需要驳回原因
        if ($params['status'] == 'FAILS') {
            $params['reason'] = isset($data['reject_reason']) ? $data['reject_reason'] : null;
        }

        $msg_text="批量审核优惠劵ids-".implode(',',$coupon_ids);
        try {

            Coupon::whereIn('id',$coupon_ids)->where('status', 'WAIT')->update($params);

        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(702, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess('批量审核成功');
    }

    /**
     * [getCode 获取优惠券唯一码]
     * @Author mssjxzw
     * @param  [type]  $user_id [des cription]
     * @return [type]           [description]
     */
    private function getCode($user_id)
    {
        $u = 'U' . $user_id;
        $length = strlen($u);
        $limit = 5;
        if ($length < $limit) {
            $u .= getRandStr($limit - $length);
            $length = $limit;
        }
        $res[] = getRandStr($length);
        $res[] = $u;
        $res[] = getRandStr($length - 4) . date('is');
        $res[] = getRandStr($length);
        return implode('-', $res);
    }

    /**
     * 重新编辑优惠券名
     *
     * @Author huiho <429294135@qq.com>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function editName(Request $request)
    {
        $data = $request->only('id', 'shop_id', 'name');

        //检验数据
        if ($data['id'] <= 0) {
            return $this->resFailed(414,'优惠卷ID不能为空');
        }

        if ($data['shop_id'] <= 0) {
            return $this->resFailed(414,'店铺ID不能为空');
        }

        if(Coupon::where('id', $data['id'])->value('shop_id')!=$data['shop_id'])
        {
            $this->adminlog('修改失败', 0);
            return $this->resFailed(414,'修改失败,信息不匹配');
        }

        //更改数据
        $Coupon = new Coupon();
        $vail = $Coupon->editName($data);
        if($vail){
            $this->adminlog('修改成功', 1);
            return $this->resSuccess('修改成功');
        }
        $this->adminlog('修改失败', 0);
        return $this->resFailed(414,'修改失败');
    }


    public function couponOffList(Request $request)
    {
        $data = $request->all();
        $data['gm_id'] = $this->GMID;
        $repository = new CouponOffRepository();
        $lists = $repository->listItems($data);
        if (empty($lists)) {
            return $this->resFailed(700);
        }
        $lists = $lists->toArray();
        foreach ($lists['data'] as $key => $value) {
            if ($value['source_shop_id'] > 0) {
                $lists['data'][$key]['source_type'] = '商家：'.$value['source_name'];
            }

        }
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     *  抽奖优惠卷列表
     *
     * @Author Huiho <mo@mocode.cn>
     * @param CouponRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function luckDrawCoupon(Request $request)
    {
        $repository = new CouponRepository();
        $data = $request->all();
        $data['gm_id'] = $this->GMID;
        $data['valid_at'] = date('Y-m-d H:i:s', time());
        $data['is_hand_push'] = 1;
        $data['is_distribute'] = 1;
        $data['search_type'] = 'luckDraw';
        $data['shop_id'] = 0;

        $lists = $repository->luckDrawItems($data, 10);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        foreach ($lists as $key => $value) {
            $lists[$key]['get_time'] = [$value['get_star'], $value['get_end']];
            $lists[$key]['use_time'] = [$value['start_at'], $value['end_at']];
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->PlatformListShowFields(),
        ]);
    }

    /**
     *  上架或下架优惠券
     *
     * @Author swl 2020-4-26
     * @param is_distribute:0下架1上架
     * @return \Illuminate\Http\JsonResponse
     */
    public function distributeConpou(Request $request){
        $data = $request->all();

        if (!isset($data['id']) || empty($data['id'])) {
            return $this->resFailed(414,'优惠卷ID不能为空');
        }

        $is_distribute = $data['is_distribute']??1;
        try {
            Coupon::where('id',$data['id'])->update(['is_distribute'=>$is_distribute]);

        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }
        return $this->resSuccess('修改成功');
    }

    /**
     *  修改库存
     *
     * @Author swl 2020-4-26
     * @param type:1增加 2减少
     * @return \Illuminate\Http\JsonResponse
     */

    public function updateStorage(Request $request){
        $data = $request->all();

        if (!isset($data['id']) || empty($data['id'])) {
            return $this->resFailed(414,'优惠卷ID不能为空');
        }
        if(!isset($data['num'])){
            return $this->resFailed(414,'参数错误');
        }
        // 1为增加，2为减少
        $type = $data['type']??1;
        $coupon = Coupon::find($data['id']);
        if($type == 1){
            $num = $coupon['issue_num'] + $data['num'];
        }else{
            $num = $coupon['issue_num'] - $data['num'];
            if($num<0){
                 return $this->resFailed(414,'优惠券数量不能小于0');
            }
        }

        DB::beginTransaction(); //开启事务
        try {
             $coupon->update(['issue_num'=>$num]);
             $params = [
                'num' => $data['num'],
                'pre_num' => $num,//修改后的库存
                'coupon_id' => $data['id'],
                'type'=>$type,
                'gm_id'=>$this->GMID,
                'admin_user_id'   => $this->platform->id,
                'admin_user_name' => $this->platform->username,
             ];
             $logMol =  new \ShopEM\Models\CouponLog;
             $logMol->create($params);
             DB::commit();  //提交
        } catch (\Exception $e) {
            DB::rollback();  //回滚
            return $this->resFailed(702, $e->getMessage());
        }
        return $this->resSuccess('修改成功');
    }

    /**
     *  优惠卷兑换列表
     *
     * @Author Huiho
     * @param CouponUseRepository $couponUseRepository
     * @return \Illuminate\Http\JsonResponse
     */

    public function couponUseList(Request $request , CouponUseRepository  $couponUseRepository )
    {
        $input_data = $request->all();
        $input_data['gm_id'] = $this->GMID;
        $input_data['per_page'] = $request->per_page ? $request->per_page : 15;
        $input_data['total_data_status'] = true;
        $lists = $couponUseRepository->listItems($input_data);

        if (empty($lists))
        {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $couponUseRepository->listShowFields(),
            'total_fee_data' => $lists['total_fee_data'],
        ]);
    }

    /**
     *  优惠卷兑换导出
     *
     * @Author Huiho
     * @param CouponUseRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */

    public function couponUseExport(Request $request , CouponUseRepository  $couponUseRepository )
    {

        $input_data = $request->all();
        $data = $input_data['exportForm'];
        $data['gm_id'] = $this->GMID;

        $lists = $couponUseRepository->listItems($data ,1);

        if (empty($lists))
        {
            return $this->resFailed(700);
        }
        $title = $couponUseRepository->listShowFields();

        $return['trade']['tHeader']= array_column($title,'title');        //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段
        $return['trade']['list']= $lists;                                       //数据

        return $this->resSuccess($return);

    }


}
