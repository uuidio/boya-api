<?php
/**
 * @Filename Malipay.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services\Payment;


class Malipay
{
    /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public static function setting(){
        $pay_api=array(
            'pay_name'=> 'pay_name',
            'parterID'=>'parterID',
            'key'=>'key',
            'email'=>'email',
        );
        return $pay_api;
    }
}