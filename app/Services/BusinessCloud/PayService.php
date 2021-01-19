<?php


namespace ShopEM\Services\BusinessCloud;


use ShopEM\Models\Payment;
use ShopEM\Models\UserAccount;

class PayService
{
    /**
     * 获取支付配置
     * @param Payment $payment
     * @return array
     */
    public function getPayConfig(Payment $payment)
    {
        $user = UserAccount::find($payment->user_id);
        $sdk = new SdkService();
        $check = $sdk->fetchUserInfo($user);
        if ($check['subCode'] != 'OK') {
            $sdk->createUser($user);
            $sdk->bindOpenid($user);
        }
        $payConfig = $sdk->fetchPayConfig($payment, $user);
        if ($payConfig['subCode'] == 'OK') {
            return $payConfig['data']['weChatAPPInfo'];
        } else {
            return [];
        }
    }
}
