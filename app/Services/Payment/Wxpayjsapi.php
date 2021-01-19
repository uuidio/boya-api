<?php
/**
 * @Filename        Wxpayjsapi.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services\Payment;

use Pay;

class Wxpayjsapi
{

    /**
     * 后台配置参数设置
     *
     * @param null
     * @return array 配置参数列表
     */
    public static function setting()
    {
        $pay_api = [
            'pay_name'       => 'pay_name',
            'appId'          => 'appId',
            'Mchid'          => 'Mchid',
            'Key'            => 'Key',
            'Appsecret'      => 'Appsecret',
            'apiclient_cert' => 'apiclient_cert', //证书
            'apiclient_key'  => 'apiclient_key',
        ];
        return $pay_api;
    }

    /**
     * 生成微信支付H5 JS PAY
     *
     * @Author moocde <mo@mocode.cn>
     * @param $payment_info
     * @return string
     */
    public static function makeJsPay($payment_info)
    {
        $data = [
            'out_trade_no' => $payment_info['payment_id'],
            'body'         => $payment_info['body'],
            'total_fee'    => $payment_info['amount'] * 100,
            'openid'       => $payment_info['open_id'],
        ];

        $return_url = env('PAY_RETURN_URL') . '/payment/status/' . $payment_info['payment_id'];

        $result = Pay::wechat()->mp($data);
        $result = json_encode($result);

        $pay_html = '
                <html>
                    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
                    <title>微信安全支付</title>
                    <script language="javascript">
                                //调用微信JS api 支付
                                function jsApiCall()
                                {
                                    WeixinJSBridge.invoke(
                                        "getBrandWCPayRequest",
                                        ' . $result . ',
                                        function(res){
                                            if(res.err_msg == "get_brand_wcpay_requset:ok"){
                                                window.location.href = "' . $return_url . '";
                                            }
                                            window.location.href = "' . $return_url . '";
                                        }
                                    );
                                }

                                function callpay()
                                {
                                    if (typeof WeixinJSBridge == "undefined"){
                                        if( document.addEventListener ){
                                            document.addEventListener("WeixinJSBridgeReady", jsApiCall, false);
                                        }else if (document.attachEvent){
                                            document.attachEvent("WeixinJSBridgeReady", jsApiCall);
                                            document.attachEvent("onWeixinJSBridgeReady", jsApiCall);
                                        }
                                    }else{
                                        jsApiCall();
                                    }
                                }
                                callpay();
                    </script>
                    <body>
                 <button type="button" onclick="callpay()" style="display:none;">微信支付</button>
                 </body>
                </html>
            ';

        echo $pay_html;
        exit;
        return $pay_html;
    }

}