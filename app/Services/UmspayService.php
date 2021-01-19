<?php
/**
 * @Filename        UmspayService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class UmspayService
{
//    protected static $umspayUrl = 'https://qr-test2.chinaums.com/netpay-portal/webpay/pay.do?';
    protected static $umspayUrl = 'https://qr.chinaums.com/netpay-portal/webpay/pay.do?';

    protected static $umspayConfig = [
        'msgId'     => '3770',
        'msgSrc'    => 'WWW.SZSRRSSY.COM',
        'mid'       => '898440351983824',
        'tid'       => '38241001',
        'instMid'   => 'YUEDANDEFAULT',
        'systemId'  => 'systemId',
        'notifyUrl' => 'http://o2o.sdjchina.com/umspay/notify',
        'returnUrl' => 'http://o2o.sdjchina.com/umspay/return',
        'key'       => 'fHGdaR5feXHPKKWAdQ5Dm3A6shNzxYRK3mDzFQhzJCiCDc5W',
    ];


    /**
     * 生成银联支付地址
     *
     * @Author moocde <mo@mocode.cn>
     * @return string
     */
    public static function makePaylink($trade_info)
    {
        $umspayData = [
            'msgId'            => self::$umspayConfig['msgId'],
            'msgSrc'           => self::$umspayConfig['msgSrc'],
            'msgType'          => 'WXPay.jsPay',
            'requestTimestamp' => date('Y-m-d H:i:s'),
            'merOrderId'       => self::$umspayConfig['msgId'] . $trade_info['payment_info']->payment_id,
            'mid'              => self::$umspayConfig['mid'],
            'tid'              => self::$umspayConfig['tid'],
            'instMid'          => self::$umspayConfig['instMid'],
            'notifyUrl'        => self::$umspayConfig['notifyUrl'],
            'returnUrl'        => self::$umspayConfig['returnUrl'],
            'systemId'         => self::$umspayConfig['systemId'],
            'totalAmount'      => $trade_info['payment_info']->amount * 100,
            'originalAmount'   => $trade_info['payment_info']->amount * 100,
//            'goods'            => self::paymentGoods($trade_info['trade_order']),
        ];

        ksort($umspayData);
        $umspayData['sign'] = strtoupper(md5(urldecode(http_build_query($umspayData)) . self::$umspayConfig['key']));
//        Cache::put('order_' . $umspayData['merOrderId'], $umspayData['sign'], Carbon::now()->addMinute(10));

        return self::$umspayUrl . http_build_query($umspayData);
    }

    /**
     * 订单商品信息
     *
     * @Author moocde <mo@mocode.cn>
     * @param $order_info
     * @return false|string
     */
    public static function paymentGoods($order_info)
    {
        $goods = [];
        foreach ($order_info as $item) {
            $goods[] = [
                "body"          => $item->goods_name,
                "goodsCategory" => $item->gc_name,
                "goodsId"       => $item->goods_id,
                "goodsName"     => $item->goods_name,
                "price"         => $item->amount * 100,
                "quantity"      => $item->quantity,
            ];
        }

        return json_encode($goods, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 验证银联返回数据sign是否正确
     *
     * @Author moocde <mo@mocode.cn>
     * @param $data
     * @return bool
     */
    public static function checkReturnSign($data)
    {
        testLog($data);
        $retrunSign = $data['sign'];
        unset($data['sign']);
        unset($data['s']);
        ksort($data);
        $sign = strtoupper(md5(urldecode(http_build_query($data)) . self::$umspayConfig['key']));
        testLog($sign);

        return $retrunSign == $sign;
    }
}