<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class CouponStockOnline extends Model
{
    protected $guarded = [];

    protected $appends = ['bn_info'];


    public function getBnInfoAttribute()
    {
        $info = [];
        if ($this->scenes > 1) {
            $filter = [
                'coupon_code' => $this->coupon_code,
                'coupon_id'   => $this->coupon_id,
            ];
            $info = CouponStock::where($filter)->select('bn','status')->first();
        }
        return $info;
    }
    /**
     * [succUseCoupon 更新成功使用状态]
     * @param  [type] $coupon     [优惠券信息]
     * @param  string $payment_id [支付单]
     * @return [type]             [description]
     */
    public static function succUseCoupon($coupon,$payment_id='')
    {
        $updateOnline['status'] = 2;
        if (!empty($payment_id)) {
            $updateOnline['payment_id'] = $payment_id;
        }
    	$result = CouponStockOnline::where(['id' => $coupon->id])->update($updateOnline);
        if ($result && $coupon->scenes > 1) {
            $stock = CouponStock::where('coupon_code',$coupon->coupon_code)->first();
            if($stock){
                $stock->status = $updateOnline['status'];
                $stock->save();
            }
        }
    }

    /**
     * [reUseCoupon 恢复优惠券状态]
     * @param [type] $coupon    [优惠券信息]
     */
    public static function reUseCoupon($coupon)
    {
        $updateOnline['status'] = 1;
        $updateOnline['tid'] = 0;
        $result = CouponStockOnline::where(['id' => $coupon->id])->update($updateOnline);
        if ($result && $coupon->scenes > 1) {
            $stock = CouponStock::where('coupon_code',$coupon->coupon_code)->first();
            if($stock){
                $stock->status = $updateOnline['status'];
                $stock->save();
            }
        }
    }


    //处理到期的券
    public static function ProcessingExpiration($user_id)
    {
        $service = new \ShopEM\Services\Marketing\Coupon;
        $model = new CouponStockOnline();
        $data = $model->leftJoin('coupons', 'coupon_stock_onlines.coupon_id', '=', 'coupons.id')
                ->select('coupons.end_at','coupons.status as coupons_status', 'coupon_stock_onlines.*')
                ->where('coupons.end_at','<',nowTimeString())
                ->where('coupon_stock_onlines.status','=',1)
                ->where('coupon_stock_onlines.user_id','=',$user_id)
                ->get()->toArray();
        foreach ($data as $key => $value) 
        {
            if ($value['end_at'] < nowTimeString()) {
                $service->invalidateCoupon($value['id']);
            }
        }
    }
}
