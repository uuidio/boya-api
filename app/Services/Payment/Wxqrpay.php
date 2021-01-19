<?php
/**
 * @Filename Wxqrpay.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services\Payment;


class Wxqrpay
{


    /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public static function setting(){
        $pay_api=array(
            'pay_name'=> 'pay_name',
            'appId'=>'appId',
            'Mchid'=>'Mchid',
            'Key'=>'Key',
            'Appsecret'=>'Appsecret',
            'apiclient_cert'=>'apiclient_cert', //证书
            'apiclient_key'=>'apiclient_key',
        );
        return $pay_api;
    }
}