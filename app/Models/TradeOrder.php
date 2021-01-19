<?php
/**
 * @Filename        TradeOrder.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TradeOrder extends Model
{
    protected $primaryKey = 'oid';
    public $incrementing = false;
    protected $guarded = [];

    // field status 订单状态
    const WAIT_BUYER_PAY = 'WAIT_BUYER_PAY';
    const WAIT_SELLER_SEND_GOODS = 'WAIT_SELLER_SEND_GOODS';
    const WAIT_BUYER_CONFIRM_GOODS = 'WAIT_BUYER_CONFIRM_GOODS';
    const TRADE_FINISHED = 'TRADE_FINISHED';
    const TRADE_CLOSED = 'TRADE_CLOSED';
    const TRADE_CLOSED_BY_SYSTEM = 'TRADE_CLOSED_BY_SYSTEM';
    const TRADE_CLOSED_AFTER_PAY = 'TRADE_CLOSED_AFTER_PAY';
    const TRADE_CLOSED_BEFORE_PAY = 'TRADE_CLOSED_BEFORE_PAY';

    public static $tradeStatusMap = [
        self::WAIT_BUYER_PAY           => '待付款',    // 已下单等待付款
        self::WAIT_SELLER_SEND_GOODS   => '待发货',    // 已付款等待发货
        self::WAIT_BUYER_CONFIRM_GOODS => '待收货',  // 已发货等待确认收货
        self::TRADE_FINISHED           => '已完成',    // 已完成
        self::TRADE_CLOSED             => '已关闭',    // 已关闭(退款关闭订单)
        self::TRADE_CLOSED_BY_SYSTEM   => '已关闭', // 已关闭(卖家或买家主动关闭)
        self::TRADE_CLOSED_AFTER_PAY   => '付款以后,用户退款成功，交易自动关闭', // 付款以后,用户退款成功，交易自动关闭
        self::TRADE_CLOSED_BEFORE_PAY   => '付款前交易结束', // 付款前交易结束
    ];

    // field after_sales_status 售后状态
    const WAIT_SELLER_AGREE = 'WAIT_SELLER_AGREE';
    const WAIT_BUYER_RETURN_GOODS = 'WAIT_BUYER_RETURN_GOODS';
    const WAIT_SELLER_CONFIRM_GOODS = 'WAIT_SELLER_CONFIRM_GOODS';
    const SUCCESS = 'SUCCESS';
    const CLOSED = 'CLOSED';
    const REFUNDING = 'REFUNDING';
    const SELLER_REFUSE_BUYER = 'SELLER_REFUSE_BUYER';
    const SELLER_SEND_GOODS = 'SELLER_SEND_GOODS';

    public static $afterSalesStatusMap = [
        self::WAIT_SELLER_AGREE         => '买家申请售后，等待卖家同意',
        self::WAIT_BUYER_RETURN_GOODS   => '卖家同意售后申请，等待买家退货',
        self::WAIT_SELLER_CONFIRM_GOODS => '买家退货，等待卖家确认收货',
        self::SUCCESS                   => '退款成功',
        self::CLOSED                    => '退款关闭',
        self::REFUNDING                 => '退款中',
        self::SELLER_REFUSE_BUYER       => '卖家拒绝退款',
        self::SELLER_SEND_GOODS         => '卖家已发货',
    ];

    protected $appends = ['gc_name', 'status_text', 'after_sales_status_text', 'aftersales_progress_text', 'refund_info','gm_name','amount_text','shop_name','refund_fee_text','refund_at'];

    /**
     * 商品分类
     *
     * @Author moocde <mo@mocode.cn>
     * @return string
     */
    public function getGcNameAttribute()
    {
        $goods_class = GoodsClass::find($this->gc_id);

        return empty($goods_class) ? '其他' : $goods_class->gc_name;
    }

    public function getStatusTextAttribute()
    {
        return isset(self::$tradeStatusMap[$this->status]) ? self::$tradeStatusMap[$this->status] : '';
    }

    public function getAfterSalesStatusTextAttribute()
    {
        return isset(self::$afterSalesStatusMap[$this->after_sales_status]) ? self::$afterSalesStatusMap[$this->after_sales_status] : '--';
    }

    public function getAftersalesProgressTextAttribute()
    {
        $text = '';
        if ($this->after_sales_status) {
            $after_sales = TradeAftersales::where('tid', $this->tid)->where('oid', $this->oid)->first();
            if ($after_sales) {
                $text = $after_sales->progress_text;
                if (in_array($after_sales->progress, ['7', '8'])) {
                    $refunds = TradeRefunds::where('tid', $this->tid)->where('oid', $this->oid)->first();
                    if ($refunds) {
                        $text = $refunds->status_name;;
                    }
                }
            }
        }
        return $text;
    }

    public function getRefundInfoAttribute()
    {
        $refund_info = DB::table('trade_refunds')->where('oid', $this->oid)->select('refund_point','updated_at as refund_time','refunds_reason')->first();
        if (!$refund_info) {
            $refund_info = [
                'refund_point' => '--',
                'refund_time' => '--',
                'refunds_reason' => '--'
            ];
        }
        return $refund_info;
    }

    /**
     * 追加项目名称
     * @Author swl
     * @return string
     */
    public function getGmNameAttribute()
    {
        $shop_info = GmPlatform::where('gm_id', '=', $this->gm_id)->select('platform_name')->first();

        return isset($shop_info['platform_name']) ? $shop_info['platform_name'] : '';
    }

    /**
     * [countUserPointGoodsBuy 每人总限购]
     * @param  [type] $user_id       [会员id]
     * @param  [type] $activity_sign [积分商品对应活动id]
     * @return [type]                [description]
     */
    public static function countUserPointGoodsBuy($user_id,$activity_sign)
    {
        //2020-4-21 15:18:46 每人总限购
        $date = date('Y-m-d');
        $trade_status = ['WAIT_SELLER_SEND_GOODS','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED'];
        $buynum = self::where('activity_type','point_goods')
                        ->whereIn('status',$trade_status)
                        // ->whereDate('pay_time',$date)
                        ->whereNotNull('pay_time')
                        ->where('activity_sign',$activity_sign)
                        ->where('user_id',$user_id)
                        ->sum('quantity');
        // $rows = DB::select("select sum(quantity) as buynum from `em_trade_orders` where `activity_type` = 'point_goods' and `activity_sign` = '" . $activity_sign . "' and `user_id` = '". $user_id ."' and `status` IN ('WAIT_SELLER_SEND_GOODS','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED')");
        if (empty($buynum)) {
            return 0;
        }
        // $num = empty($rows[0]->buynum) ? 0 : intval($rows[0]->buynum);
        return $buynum;
    }

    /**
     * [countPointGoodsBuy 每人每日限购]
     * @param  [type] $activity_sign [积分商品对应活动id]
     * @return [type]                [description]
     */
    public static function countDayPointGoodsBuy($user_id,$activity_sign)
    {
        $date = date('Y-m-d');
        $trade_status = ['WAIT_SELLER_SEND_GOODS','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED'];
        $buynum = self::where('activity_type','point_goods')
                        ->whereIn('status',$trade_status)
                        ->whereDate('pay_time',$date)
                        ->where('activity_sign',$activity_sign)
                        ->where('user_id',$user_id)
                        ->sum('quantity');
        if (empty($buynum)) {
            return 0;
        }
        return $buynum;
    }


    /**
     * [countPointGoodsBuy 统计活动期间会员购买积分商品数据]
     * @param  [type] $activity_sign [积分商品对应活动id]
     * @return [type]                [description]
     */
    public static function countActivityPointGoodsBuy($user_id,$activity_sign,$active_start,$active_end)
    {
        $date = date('Y-m-d');
        $trade_status = ['WAIT_SELLER_SEND_GOODS','WAIT_BUYER_CONFIRM_GOODS','TRADE_FINISHED'];
        $buynum = self::where('activity_type','point_goods')
                        ->whereIn('status',$trade_status)
                        ->where('pay_time','>=',$active_start)
                        ->where('pay_time','<=',$active_end)
                        ->where('activity_sign',$activity_sign)
                        ->where('user_id',$user_id)
                        ->sum('quantity');
        if (empty($buynum)) {
            return 0;
        }
        return $buynum;
    }


    /**
     * 追加退款实付金额转义显示
     * @Author Huiho
     * @return string
     */
    public function getAmountTextAttribute()
    {

        if((isset($this->after_sales_status)&&in_array($this->after_sales_status,['SUCCESS',]))||in_array($this->status,['TRADE_CLOSED_BEFORE_PAY']))
        {
            return '-'.$this->amount;
        }
        else
        {
            return $this->amount;
        }

    }

    /**
     * 追加退款实付金额
     * @Author Huiho
     * @return string
     */
    public function getRefundFeeTextAttribute()
    {
        //售后退款(多子订单)
        $refund_info = DB::table('trade_refunds')->where('oid', $this->oid)->where('status', '1')->select('refund_fee')->first();
        //取消退款
        $refund__log_info = DB::table('trade_refund_logs')->where('tid', $this->tid)->where('status', 'succ')->select('return_fee')->first();
        if($refund_info)
        {
            if(in_array($this->status,['TRADE_CLOSED_BEFORE_PAY']))
            {
                return $this->amount;
            }
            else
            {
                return $refund_info->refund_fee;
            }
        }
        elseif($refund__log_info)
        {
            if(in_array($this->status,['TRADE_CLOSED_BEFORE_PAY']))
            {
                    return $this->amount;
            }
            else
            {
                return $refund__log_info->return_fee;
            }
        }
        else
        {
            return '0.00';
        }

    }

    // 追加店铺名称
    public function getShopNameAttribute(){
        $shop_name = Shop::find($this->shop_id);
        return $shop_name['shop_name'] ?? '--';
    }


    /**
     * 追加退款时间
     * @Author Huiho
     * @return string
     */
    public function getRefundAtAttribute()
    {
        //售后退款(多子订单)
        $refund_info = DB::table('trade_refunds')->where('oid', $this->oid)->where('status', '1')->select('updated_at')->first();
        //取消退款
        $refund__log_info = DB::table('trade_refund_logs')->where('tid', $this->tid)->where('status', 'succ')->select('updated_at')->first();
        if($refund_info)
        {
            $count = DB::table('trade_orders')->where('tid', $this->tid)->count();
            if($count>1)
            {
                if(in_array($this->status,['TRADE_CLOSED_BEFORE_PAY']))
                {
                    return $this->updated_at;
                }
                else
                {
                    return $refund_info->updated_at;
                }
            }
            else
            {
                return $refund_info->updated_at;
            }
        }
        elseif($refund__log_info)
        {
            return $refund__log_info->updated_at;
        }
        else
        {
            return '--';
        }

    }

    
}
