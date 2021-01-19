<?php
/**
 * @Filename        Wxpaymini.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services\Payment;
use Illuminate\Support\Facades\Cache;
use Yansongda\Pay\Pay;

class Wxpaymini
{

    public function __construct($gm_id)
    {

        $config = Cache::get('gm_platform_'.$gm_id);
        if(empty($config)){
            throw new \Exception('支付参数异常！');
        }

        $this->payConfig  = [
        // 公众号 APPID
        'app_id'      => $config['mini_appid'],

        // 小程序 APPID
        'miniapp_id'  => $config['mini_appid'],


        // APP 引用的 appid
        'appid'       => $config['mini_appid'],

        // 微信支付分配的微信商户号
        'mch_id'      => $config['mch_id'],

        // 微信支付异步通知地址
        'notify_url'  => env('APP_URL') . '/payment/wx_notify',

        // 微信支付签名秘钥
        'key'         => $config['pay_key'],

        // 客户端证书路径，退款、红包等需要用到。请填写绝对路径，linux 请确保权限问题。pem 格式。
        'cert_client' => config_path('cert/wechat/').'apiclient_cert.pem',

        // 客户端秘钥路径，退款、红包等需要用到。请填写绝对路径，linux 请确保权限问题。pem 格式。
        'cert_key'    => config_path('cert/wechat/').'apiclient_key.pem',

        // optional，默认 warning；日志路径为：sys_get_temp_dir().'/logs/yansongda.pay.log'
        'log'         => [
            'file'     => storage_path('logs/pay.log'),
            'level'    => 'debug',
            'type'     => 'daily', // optional, 可选 daily.
            'max_file' => 30,
        ]];
    }


    /**
     *  微信支付接口
     * @Author hfh_wind
     */
    public  function wxpay_native($param)
    {

        if ($param) {
            //付款金额，必填
            $total_amount = $param['total_fee'];

            $config = [
                'out_trade_no'     => $param['payment_id'],
                // 订单号
                'total_fee'        => $total_amount * 100,
                // 订单金额，**单位：分**
                'attach'           => $param['body_title'],
                // 额外信息，可自定义填写
                'body'             => $param['body_title']."-支付编号".$param['payment_id'],
                // 订单描述
                'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],

                'openid' => $param['openid'],

//                'notify_url' => env('APP_URL')."/payment/wx_notify",
                'notify_url' => $param['app_url']."/payment/wx_notify",

                'trade_type' => 'JSAPI',
            ];

            // $config = array_merge($this->payConfig,$config);
            // 更多参数:https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_1
//            $data['url'] = Pay::wechat()->miniapp($config);
            $data['url'] = Pay::wechat($this->payConfig)->miniapp($config);
            return $data;
        }

    }


    /**
     * 获取微信小程序的openid
     * @Author hfh_wind
     * @param $code
     * @param $appid
     * @param $appsecret
     * @return mixed
     * @throws \Exception
     */
    public  function getOpenId($code, $appid, $appsecret)
    {
        try {
            //获取session_key和openid
            $client = new \GuzzleHttp\Client();
            $api_url = 'https://api.weixin.qq.com/sns/jscode2session';
            $api_url = $api_url . '?appid=' . $appid;
            $api_url = $api_url . '&secret=' . $appsecret;
            $api_url = $api_url . '&js_code=' . $code;
            $api_url = $api_url . '&grant_type=authorization_code';
            testLog($api_url);
            $respond = $client->request('GET', $api_url);
            if ($respond->getStatusCode() === 200) {
                $jscode_res = json_decode($respond->getBody()->getContents(), true);
                if (isset($jscode_res['errcode']) && $jscode_res['errcode'] !== 0) {
                    throw new \Exception('无法获取openid：' . $jscode_res['errmsg']);
                }
                return $jscode_res;
            }
            throw new \Exception('无法获取openid：请求失败');
        } catch (\Exception $exception) {
            throw new \Exception('无法获取openid：' . $exception->getMessage());
        }
    }


    /**
     *  小程序退款
     *
     * @Author hfh_wind
     * @param $payment_info
     * @return bool
     */
    public function dorefund($payment_info)
    {
//        testLog($payment_info);
        if (isset($payment_info['pay_type']) && $payment_info['pay_type'] == 'refund')
        {
            $result['status'] = 'progress';

            $order = [
                'out_trade_no' => $payment_info['payment_id'],
                'out_refund_no' => $payment_info['refund_bn'],
                'total_fee' => $payment_info['total_fee'] * 100,
                'refund_fee' => $payment_info['refund_fee']* 100,
                'refund_desc' => '部分退款',
            ];

            if ($order['total_fee'] == $order['refund_fee'])
            {
                $order['refund_desc'] = '全额退款';
            }
            if(isset($payment_info['refund_desc'])) $order['refund_desc'] = $payment_info['refund_desc'];
            
//            $res = Pay::wechat()->refund($order);
            $res = Pay::wechat($this->payConfig)->refund($order);

            if ($res['return_code'] !='SUCCESS')
            {
                $result['status'] = 'failed';
            }
            else
            {
                $result['status'] = 'succ';
            }
            return $result;
        }


        return false;
    }


}