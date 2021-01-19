<?php
/**
 * @Filename    商家端优惠券控制器
 *
 * @Copyright   Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License     Licensed <http://www.shopem.cn/licenses/>
 * @authors     Mssjxzw (mssjxzw@163.com)
 * @date        2019-03-19 15:16:03
 * @version     V1.0
 */
namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Http\Requests\Seller\CouponRequest;
use ShopEM\Http\Requests\Seller\FindCouponRequest;
use ShopEM\Jobs\InvalidateCoupon;
use ShopEM\Repositories\CouponRepository;
use ShopEM\Repositories\CouponOffRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use ShopEM\Models\Coupon;
use ShopEM\Models\CouponStockOnline;
use ShopEM\Models\CouponStock;
use ShopEM\Models\CouponWriteOff;

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
        $data = $request->all();
        $data['shop_id'] = $this->shop->id;
        $repository = new CouponRepository();
        $lists = $repository->listItems($data,10);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        foreach ($lists as $key => $value) {
            $lists[$key]['get_time'] = [$value['get_star'],$value['get_end']];
            $lists[$key]['use_time'] = [$value['start_at'],$value['end_at']];
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
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
        $data = $request->only('id', 'shop_id', 'name', 'desc', 'issue_num','user_num', 'scenes', 'type',
            'discount', 'denominations', 'origin_condition', 'max_discount_fee', 'is_single', 'is_bind_goods',
            'channel', 'is_bind_shop', 'limit_shop', 'limit_goods', 'get_star', 'get_end', 'start_at', 'end_at',
            'reason', 'fullminus_act_enabled', 'discount_act_enabled', 'group_act_enabled', 'seckill_act_enabled', 'spread_goods_enabled');
        $data['shop_id'] = $this->shop->id;
        $data['gm_id'] = $this->GMID;
        /*$service = new \ShopEM\Services\Marketing\Coupon();
        $id_error = [];
        if (isset($data['limit_goods'])) {
            foreach ($data['limit_goods'] as $k => $v) {
                $check = $service->checkGoods($v['id']);
                if ($check['code']) {
                    $id_error[] = $v['goods_name'];
                }
            }
            if (count($id_error) > 0) {
                $id_error = implode(',',$id_error).'已有绑定优惠券';
                return $this->resFailed(414,$id_error);
            }
        }*/
        //优惠券的默认类型
        if (!isset($data['type']) || empty($data['type'])) {
            $data['type'] = 1;
        }
        //优惠券的默认场景
        if (!isset($data['scenes']) || empty($data['scenes'])) {
            $data['scenes'] = 1;
        }

        try {
            //检查保存的优惠券信息
            Coupon::checkSaveCoupon($data);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        if (isset($data['id']) && $data['id']) {
            $id = $data['id'];
            unset($data['id']);
            $coupon = Coupon::find($id);
            if (!$coupon) {
                return $this->resFailed(701,'没有此优惠券');
            }
            if ($coupon->status == 'SUCCESS') {
                return $this->resFailed(701,'优惠券已通过审核,无法编辑');
            }
            $now = time();
            $star = strtotime($coupon->start_at);
            $stop = strtotime($coupon->end_at);
            if ($now > $star && $now < $stop) {
                return $this->resFailed(701,'该优惠券已生效，不能更改');
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
            $data['status'] = 'WAIT';//把优惠券状态改为待审核
            $data['reason'] = '';//把优惠券驳回原因清空
            Coupon::where('id',$id)->update($data);
        }else{
            $data['is_distribute'] = 1;//创建优惠券默认是不派发
            $coupon = Coupon::create($data);
            // if ($data['scenes'] == 2) {
            //     $head = getRandStr(4);
            //     for ($i=0; $i < $data['issue_num']; $i++) {
            //         $store['bn'] = $this->getBn($head);
            //         $store['coupon_id'] = $coupon->id;
            //         $res = \ShopEM\Models\CouponStock::create($store);
            //     }
            // }
        }
        return $this->resSuccess();
    }

    /**
     * [getOffLineCouponStock 获取线下优惠券库存列表]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function getOffLineCouponStock(Request $request)
    {
        if (!$request->filled('coupon_id')) {
            return $this->resFailed(414,'参数不全');
        }
        $where = $request->only('coupon_id','bn','status');
        $size = $request->only('size');
        if (!$size || !is_numeric($size['size'])) {
            $size['size'] = 100;
        }
        $lists = \ShopEM\Models\CouponStock::where($where)->paginate($size['size']);
        return $this->resSuccess($lists);
    }

    /**
     * [getBn 获取线下优惠券唯一码]
     * @Author mssjxzw
     * @param  [type]  $head [description]
     * @return [type]        [description]
     */
    private function getBn($head)
    {
        $shop_id = $this->shop->id;
        $date = date('Ymd');
        $num = Cache::remember('coupon_num_'.$shop_id.'_'.$date, now()->addDay(1), function () {
            return 0;
        });
        $num = $num+1;
        cache(['coupon_num_'.$shop_id.'_'.$date => $num], now()->addDay(1));
        return $head.date('Y').$shop_id.$num.date('md');
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

        if ($detail->shop_id != $this->shop->id) {
            return $this->resFailed(700);
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
            return $this->resFailed(701,'没有此优惠券');
        }
        if ($coupon->shop_id != $this->shop->id) {
            return $this->resFailed(700);
        }
        $now = time();
        $star = strtotime($coupon->start_at);
        $stop = strtotime($coupon->end_at);
        if ($now > $star && $now < $stop) {
            if ($coupon->status == 'SUCCESS') {
                return $this->resFailed(701,'该优惠券已生效，不能删除');
            }
        }
        try {
            Coupon::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();

    }

    /**
     * [send 定向发放优惠券]
     * @Author mssjxzw
     * @param  Request $request [请求数据对象]
     * @return [type]           [description]
     */
    public function send(Request $request)
    {
        $data = $request->only('coupon_id','user_id');
        if (!array_key_exists('user_id', $data) || !array_key_exists('coupon_id', $data)) {
            return $this->resFailed(414,'参数不全');
        }
        $coupon = Coupon::find($data['coupon_id']);
        $data['operator'] = 2;
        $user_id_arr = explode(',', $data['user_id']);
        DB::beginTransaction();
        try {
            foreach ($user_id_arr as $key => $value) {
                $data['user_id'] = $value;
                $res = \ShopEM\Models\CouponStockOnline::create($data);
                InvalidateCoupon::dispatch($res->id)->delay(now()->parse($coupon->end_at));
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * [writeOff 核销]
     * @param string $value [description]
     */
    public function writeOff(FindCouponRequest $request)
    {
        $data = $request->only('bn','user_mobile','trade_no','remark','voucher');
        if (!isset($data['trade_no']) || empty($data['trade_no'])) {
            return $this->resFailed(414,'请输入小票号');
        }
        $data['shop_id'] = $this->shop->id;
        DB::beginTransaction();
        try {
            $coupon_id = CouponStock::where('bn',$data['bn'])->where('status',1)->value('coupon_id');
            if (empty($coupon_id)) {
                throw new \Exception("优惠券已过期");
            }

            $coupon = Coupon::where(['id'=>$coupon_id,'gm_id'=>$this->GMID])->first();
            if (empty($coupon)) {
                throw new \Exception("优惠券已删除/不存在");
            }
            if ($coupon->shop_id == 0)
            {
                $serviceCoupon = new \ShopEM\Services\Marketing\Coupon;
                $checkShop = $serviceCoupon->checkShop($coupon_id,$data['shop_id']);
                if ($checkShop['code'] > 0) {
                    throw new \Exception("该商家不适用此优惠券");
                }
            }else{
                if ($coupon->shop_id != $data['shop_id']) {
                    throw new \Exception("该商家不适用此优惠券");
                }
            }
            $user_id = \ShopEM\Models\UserAccount::where('mobile',$data['user_mobile'])->value('id');

            $data['status'] = $filter['status'] = 1;
            $data['user_id'] = $filter['user_id'] = $user_id??0;
            $data['coupon_id'] = $filter['coupon_id'] = $coupon_id;
            $data['source_shop_id'] = $coupon->shop_id;
            $data['gm_id'] = $this->GMID;

            //优惠券使用
            $userCoupon = CouponStockOnline::where($filter)->first();
            if (!$userCoupon) {
                throw new \Exception("优惠券已删除/不存在");
            }
            CouponStockOnline::succUseCoupon($userCoupon);

            //记录
            CouponWriteOff::create($data);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }
        return $this->resSuccess();

        // $stock = CouponStock::where('bn')
    }

    /**
     * [takeFindCoupon 输入方式查询]
     * @param  FindCouponRequest $request [description]
     * @return [type]                     [description]
     */
    public function takeFindCoupon(FindCouponRequest $request)
    {
        $data = $request->only('bn','user_mobile');
        try {
            $detail = $this->couponOffDetail($data['bn']);
            $user_id = \ShopEM\Models\UserAccount::where('mobile',$data['user_mobile'])->value('id');
            $count = CouponStockOnline::where('coupon_code',$detail['coupon_code'])->where('user_id',$user_id)->count();
            if ($count <= 0 ) {
                throw new \Exception("客户手机号有误");
            }
            $detail['user_mobile'] = $data['user_mobile'];
            return $this->resSuccess($detail);

        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
    }
    /**
     * [qrcodeFindCoupon 二维码方式查询]
     * @param  FindCouponRequest $request [description]
     * @return [type]                     [description]
     */
    public function qrcodeFindCoupon(Request $request)
    {
        $data = $request->only('bn');
        if (!isset($data['bn'])) {
            return $this->resFailed(414,'参数不全');
        }
        try {
            $detail = $this->couponOffDetail($data['bn']);
            $user_id = CouponStockOnline::where('coupon_code',$detail['coupon_code'])->value('user_id');
            $user_mobile = \ShopEM\Models\UserAccount::where('id',$user_id)->value('mobile');

            $detail['user_mobile'] = $user_mobile;
            return $this->resSuccess($detail);

        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
    }

    /**
     * [couponOffDetail 线下优惠券详情]
     * @param  [type] $bn [核销码]
     * @return [type]     [description]
     */
    public function couponOffDetail($bn)
    {
        $shop_id = $this->shop->id;
        $date = date('Y-m-d H:i:s');
        $model = new CouponStock;
        $detail = $model->leftJoin('coupons','coupons.id','=','coupon_stocks.coupon_id')->select(
            'coupon_stocks.status as stock_status','coupon_stocks.coupon_id','coupon_stocks.bn','coupon_stocks.coupon_code'
            ,'coupons.*'
        )->where('coupons.scenes','!=','1')
        // ->where('coupons.start_at','<=',$date)
        // ->where('coupons.end_at','>=',$date)
        ->where('coupon_stocks.bn','=',$bn)
        ->where('coupon_stocks.status','=','1')
        ->first();

        if (empty($detail)) {
            throw new \Exception("优惠券已失效");
        }
        if($detail->start_at > $date){
            throw new \Exception("优惠券活动未开始");
        }
        if ($detail->end_at < $date) {
            throw new \Exception("优惠券活动已结束");
        }
        if ($detail->shop_id == 0)
        {
            $serviceCoupon = new \ShopEM\Services\Marketing\Coupon;
            $checkShop = $serviceCoupon->checkShop($detail->id,$shop_id);
            if ($checkShop['code'] > 0) {
                throw new \Exception("该商家不适用此优惠券");
            }
        }else{
            if ($detail->shop_id != $shop_id) {
                throw new \Exception("该商家不适用此优惠券");
            }
        }

        return $detail->toArray();
    }

    /**
     * [couponOffList 线下优惠券列表]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function couponOffList(Request $request)
    {
        $data = $request->all();
        $data['shop_id'] = $this->shop->id;
        $repository = new CouponOffRepository();
        $lists = $repository->listItems($data);
        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
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
             DB::commit();  //提交
        } catch (\Exception $e) {
            DB::rollback();  //回滚
            return $this->resFailed(702, $e->getMessage());
        }
        return $this->resSuccess('修改成功');
    }
}
