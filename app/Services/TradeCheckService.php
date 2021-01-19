<?php


namespace ShopEM\Services;


use Illuminate\Support\Carbon;
use ShopEM\Models\Payment;
use ShopEM\Models\Trade;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\TradeRefunds;
use ShopEM\Models\WechatTradeCheck;

class TradeCheckService
{
    public function createFromBillByTime(Carbon $start, Carbon $stop)
    {
        $bills = TradePaybill::where('status','succ')->whereBetween('payed_time',[$start->toDateTimeString(),$stop->toDateTimeString()])->get();
        foreach ($bills as $bill) {
            $this->createPayData($bill);
        }
    }

    public function createFromBillById($paymentId)
    {
        $bills = TradePaybill::where('payment_id',$paymentId)->get();
        foreach ($bills as $bill) {
            $this->createPayData($bill);
        }
    }

    public function createFromRefundByTime(Carbon $start, Carbon $stop)
    {
        $refunds = TradeRefunds::where('status',1)->whereBetween('refund_at',[$start->toDateTimeString(),$stop->toDateTimeString()])->get();
        foreach ($refunds as $refund) {
            $this->createRefundData($refund);
        }
    }

    public function createFromRefundByTid($tid)
    {
        $refunds = TradeRefunds::where('tid',$tid)->where('status', 1)->get();
        foreach ($refunds as $refund) {
            $this->createRefundData($refund);
        }
    }

    public function createPayData(TradePaybill $bill)
    {
        $trade = Trade::where('tid', $bill->tid)->select('shop_id','end_time','status')->first();
        $payment = Payment::where('payment_id',$bill->payment_id)->select('status','pay_app','trade_no')->first();
        if ($payment->status == 'succ' && $payment->pay_app == 'Wxpaymini') {
            $data = [
                'trade_at'      =>  $bill->payed_time,
                'payment_id'    =>  $bill->payment_id,
                'tid'           =>  $bill->tid,
                'payed_fee'     =>  $bill->amount,
                'gm_id'         =>  $bill->gm_id,
                'trade_type'    =>  'TRADE',
                'shop_id'       =>  $trade->shop_id,
                'finish_at'     =>  $trade->end_time,
                'trade_no'      =>  $payment->trade_no,
            ];
            WechatTradeCheck::create($data);
        }
    }

    public function createRefundData(TradeRefunds $refunds)
    {
        $bill = TradePaybill::where('tid',$refunds->tid)->first();
        $trade = Trade::where('tid', $bill->tid)->select('shop_id','end_time','status')->first();
        $payment = Payment::where('payment_id',$bill->payment_id)->select('status','pay_app','trade_no')->first();
        if ($payment->status == 'succ' && $payment->pay_app == 'Wxpaymini') {
            $data = [
                'trade_at'      =>  $bill->payed_time,
                'payment_id'    =>  $bill->payment_id,
                'tid'           =>  $bill->tid,
                'payed_fee'     =>  $bill->amount,
                'gm_id'         =>  $bill->gm_id,
                'trade_type'    =>  'REFUND',
                'shop_id'       =>  $trade->shop_id,
                'finish_at'     =>  $trade->end_time,
                'trade_no'      =>  $payment->trade_no,
                'refund_bn'     =>  $refunds->refund_bn,
                'refund_fee'    =>  $refunds->refund_fee,
                'refund_at'     =>  $refunds->refund_at,
                'oid'           =>  $refunds->oid,
            ];
            WechatTradeCheck::create($data);
        }
    }
}
