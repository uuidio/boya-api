<?php
/**
 * @Filename Deposit.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services\Payment;

use ShopEM\Models\UserDeposit;
use ShopEM\Models\UserDepositLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Payment;
use ShopEM\Models\SecKillOrder;
use ShopEM\Services\PaymentService;

class Deposit
{


    /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public static function setting()
    {
        $pay_api = array(
            'pay_name' => '余额',
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
        $userId = $params['user_id'];
        $password = $params['pay_password'];

        $flag = UserDeposit::where(['user_id' => $userId])->first();
        if(empty($flag)){
            $re_params=['res'=>'0','msg'=>'请设置支付密码!'];
            return $re_params;
        }

        //确认支付密码是否正确
        if (!isset($params['pay_password'])) {
            $re_params=['res'=>'1','msg'=>'请输入支付密码!'];
            return $re_params;
        }



        if (!Hash::check($password, $flag->password)) {
            $re_params=['res'=>'2','msg'=>'支付密码错误!'];
            return $re_params;
//            throw new \LogicException('支付密码错误!');
        }

        if ($params['payment_info']->amount > $flag->deposit) {
            $re_params=['res'=>'3','msg'=>'预存款不足请充值!'];
            return $re_params;
//            throw new \LogicException('预存款不足请充值!');
        }

        $fee = ($params['payment_info']->amount < 0)?0:$params['payment_info']->amount;
        $payment_id = $params['payment_info']->payment_id;

        //避免支付单金额为负数的时候也完成支付
        if ($fee < 0) {
            throw new \Exception('支付单异常,支付金额为负数!');
        }

        DB::beginTransaction();
        try {

//            DB::update('UPDATE em_user_deposits SET deposit = deposit - ? WHERE user_id = ? and deposit >= ?', [$fee, $userId, $fee]);
            $update_res = UserDeposit::where(['user_id' => $userId])->where('deposit', '>=', $fee)->decrement('deposit', $fee);
            if (!$update_res) {
                throw new \Exception('余额不足,预存款扣减失败!');
            }
            //记录预存款操作
            $deposit_log['user_id'] = $params['user_id'];
            $deposit_log['operator'] = '会员';
            $deposit_log['fee'] = $fee;
            $deposit_log['type'] = 'expense';
            $deposit_log['message'] = '消费扣款支付单号' . $payment_id;

            UserDepositLog::create($deposit_log);


            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();
            throw new \Exception('预存款扣减失败!');
        }

        $payment_data['pay_app'] = 'Deposit';

        if ($params['pay_type'] == 'Recharge') {
            $payment_data['payment_id'] = $payment_id;
            $payment_data['recharge'] = true;
        } else {
            $payment_data['payment_id'] = $payment_id;
        }
        $payment_data['user_id']=$params['user_id'];
        //回写订单状态
        PaymentService::paySuccess($payment_data, '');

        //变更秒杀订单状态
        $change =SecKillOrder::where(['payment_id'=>$payment_id])->count();
        if($change){
            SecKillOrder::where(['payment_id'=>$payment_id])->update(['state'=>'1']);
        }


        return ['res'=>'6','msg'=>'支付成功!'];
    }


    /**
     * @Author hfh_wind
     *
     * 提交退款支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dorefund($payment)
    {
        $fee = ($payment['refund_fee'] < 0)?0:$payment['refund_fee'];
        $paymentInfo = Payment::where(['payment_id' => $payment['payment_id']])->first();
        $userId = $paymentInfo->user_id;
        $ret = [];
        DB::beginTransaction();
        try {
//            DB::insert('UPDATE em_user_deposits SET deposit = deposit + ? WHERE user_id = ?', [$fee, $userId]);
            UserDeposit::where(['user_id' => $userId])->increment('deposit', $fee);
            //记录预存款操作
            $deposit_log['user_id'] = $userId;
            $deposit_log['operator'] = '会员';
            $deposit_log['fee'] = $fee;
            $deposit_log['type'] = 'add';
            $deposit_log['message'] = '退款返还支付单号' . $paymentInfo->payment_id;

            $res = UserDepositLog::create($deposit_log);
        } catch (\Exception $e) {

            DB::rollBack();
            throw new \Exception('退款预存款返回失败!' . $e->getMessage());
        }
        DB::commit();

        if ($res) {
            $ret['status'] = 'succ';
        } else {
            $ret['status'] = 'failed';
        }
        return $ret;
    }

    public function recharge($payment_id,$amount)
    {
        $fee = ($amount < 0)?0:$amount;
    }
}