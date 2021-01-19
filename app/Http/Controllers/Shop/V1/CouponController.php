<?php
/**
 * @Filename    前台优惠券控制器
 *
 * @Copyright   Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License     Licensed <http://www.shopem.cn/licenses/>
 * @authors     Mssjxzw (mssjxzw@163.com)
 * @date        2019-03-19 15:16:03
 * @version     V1.0
 */
namespace ShopEM\Http\Controllers\Shop\V1;

use ShopEM\Models\Coupon;
use ShopEM\Models\CouponStock;
use ShopEM\Models\CouponStockOnline;
use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Services\Marketing\Coupon as service;
use ShopEM\Jobs\InvalidateCoupon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CouponController extends BaseController
{
    /**
     * [lists 优惠券列表(未登录)]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function lists(Request $request)
    {
      $service = new service();
      $input = $request->all();

      if (!isset($input['gm_id'])) 
      {
        $input['gm_id'] = $this->GMID;
      }
      $data = $service->lists($input);
      return $this->resSuccess($data);
    }

    /**
     * [lists 优惠券列表(已登录)]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function listsLogin(Request $request)
    {
      $service = new service();
      $input = $request->all();

      if (!isset($input['gm_id'])) 
      {
        $input['gm_id'] = $this->GMID;
      }
      $data = $service->lists($input);
      return $this->resSuccess($data);
    }

    /**
     * [userList 会员优惠券列表]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function userList(Request $request)
    {
        $input = $request->all();
        $input['user_id'] = $this->user->id;
        // if (!isset($input['gm_id'])) 
        // {
        //     $input['gm_id'] = $this->GMID;
        // }
        $service = new service();
        $data = $service->getUserCouponList($input);
        return $this->resSuccess($data);
    }

    /**
     * [receive 领取优惠券]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function receive(Request $request)
    {
        if ($request->filled('coupon_id')) {
            $data['coupon_id'] = $request->coupon_id;
        }else{
            return $this->resFailed(414,'参数不全');
        }
        $data['user_id'] = $this->user->id;
        DB::beginTransaction();
        try {
            $coupon = Coupon::where('id', $data['coupon_id'])->where('is_hand_push', 0)->first();
            if (!$coupon) {
                return $this->resFailed(414,'无效优惠券');
            }
            if ($coupon->issue_num <= $coupon->rec_num) {
                return $this->resFailed(414,'优惠券库存已空');
            }
            if ($coupon->is_distribute <= 0) {
                return $this->resFailed(414, '优惠券已下架');
            }
            if (!isInTime($coupon->get_star,$coupon->get_end)) {
                return $this->resFailed(414,'不在领取时间');
            }
            $userCoupon = \ShopEM\Models\CouponStockOnline::where($data)->get();
            if (count($userCoupon) >= $coupon->user_num) {
                return $this->resFailed(500,'每个会员只能领取'.$coupon->user_num.'张');
            }
            if ($coupon->issue_num > 0) {
                $coupon->rec_num = $coupon->rec_num + 1;
                $coupon->save();
            }
            $data['coupon_code'] = $this->getCode($this->user->id);
            $data['coupon_fee'] = $coupon->denominations;
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
            //     $stock = \ShopEM\Models\CouponStock::where('bn',$request->bn)->first();
            //     if ($stock) {
            //         $stock->status = 2;
            //         $stock->save();
            //     }
            // }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->resFailed(700,'领取失败');
        }

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

        return $this->resSuccess($detail);
    }


    /**
     * [userCouponDetail 已领优惠券详情]
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function userCouponDetail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $detail = CouponStockOnline::find($id);
        $detail->coupon_info = Coupon::find($detail->coupon_id);
        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * [offLineCoupon 线下优惠券信息（领取页）]
     * @Author mssjxzw
     * @param  string  $bn [description]
     * @return [type]      [description]
     */
    public function offLineCoupon($bn='')
    {
        if (!$bn) {
            return $this->resFailed(414,'参数不全');
        }
        $stock = \ShopEM\Models\CouponStock::where('bn',$bn)->first();
        if (!$stock) {
            return $this->resFailed(414,'二维码有误');
        }

        $coupon = Coupon::find($stock->coupon_id);
        switch ($stock->status) {
            case 1:
                $coupon['qrcode_statue'] = 1;
                $coupon['msg'] = '未领取';
                break;
            case 3:
                $coupon['qrcode_statue'] = 3;
                $coupon['msg'] = '已过期';
                break;
            default:
                $coupon['qrcode_statue'] = 2;
                $coupon['msg'] = '已领取';
                break;
        }
        return $this->resSuccess($coupon);
    }

    /**
     * [getCode 获取优惠券唯一码]
     * @Author mssjxzw
     * @param  [type]  $user_id [description]
     * @return [type]           [description]
     */
    private function getCode($user_id)
    {
        $u = 'U'.$user_id;
        $length = strlen($u);
        $limit = 5;
        if ($length < $limit) {
            $u .= getRandStr($limit-$length);
            $length = $limit;
        }
        $res[] = getRandStr($length);
        $res[] = $u;
        $res[] = getRandStr($length-4).date('is');
        $res[] = getRandStr($length);
        return implode('-',$res);
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
     * 分类tab
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function classTab()
    {
        $service = new service();
        $data = $service->getClassTab($this->GMID);
        return $this->resSuccess($data);
    }
}
