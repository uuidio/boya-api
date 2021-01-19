<?php
/**
 * @Filename        IpsWechatPay.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Services\Payment;

use GuzzleHttp\Client;

class IpsWechatPay
{

    public function mwebPay($payment_id)
    {
        $exFields = [
            'clientIp'  => '119.129.114.120',
            'sceneinfo' => '1',
        ];

        $request_params = [
            'account'     => config('ipspay.account'),
            'trxId'       => $payment_id,
            'productType' => 9510,  // MWEB（h5 支付）
            'sceneType'   => 'MWEB',
            'trxDtTm'     => date('Ymd'),
            'trxCcyCd'    => config('ipspay.trxCcyCd'),
            'trxAmt'      => 0.01,
            'notifyUrl'   => config('ipspay.notifyUrl'),
            'expireDtTm'  => date('YmdHis', time() + 3600),
            'goodsDesc'   => 'shopem bbc',
            'extFields'   => $exFields,
        ];
        $request_params = json_encode($request_params);

//        dd($request_params);

        $aes = $this->aes_encrypt($request_params, '9SdKC1x3RTYJJMA8');

//        dd($this->aes_decrypt($aes, '9SdKC1x3RTYJJMA8'));

        $post_data = [
            'version'     => config('ipspay.version'),
            'ts'          => date('YmdHis'),
            'merCode'     => config('ipspay.merCode'),
            'nonceStr'    => config('ipspay.nonceStr'),
            'format'      => 'json',
            'encryptType' => 'AES',
            'signType'    => 'RSA2',
            'data'        => $aes,
        ];

//        dd($post_data);

        $post_sign_data = $this->getSignContent($post_data);
        $post_data['sign'] = $this->createSign($post_sign_data);

        $client = new Client();
        $respond = $client->request('POST', config('ipspay.ips_trade_url'), ['form_params' => $post_data]);


        dd(json_decode($respond->getBody()->getContents(), true));

        return $post_data;
    }

    /**
     * encrypt aes加密
     *
     * @Author moocde <mo@mocode.cn>
     * @param $input
     * @param $key
     * @return string
     */
    public function aes_encrypt($input, $key)
    {
        $data = openssl_encrypt($input, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        $data = bin2hex($data);
        return $data;
    }

    /**
     * decrypt aes解密
     *
     * @Author moocde <mo@mocode.cn>
     * @param $sStr
     * @param $sKey
     * @return string
     */
    public function aes_decrypt($sStr, $sKey)
    {
        $decrypted = openssl_decrypt(hex2bin($sStr), 'AES-128-ECB', $sKey, OPENSSL_RAW_DATA);
        return $decrypted;
    }


    public function createSign($data)
    {
        if (!is_string($data)) {
            return null;
        }

        return openssl_sign(
            $data,
            $sign,
            config('ipspay.private_key'),
            OPENSSL_ALGO_SHA256
        ) ? bin2hex($sign) : null;
    }

    public function getSignContent($params)
    {
        ksort($params);
        return urldecode(http_build_query($params));
    }

    public function checkEmpty()
    {
        return false;
    }

}