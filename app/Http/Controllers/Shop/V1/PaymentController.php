<?php
/**
 * @Filename        PaymentController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\Payment;
use ShopEM\Models\PaymentApis;
use ShopEM\Models\Trade;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\PaymentCfgs;
use ShopEM\Models\UserWallet;
use ShopEM\Models\UserPassword;
use ShopEM\Services\PaymentService;
use ShopEM\Services\PayToolService;
use ShopEM\Http\Requests\Shop\DoPayRequest;
use Illuminate\Support\Facades\Cache;
use ShopEM\Services\CartService;
use ShopEM\Services\TlPay\WalletService;

class PaymentController extends BaseController
{
    /**
     * 支付单信息
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentInfo(Request $request)
    {
        if (!$request->has('payment_id')) {
            return $this->resFailed(406, '参数错误!');
        }
        $payment_info = Payment::where('payment_id', $request->payment_id)->where('user_id',
            $this->user['id'])->first();
        if (empty($payment_info)) {
            return $this->resFailed(610);
        }

       // $trade_info = PaymentService::tradeInfo($payment_info->payment_id);
        $pay_info['payment_info'] = $payment_info;
        $bill = TradePaybill::where('payment_id', $request->payment_id)->get();
        if (count($bill) == 1) {
            $trade = Trade::where('tid', $bill[0]->tid)->first();
            if ($trade->pick_type == 1) {
                $m = new \ShopEM\Http\Controllers\Shop\V1\UserTradeController();
                $pay_info['qrcode_pick'] = $m->getZitiQrcode($trade->tid);
            }
        }
        foreach ($bill as $key => $value) {
            $trade = Trade::where('tid', $value->tid)->first();
            $pay_info['trade_type'] = $trade->trade_type;

        }
        if (isset($exchange_no) && count($exchange_no) > 0) {
            $pay_info['exchange_no'] = implode(',', $exchange_no);
        }
        //可选支付方式
        $platform = $request->platform = 'iswap';
        $paylist = PaymentCfgs::where(['on_use' => 1])->where(function ($query) use ($platform) {
            $query->where(['platform' => 'iscommon'])->orWhere(['platform' => $platform]);
        })->select('id', 'name', 'pay_type')->get();

        foreach ($paylist as $key => $value) {
            // 判断微阅览器使用的支付条件
            if (strpos($_SERVER["HTTP_USER_AGENT"], "MicroMessenger") && $value->pay_type == 'Wxpaywap') {
                unset($paylist[$key]);
            }

            if (!strpos($_SERVER["HTTP_USER_AGENT"], "MicroMessenger") && $value->pay_type == 'Wxpayjsapi') {
                unset($paylist[$key]);
            }

        }
        $pay_info['paylist'] = $paylist;

        if ($payment_info->amount == 0) {
            foreach ($paylist as $key => $value) {
                if ($value->pay_type == 'Deposit') {
                    $paynewlist[] = $value;
                }
            }
            $pay_info['paylist'] = $paynewlist;
        }


        return $this->resSuccess($pay_info);
    }

    /**
     * 更新支付方式
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePayApp(Request $request)
    {
        return $this->resFailed(406);
        testLog($request->all());
        Payment::where('payment_id', $request->payment_id)
            ->where('user_id', $this->user['id'])
            ->update(['pay_app' => $request->pay_app]);

        return $this->resSuccess();
    }

    public function payList(Request $request,PaymentApis $apis)
    {
//        $user_id = $this->user->id;
//        $type = $request->type??'';
//
//        //根据会员钱包状态和商品配置状态，判断是否可以使用钱包功能支付
//        $userWalletStatus = $payBuyStatus = false;
//
//        #钱包功能未注册  或者 冻结 都不使用
//        $wallet = UserWallet::where('user_id',$user_id)->first();
//        if (UserPassword::usability($user_id) && $wallet) $userWalletStatus = true;
//
//        #店铺判断
//        if($userWalletStatus) $payBuyStatus = (new CartService)->checkWalletPay($user_id,$type);
//
//        if($userWalletStatus && $payBuyStatus)
//        {
//            $openWalletPay = true;
//            $info = (new WalletService())->getWalletInfo($wallet);
//        }else{
            $apis = $apis->whereNotIn('app', ['WalletPhysical','WalletVirtual']);
//        }
        return $this->resSuccess([
            'list'      =>  $apis->get()->KeyBy('app'),
            'balance'   =>  [
                'WalletPhysical'    =>  isset($info)? $info['physical_card']?? 0 : 0,
                'WalletVirtual'     =>  isset($info)? $info['virtual_card']?? 0 : 0,
            ]
        ]);
    }


    /**
     *  列出所有启用的支付方式
     *
     * @Author hfh_wind
     * @param Request $request
     * @return mixed
     */
    public function paycenter(Request $request)
    {
        if (!$request->platform) {
            return $this->resFailed(406, ['error' => '请传入平台类型']);
        }

        $platform = $request->platform = 'iswap';
        $paylist = PaymentCfgs::where(['on_use' => 1])->where(function ($query) use ($platform) {
            $query->where(['platform' => 'iscommon'])->orWhere(['platform' => $platform]);
        })->select('id', 'name', 'pay_type')->get();

        return $paylist;
    }


    /**
     * 去支付
     *
     * @Author hfh_wind
     * @param DoPayRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dopay(DoPayRequest $request)
    {

        $payment_info = Payment::where('payment_id', $request->payment_id)->where('user_id',
            $this->user['id'])->first();

        if (empty($payment_info)) {
            return $this->resFailed(610);
        }

        $trade_info = PaymentService::tradeInfo($payment_info->payment_id);
        PaymentService::goodsIsAleAble($trade_info['trade_order']);//判断子订单里的商品是否能购买
        $trade_info['payment_info'] = $payment_info;
        $umspay_link = UmspayService::makePaylink($trade_info);
        $payment_info = $payment_info->toArray();
        $payment_info['wechatpay'] = $umspay_link;

        return $this->resSuccess($payment_info);
    }


    /**
     * 请求第三方支付网关
     *
     * @Author hfh_wind
     * @param $payData
     * @return bool
     * @throws \Exception
     */
    public function generate(DoPayRequest $request, PayToolService $pay)
    {
        // 异常处理
        $payData = $request->all();
        $payData['user_id'] = $this->user['id'];
        $payment_id = $request->payment_id;
        testLog(['generate'=>$payment_id]);

        $payment_info = Payment::where('payment_id', $payment_id)->where('user_id', $this->user['id'])->first();

        if (empty($payment_info) || $payment_info->status == 'succ') {
            return $this->resFailed(406, ['error' => '支付单异常,请到会员中心查看订单!']);
        }
       // 付款时候秒杀订单再做判断
       // $groupservice = new \ShopEM\Services\SecKillService();
       // $groupservice->GroupPayCheck($payment_info['payment_id']);

        $str_app = $request->pay_app;
        $payData['payment_info'] = $payment_info;

        //避免传小写的这里处理掉
        $str_app = ucfirst($str_app);

        //如果支付金额为0，则使用0元支付
        if ($payment_info['amount'] <= 0) {
            $str_app = 'Zero';
        }

        //去支付
        $is_payed = $pay->dopay($payData, $str_app);
        //余额支付和0元支付单独处理
        if ($str_app == 'Deposit'  || $str_app=='Zero') {

            if ($is_payed['res'] == '6') {
                // $this->freezeGoodStore($payment_id);
            }
            return $this->resSuccess($is_payed);
        }

        if ($is_payed) {
            //前期没有冻结库存的情况下先,支付完成直接扣减库存
            // $this->freezeGoodStore($payment_id);
            $is_payed = ['res' => '6', '支付成功!'];
            return $this->resSuccess($is_payed);
        }

    }

    /**
     * 支付完成扣减库存
     *
     * @Author hfh_wind
     * @param $payment_id
     * @return bool|\Illuminate\Http\JsonResponse
     */
    public function freezeGoodStore($payment_id)
    {
        $freezeStore = new  \ShopEM\Services\GoodsService();

        DB::beginTransaction();
        try {
            $TradePaybill = TradePaybill::where(['payment_id' => $payment_id, 'status' => 'succ'])->first();

            if (empty($TradePaybill)) {
                return $this->resFailed(406, ['error' => '尚未支付完成,异常订单!']);
            }
            $trad_model = new Trade();
            $trade_info = $trad_model->where(['trades.tid' => $TradePaybill->tid])->select('trade_orders.*')
                ->leftJoin('trade_orders', 'trades.tid', '=', 'trade_orders.tid')->get();
            $arrParams = [];
            foreach ($trade_info as $key => $value) {

                $arrParams['goods_id'] = $value['goods_id'];
                $arrParams['sku_id'] = $value['sku_id'];
                $arrParams['quantity'] = $value['quantity'];
                $arrParams['oid'] = $value['oid'];
                $arrParams['shop_id'] = $value['shop_id'];

                $res = $freezeStore->freezeItemStore($arrParams);
                if (!$res) {
                    throw new \Exception('扣减库存失败!');
                }
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(700, '扣减库存失败' . $e->getMessage());
        }
        DB::commit();

        return true;
    }


    public function apiPayStatus(Request $request)
    {
        if (!$request->has('payment_id')) {
            return $this->resFailed(406);
        }
        $payment_id = $request->payment_id;
        $cache_key = 'api_pay_status_id_' . $payment_id;
        $value = Cache::get($cache_key, ['status' => 'ready']);
        // if ($value['status'] == 'succ') {
        //     $pay_html = '
        //         <html>
        //             <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
        //             <title>微信安全支付</title>
        //             <script language="javascript">
        //                 window.location.reload();
        //             </script>
        //             <body>
        //          </body>
        //         </html>
        //     ';
        //     echo $pay_html;
        //     exit;
        //     return $pay_html;
        // }
        return $value;
    }

}
