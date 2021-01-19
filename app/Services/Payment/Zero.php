<?php
/**
 * @Filename Zero.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Services\Payment;

use ShopEM\Models\UserDeposit;
use ShopEM\Models\UserDepositLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Payment;
use ShopEM\Models\SecKillOrder;
use ShopEM\Services\PaymentService;

class Zero
{


    /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public static function setting()
    {
        $pay_api = array(
            'pay_name' => '0元支付',
        );
        return $pay_api;
    }


    /**
     *
     * @Author hfh_wind
     * @return array
     */
    public static function dopay($params)
    {

        $fee = $params['payment_info']->amount;
        $payment_id = $params['payment_info']->payment_id;

        if ($fee != 0) {
            throw new \Exception('支付单异常,支付金额异常!');
        }

        $payment_data['pay_app'] = 'Zero';

        if ($params['pay_type'] == 'Recharge') {
            $payment_data['payment_id'] = $payment_id;
            $payment_data['recharge'] = true;
        } else {
            $payment_data['payment_id'] = $payment_id;
        }
        $payment_data['user_id']=$params['user_id'];
        //回写订单状态
        PaymentService::paySuccess($payment_data, '');

        return ['res'=>'6','msg'=>'支付成功!'];
    }


    /**
     * @Author hfh_wind
     *
     * 提交退款支付信息的接口
     * @param array $payment 提交信息的数组
     * @return mixed false or null
     */
    public function dorefund($payment)
    {
        $fee = $payment['refund_fee'];

        if ($fee != 0) {
            throw new \Exception('支付单异常,退款金额异常!');
        }
        return 'succ';
    }
}