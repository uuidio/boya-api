<?php
/**
 * @Filename        UmspayController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\Payment;
use ShopEM\Services\PaymentService;
use ShopEM\Services\UmspayService;

class UmspayController extends BaseController
{
    public function pay()
    {
        print_r(Carbon::now());
        dd(Carbon::now()->toDateTimeString());

        $trade_info = PaymentService::tradeInfo('10190101150425148447');
        dd($trade_info->trade_info);

        $umspaylink = '';
        $umspaylink = UmspayService::makePaylink([]);
        echo $umspaylink;
    }

    /**
     * 银联异步通知处理
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return string
     */
    public function notify(Request $request)
    {
        $data = $request->all();
        Log::info('umspay log info: ', $request->all());
        $is_sign = UmspayService::checkReturnSign($data);

        if($is_sign) {
            $payment_id = substr($data['merOrderId'], 4);
            $payment_info = Payment::find($payment_id);
            if(!empty($payment_info) && $payment_info->status != Payment::SUCC && $data['status'] == 'TRADE_SUCCESS') {
                PaymentService::paySuccess($payment_id, $data['merOrderId']);
            }
        }

        return 'SUCCESS';
    }

    /**
     *   回调所带参数
     *   "payTime" => "2019-01-02 21:50:44"
     *   "sx" => "cKwh"
     *   "connectSys" => "UNIONPAY"
     *   "sign" => "3F6CFD63504F081D7214A04C5B11F723"
     *   "mid" => "898440351983824"
     *   "settleDate" => "2019-01-02"
     *   "mchntUuid" => "A29BE9ECAB324E47BBF687DD686DAD45"
     *   "tid" => "38241001"
     *   "totalAmount" => "24900"
     *   "couponAmount" => "0"
     *   "notifyId" => "9c066e76-3817-48bc-aac2-59aa1f2a93c9"
     *   "subInst" => "100500"
     *   "orderDesc" => "深圳市人人颂实业有限公司"
     *   "seqId" => "05813253877N"
     *   "merOrderId" => "377010190102135039334581"
     *   "status" => "WAIT_BUYER_PAY"
     *   "targetSys" => "WXPay"
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function returnData(Request $request)
    {
        $data = $request->all();
        $is_sign = UmspayService::checkReturnSign($data);

        if($is_sign) {
            $payment_id = substr($data['merOrderId'], 4);
//            $payment_info = Payment::find($payment_id);
//            testLog($payment_info->toArray());
//            if(!empty($payment_info) && $payment_info->status != Payment::SUCC && $data['status'] == 'TRADE_SUCCESS') {
//                PaymentService::paySuccess($payment_id, $data['merOrderId']);
//            }

            return redirect('http://m.sdjchina.com/payment/status/' . $payment_id);
        }

        return redirect('http://m.sdjchina.com/error/payment');
    }
}