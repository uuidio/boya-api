<?php
/**
 * @Filename        WxpayController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\Payment;
use ShopEM\Models\SecKillOrder;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\Trade;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserDepositCash;
use ShopEM\Models\UserXinpollInfo;
use ShopEM\Services\BusinessCloud\PayService;
use ShopEM\Services\Payment\Wxpayjsapi;
use ShopEM\Services\Payment\Wxpaywap;
use ShopEM\Services\Payment\Wxpaymini;
use ShopEM\Services\PaymentService;
use Illuminate\Support\Facades\Cache;
use ShopEM\Services\WeChatMini\WXMessage;
use Yansongda\Pay\Pay;

class WxpayController extends BaseController
{
    /**
     * 微信公众号支付
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function mpPay(Request $request)
    {
        $referer = $request->getRequestUri();

        if (!$request->session()->has('openid')) {
            return redirect('/wechat/oauth_redirect?referer=' . $referer);
        }

        $payment_info = Payment::where('payment_id', $request->payment_id)->first();

        try {
            Payment::where('payment_id', $request->payment_id)->update(['pay_app' => 'Wxpayjsapi']);
        } catch (Exception $e) {

        }

        $pay_params = [
            'payment_id' => $payment_info->payment_id,
            'body' => 'shopem bbc',
            'amount' => $payment_info->amount,
            'open_id' => $request->session()->get('openid'),
        ];

        $pay_info = Wxpayjsapi::makeJsPay($pay_params);

        echo $pay_info;
        exit;
    }


    /**
     * 微信wap H5支付
     *
     * @Author nlx
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function wapPay(Request $request)
    {
        $referer = $request->getRequestUri();

        $payment_info = Payment::where('payment_id', $request->payment_id)->first();
        try {
            Payment::where('payment_id', $request->payment_id)->update(['pay_app' => 'Wxpaywap']);
        } catch (Exception $e) {

        }
        if ($payment_info->status == 'succ') {
            $return_url = env('PAY_RETURN_URL') . '/payment/status/' . $payment_info->payment_id;
            $pay_html = '
                    <html>
                        <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
                        <title>微信安全支付</title>
                        <script language="javascript">
                            window.location.href = "' . $return_url . '";
                        </script>
                        <body>
                     </body>
                    </html>
                ';

            echo $pay_html;
            exit;
        }
        $pay_params = [
            'payment_id' => $payment_info->payment_id,
            'body' => '宝能电商平台',
            'amount' => $payment_info->amount,
        ];
        $data = [
            'out_trade_no' => $pay_params['payment_id'],
            'body' => $pay_params['body'],
            'total_fee' => $pay_params['amount'] * 100,
        ];
        // Pay::wechat()->wap($data);
        return Pay::wechat()->wap($data);
        // $pay_info = Wxpaywap::makeWapPay($pay_params);

        // echo $pay_info;
        // exit;
    }


    /**
     * 微信小程序支付
     *
     * @Author hfh_wind
     * @param Request $request ['payment_id','code']
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function MiniPay(Request $request)
    {
//        $gm_id = $this->GMID;

        $payment_info = Payment::where('payment_id', $request->payment_id)->first();

        $gm_id = $payment_info['gm_id'];
        $param = Cache::get('gm_platform_' . $gm_id);

        if (empty($param)) {
            throw new \Exception('支付参数异常！');
        }

        /*$appid = env('WECHAT_MINI_APPID');
        $appsecret = env('WECHAT_MINI_APPSECRET');*/

        $appid = $param['mini_appid'];
        $appsecret = $param['mini_secret'];

        try {
            Payment::where('payment_id', $request->payment_id)->update(['pay_app' => 'Wxpaymini', 'appid' => $appid]);
        } catch (\Exception $e) {
            throw new \Exception('微信小程序：' . $e->getMessage());
        }

        $redis = new Redis();
        $group_payment_key = 'group_pay_' . $request->payment_id;
        $group_payment = $redis::get($group_payment_key);
        if ($group_payment) {
            $group_payment = json_decode($group_payment, true);

            if ($group_payment['set_type'] == 'group') {
                $key_group = $group_payment['user_id'] . 'group' . $request->payment_id;
                $start_group = $redis::get($key_group);
                if (empty($start_group)) {
                    throw new \Exception("团购已经过期,请勿支付!!");
                }
            } else {
                $key_group = $group_payment['user_id'] . 'groupjoin' . $request->payment_id;
                $join_group = $redis::get($key_group);
                if (empty($join_group)) {
                    throw new \Exception("跟团已经过期,请勿支付!!");
                }
            }
        }

        $tid = TradePaybill::where('payment_id', $payment_info->payment_id)->value('tid');
        $tradeOrder = TradeOrder::where(['activity_type' => 'point_goods', 'tid' => $tid])->select('user_id', 'activity_sign', 'quantity')->latest()->first();
        if ($tradeOrder) {
            $point_goods = \ShopEM\Models\PointActivityGoods::where('id', $tradeOrder->activity_sign)->first();
            if (!$point_goods) {
                throw new \Exception('积分活动已结束');
            }
            //积分商品限制判断
            $pointGoodsObj = new \ShopEM\Services\Marketing\PointGoods;
            $check = $pointGoodsObj->buyCheck($tradeOrder->user_id, $point_goods, $tradeOrder->quantity);
            if (isset($check['code']) && $check['code'] > 0) {
                throw new \Exception($check['msg']);
            }
        }
        //避免秒杀订单支付时被关掉做一个时间限制。
        ###注意这个时间跟秒杀关闭时间有关系。当前5分钟关闭
        $seckill_created_at = Trade::where('tid', $tid)->where('activity_sign', 'seckill')->value('created_at');
        if ($seckill_created_at) {
            //4分30秒  = 4*60
            if (time() - strtotime($seckill_created_at) >= 240 + 30) {
                throw new \Exception("秒杀已经过期,请勿支付!!");
            }
        }


        $mini = new Wxpaymini($gm_id);
        //获取openid
        $jscode_res = $mini->getOpenId($request->code, $appid, $appsecret);

        $pay_params = [
            'payment_id' => $payment_info->payment_id,
            'openid' => $jscode_res['openid'],
            'total_fee' => $payment_info->amount,
//            'attach'  => $param['platform_name'],
            'body_title' => $param['platform_name'],
            'app_url' => $param['app_url'],
        ];

        // testLog($pay_params);
        $pay_info = $mini->wxpay_native($pay_params);
        return $this->resSuccess($pay_info);
        // return $this->resSuccess(['url'=>(new PayService())->getPayConfig($payment_info)]);
    }


    /**
     * 微信支付异步通知处理
     *
     * @Author moocde <mo@mocode.cn>
     * @return string
     */
    public function notify()
    {

        \Log::info('notify success');

        $xml = request()->getContent();
        $res = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        try {
            $gm_id = DB::table('payments')->where('payment_id', $res['out_trade_no'])->value('gm_id');
            $mini = new Wxpaymini($gm_id);
            $pay = Pay::wechat($mini->payConfig);
            $data = $pay->verify();
            $payment_info = Payment::where('payment_id', $data->out_trade_no)->first();
            if (!$payment_info) {
                return 'fail';
            }
            if ($payment_info->status == 'succ') {
                return $pay->success();
            }
        } catch (\Exception $e) {
            Log::error('wechat notify: ' . $e->getMessage());
            return 'fail';
        }

        \Log::info('notify 1s');

        $payment_data['trade_no'] = $data->transaction_id;
        $payment_data['payment_id'] = $data->out_trade_no;
        $payment_data['pay_app'] = $payment_info->pay_app;

        $tradePaybill = TradePaybill::where('payment_id', $payment_info->payment_id)->first();
        $trade = Trade::where('tid', $tradePaybill->tid)->first();
        \Log::info('notify 2');

        #如果是系统自动关闭的订单，支付成功的要自动退款
        if ($trade && $trade->status == Trade::TRADE_CLOSED_BY_SYSTEM) {
            (new PaymentService)->systemDoRefund($payment_data, $payment_info);
            return $pay->success();
            exit;
        }
        \Log::info('notify 3');

        $no_status = [Trade::TRADE_FINISHED, Trade::TRADE_CLOSED];
        if ($trade && in_array($trade->status, $no_status)) {
            \Log::info('notify 4');

            return $pay->success();
            exit;
        }
        \Log::info('notify 6');

        $payment_data['user_id'] = $payment_info->user_id;

        PaymentService::paySuccess($payment_data, '');

        return $pay->success();
    }

}
