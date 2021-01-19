<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CouponUse extends Model
{
    protected $table = 'coupon_stock_onlines';

    protected $guarded = [];

    protected $appends = ['user_accounts_info' , 'coupons_info' , 'coupon_stocks_info' ,'scenes_text','status_text','used_at_text','payed_text'];
    
    //追加会员表信息
    public function getUserAccountsInfoAttribute()
    {
        $accounts = DB::table('user_accounts')->where('id',$this->user_id)->select('mobile')->first();
        $userInfos = DB::table('wx_userinfos')->where('user_id',$this->user_id)->select('real_name')->first();

        if(!$accounts)
        {
            $user['mobile'] = '--';
        }
        else
        {
            $user['mobile'] = $accounts->mobile ? : '--';

        }
        if(!$userInfos)
        {
            $user['nickname'] = '--';
        }
        else
        {
            $user['nickname'] = $userInfos->real_name ? : '--';

        }
        return $user;
    }

    //追加优惠卷信息
    public function getCouponsInfoAttribute()
    {
        $coupons_info = DB::table('coupons')->where('id',$this->coupon_id)->where('gm_id',$this->gm_id)->select('shop_id' , 'name' , 'is_hand_push')->first();


        if(!$coupons_info)
        {
            $coupons['is_hand_push'] = '--';
            $coupons['source_type'] = '--';
            $coupons['coupon_name'] = '--';
        }
        else
        {
            $coupons['is_hand_push'] = $this->is_hand_push ? '是' : '否';

            if(!isset($coupons_info->shop_id) || $coupons_info->shop_id == 0)
            {
                $coupons['source_type'] = '平台';
            }
            else
            {
                $shop_name = DB::table('shops')->where('id' , $coupons_info->shop_id)->where('gm_id',$this->gm_id)->value('shop_name');
                $shop_name = $shop_name??'--';
                $coupons['source_type'] = '商家：'.$shop_name;
            }
            $coupons['coupon_name'] = $coupons_info->name;

        }


        return $coupons;
    }

    //追加线下核销信息
    public function getCouponStocksInfoAttribute()
    {
        $coupon_bn = DB::table('coupon_stocks')->where('coupon_code',$this->coupon_code)->value('bn');

        $write_offs = DB::table('coupon_write_offs')->where('bn',$coupon_bn)->where('gm_id',$this->gm_id)->select('bn','created_at','remark','trade_no','voucher')->first();

        if(!$write_offs)
        {
            $stocks_info['bn'] = '--';
            $stocks_info['trade_no'] = '--';
            $stocks_info['remark'] = '--';
            //$stocks_info['write_off_at'] =  '--';
            $stocks_info['voucher'] =  '--';
            if(isset($this->payment_id)&&!empty($this->payment_id))
            {
                $tid =  DB::table('trade_paybills')->where('payment_id','=',$this->payment_id)->value('tid');
                $shop_id =  DB::table('trades')->where('tid', '=', $tid)->value('shop_id');
                $shop_name =  DB::table('shops')->where('id', $shop_id)->value('shop_name');
                $stocks_info['shop_name'] = $shop_name;
                return $stocks_info;

            }
            elseif(isset($this->tid)&&!empty($this->tid))
            {

                $shop_id =  DB::table('trades')->where('tid', '=', $this->tid)->value('shop_id');
                $shop_name =  DB::table('shops')->where('id', $shop_id)->value('shop_name');
                $stocks_info['shop_name'] = $shop_name ;
            }
            else
            {
                $shop_name = '--';
            }
            $stocks_info['shop_name'] = $shop_name;
        }
        else
        {
            $shop_name =  DB::table('shops')->where('id',$this->user_id)->value('shop_name');
            $stocks_info['bn'] = $write_offs->bn ?? '--';
            $stocks_info['trade_no'] = $write_offs->trade_no ?? '--';
            $stocks_info['remark'] = $write_offs->remark ?? '--';
            //$stocks_info['write_off_at'] = $write_offs->created_at ?? '--';
            $stocks_info['voucher'] = $write_offs->voucher ?? '--';
            $stocks_info['shop_name'] = $shop_name ?? '--';
        }

        return $stocks_info;
    }

    //转化渠道
    public function getScenesTextAttribute()
    {
        $scenesMap = [
            1 => '线上',    // 线上
            2 => '线下',    // 线下
            3 => '全渠道',    // 全渠道
        ];

        return isset($scenesMap[$this->scenes]) ? $scenesMap[$this->scenes] : '--';
    }

    //转化状态
    public function getStatusTextAttribute()
    {
        $statusMap = [
            1 => '未使用',    // 未使用
            2 => '已使用',    // 已使用
            3 => '已过期',    // 已过期
        ];
        return isset($statusMap[$this->status]) ? $statusMap[$this->status] : '--';
    }


    //追加优惠卷使用时间
    public function getUsedAtTextAttribute()
    {
        if(isset($this->attributes['status']) && $this->attributes['status'] == 2)
        {
            return $this->attributes['updated_at'];
        }
        else
        {
            return '--';
        }

    }

    //追加实付金额
    public function getPayedTextAttribute()
    {
        if(isset($this->payment_id)&&!empty($this->payment_id))
        {
            $payed =  DB::table('payments')->where('payment_id',$this->payment_id)->value('amount');

        }
        elseif(isset($this->tid)&&!empty($this->tid))
        {
            $payed =  DB::table('trade_paybills')->where('tid',$this->tid)->value('amount');
        }
        else
        {
            $payed = '0.00';
        }

        return $payed;

    }

}
