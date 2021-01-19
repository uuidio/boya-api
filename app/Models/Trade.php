<?php
/**
 * @Filename        Trade.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Trade extends Model
{
    protected $primaryKey = 'tid';
    public $incrementing = false;
    protected $guarded = [];

    // field status 订单状态
    const WAIT_BUYER_PAY = 'WAIT_BUYER_PAY';
    const WAIT_SELLER_SEND_GOODS = 'WAIT_SELLER_SEND_GOODS';
    const WAIT_BUYER_CONFIRM_GOODS = 'WAIT_BUYER_CONFIRM_GOODS';
    const TRADE_FINISHED = 'TRADE_FINISHED';
    const TRADE_CLOSED = 'TRADE_CLOSED';
    const TRADE_CLOSED_BY_SYSTEM = 'TRADE_CLOSED_BY_SYSTEM';

    // field cancel_status 取消订单状态
    const NO_APPLY_CANCEL = 'NO_APPLY_CANCEL';
    const WAIT_PROCESS = 'WAIT_PROCESS';
    const WAIT_REFUND = 'WAIT_REFUND';
    const REFUND_PROCESS = 'REFUND_PROCESS';
    const SHOP_CHECK_FAILS = 'SHOP_CHECK_FAILS';
    const SUCCESS = 'SUCCESS';
    const FAILS = 'FAILS';

    public static $tradeStatusMap = [
        self::WAIT_BUYER_PAY           => '待付款',    // 已下单等待付款
        self::WAIT_SELLER_SEND_GOODS   => '待发货',    // 已付款等待发货
        self::WAIT_BUYER_CONFIRM_GOODS => '待收货',  // 已发货等待确认收货
        self::TRADE_FINISHED           => '已完成',    // 已完成
        self::TRADE_CLOSED             => '已关闭',    // 已关闭(退款关闭订单)
        self::TRADE_CLOSED_BY_SYSTEM   => '已关闭', // 已关闭(卖家或买家主动关闭)
    ];

    public static $tradeCancelStatusMap = [
        self::NO_APPLY_CANCEL  => '未申请',
        self::WAIT_PROCESS     => '等待审核',
        self::WAIT_REFUND      => '等待退款',
        self::REFUND_PROCESS   => '退款处理',
        self::SHOP_CHECK_FAILS => '商家审核不通过',
        self::SUCCESS          => '取消成功',
        self::FAILS            => '取消失败',
    ];

    protected $appends = [
        'trade_order',
        'status_text',
        'payment_id',
        'shop_info',
        'trade_cancel',
        'user_account',
        'pick_type_name',
        'recharge_info',
        'cancel_text',
        'point_discount_fee',
        'group_info',
        'logi_name',
        'activity_sign_text',
        'pay_type_text',
        'user_mobile',
        'shop_name',
        'group_status_text',
        'push_crm_text',
        'gm_name',
        'point_unit',
        'is_allow_cancel',
        'write_off_time',
        'amount_text',
        'payed_time',
        'syn_stock_status_text'
    ];

    public function getTradeOrderAttribute()
    {
        return TradeOrder::where('tid', $this->tid)->get();
    }

    public function getSynStockStatusTextAttribute()
    {
        $map = ['未同步','成功','失败'];
        if (isset($this->syn_stock_status)) {
            return $map[$this->syn_stock_status];
        } else {
            return '';
        }
    }

    public function getUserAccountAttribute()
    {
        return UserAccount::find($this->user_id);
    }

    public function getUserMobileAttribute()
    {
        $user = UserAccount::select('mobile')->find($this->user_id);
        return $user['mobile'] ?? '';
    }

    public function getRechargeInfoAttribute()
    {
        if ($this->trade_type == 2) {
            return TradeRecharge::where('tid', $this->tid)->first();
        } else {
            return null;
        }
    }

    /**
     *  团购信息
     * @Author hfh_wind
     * @return mixed
     */
    public function getGroupInfoAttribute()
    {
        $get_group = GroupsUserJoin::where('tid', '=', $this->tid)->select('groups_bn', 'tid')->first();
        $retrun = [];
        if (!empty($get_group)) {
            $retrun = GroupsUserOrder::where(['groups_bn' => $get_group['groups_bn']])->select('group_number', 'groups_bn', 'status')->first();
        }

        return $retrun;
    }


    public function getTradeCancelAttribute()
    {
        return TradeCancel::where('tid', $this->tid)->orderBy('id', 'desc')->first();
    }

    public function getCancelTextAttribute()
    {
        $cancel = TradeCancel::where('tid', $this->tid)->orderBy('id', 'desc')->first();
        if ($cancel) {
            $text = [
                'NO_APPLY_CANCEL'  => '未申请',
                'WAIT_CHECK'       => '等待审核',
                'WAIT_REFUND'      => '等待退款',
                'SHOP_CHECK_FAILS' => '商家审核不通过',
                'FAILS'            => '退款失败',
                'SUCCESS'          => '退款成功',
            ];
            if ($cancel->is_refund) {
                return $text[$cancel->refunds_status];
            }
            if ($cancel->cancel_from == 'admin') {
                return '系统关闭';
            } else {
                $status = isset(self::$tradeCancelStatusMap[$cancel->refunds_status]) ? self::$tradeCancelStatusMap[$cancel->refunds_status] : $text[$cancel->refunds_status];
                return $status;
            }
        } else {
            return '无取消';
        }
    }


    public function getIsAllowCancelAttribute()
    {
        if ($this->status == 'WAIT_BUYER_PAY' || $this->status == 'WAIT_SELLER_SEND_GOODS')
        {
            if ($this->cancel_text == '无取消')
            {
                if ($this->activity_sign == 'choujiang') {
                    return false;
                }
                if ($this->activity_sign == 'point_goods') {
                    $exists = TradeOrder::where(['tid'=>$this->tid,'allow_after'=>0])->exists();
                    if ($exists) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     *  返回支付单号
     * @Author hfh_wind
     * @return string
     */
    public function getPaymentIdAttribute()
    {
        $paybill = TradePaybill::where('tid', $this->tid)->first();

        return empty($paybill) ? '' : $paybill->payment_id;
    }


    public function getPointDiscountFeeAttribute()
    {
        $points_fee = 0;
        $paybill = TradePaybill::where('tid', $this->tid)->first();
        if ($paybill) {
            //优惠分摊
            $paymentInfo = Payment::where(['payment_id' => $paybill->payment_id])->first();
            if ($paymentInfo) {
                $total = $paymentInfo->amount + $paymentInfo->points_fee;
                $getRefundBn = new \ShopEM\Services\TradeService();
                if ($total) {
                    $points_fee = $getRefundBn->avgDiscountFee($total, $paymentInfo->points_fee,
                        $this->amount); //积分抵扣金额
                }
            }
        }
        return $points_fee;

    }

    public function getStatusTextAttribute()
    {
        return self::$tradeStatusMap[$this->status];
    }

    public function getPickTypeNameAttribute()
    {
        if ($this->pick_type == 1) {
            return '自提';
        } else {
            return '快递';
        }
    }

//    public function getRelationTradeAttribute()
//    {
//        return TradeRelation::where('tid',$this->tid)->get();
//    }

    /**
     * 店铺信息
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getShopInfoAttribute()
    {
        if ($this->shop_id  == 0) {
            $data = (object)[];
            $data->id = 0;
            $data->shop_name = '项目平台端';
            $data->shop_logo = '';
            return $data;
        }
        return Shop::select('id', 'shop_name', 'shop_logo')->where('id', $this->shop_id)->first();
    }

    public function getShopNameAttribute()
    {
        $shop = Shop::select('shop_name')->where('id', $this->shop_id)->first();
        return $shop['shop_name'] ?? '';
    }

    public static function shipping($tid)
    {
        DB::beginTransaction();
        try {
            self::where('tid', $tid)->update([]);

            DB::commit();
        } catch (\Exception $e) {

        }
    }

    /**
     * 获取收件的快递公司名称
     *
     * @Author djw
     * @return string
     */
    public function getLogiNameAttribute()
    {
        $logi_name = '';
        if ($this->invoice_no) {
            $delivery = LogisticsDelivery::select('logi_name')->where('tid', $this->tid)->first();
            $logi_name = $delivery ? $delivery['logi_name'] : '';
        }
        return $logi_name;
    }

    /**
     * 追加活动状态
     * @Author hfh_wind
     * @return mixed
     */
    public function getActivitySignTextAttribute()
    {
        $text='线上交易';
        if($this->activity_sign=='seckill'){
            $text='秒杀活动';
        }elseif($this->activity_sign=='is_group'){
            $text='团购活动';
        }elseif($this->activity_sign=='point_goods'){
            $text='积分兑换活动';
        }
        elseif($this->activity_sign=='choujiang'){
            $text='抽奖活动';
        }

        return $text;
    }


    public function getWriteOffTimeAttribute()
    {
        $text = '不限制';
        if($this->activity_sign == 'point_goods')
        {
            $activity_point = TradeOrder::where('tid', $this->tid)
                ->where('activity_type','point_goods')
                ->select('write_off_start','write_off_end')
                ->first();
            $text = '';
            if (isset($activity_point->write_off_start)) {
                $text = $activity_point->write_off_start;
            }
            if (isset($activity_point->write_off_end)) {
                $text .= ' ~ '.$activity_point->write_off_end;
            }
            if (empty($text)) {
                $text = '不限制';
            }
        }
        return $text;
    }

    /**
     * 追加支付方式
     * @Author djw
     * @return mixed
     */
    public function getPayTypeTextAttribute()
    {
        $text='';
        if($this->pay_type=='online'){
            $text='线上支付';
        }elseif($this->pay_type=='offline'){
            $text='货到付款';
        }
        return $text;
    }

    public function getGroupStatusTextAttribute()
    {
        return '--';
    }

    public function getPushCrmTextAttribute()
    {
        switch ($this->push_crm) {
            case '1':
                $text = 'CRM推送中';
                break;
            case '2':
                $text = 'CRM推送成功';
                break;
            case '3':
                $text = 'CRM推送失败';
                break;
            default:
                $text = 'CRM未推送';
                break;
        }
        return $text;
    }

    /**
     * 追加项目名称
     * @Author swl
     * @return string
     */
    public function getGmNameAttribute()
    {
        $platform_name = GmPlatform::where('gm_id', '=', $this->gm_id)->value('platform_name');

        return !empty($platform_name) ? $platform_name : '';
    }

    public function getPointUnitAttribute()
    {
        if ($this->gm_id == GmPlatform::gmSelf()) {
            return '牛币';
        }
        return '积分';
    }

    /**
     * 追加退款实付金额转义显示
     * @Author Huiho
     * @return string
     */
    public function getAmountTextAttribute()
    {

//        if(isset($this->cancel_status)&&in_array($this->cancel_status,['SUCCESS']))
//        {
//            return '-'.$this->amount;
//        }
//        else
//        {
            return $this->amount;
//        }

    }


    /**
     * 追加支付时间
     * @Author Huiho
     * @return string
     */
    public function getPayedTimeAttribute()
    {

        $payed_time =  DB::table('trade_paybills')->where('tid', $this->tid)->value('payed_time');

        return empty($payed_time) ? '--' : $payed_time;

    }


}
