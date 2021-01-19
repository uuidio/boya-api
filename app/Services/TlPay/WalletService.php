<?php


namespace ShopEM\Services\TlPay;


use ShopEM\Models\Config;
use ShopEM\Models\Payment;
use ShopEM\Models\UserBindTime;
use ShopEM\Models\UserWallet;
use ShopEM\Services\PaymentService;

class WalletService
{
    private $physicalCardApi;
    public function __construct()
    {
        $this->physicalCardApi = new PhysicalCardApiSdkService();
    }

    public function registerMember($user_id,$mobile)
    {
        $order_id = $user_id.(string)getMicroTime();
        $register = $this->physicalCardApi->pushApi('memberRegister',$order_id,$mobile,$user_id,env('TL_DEFAULT_BRAND_ID'));
        if ($register['code']) return $register;
        if (isset($register['res']['error_response'])) {
            if (strpos($register['res']['error_response']['sub_code'],'1044') !== false) {
                $userInfo = $this->fetchUserInfo($mobile);
                if ($userInfo['code']) {
                    workLogBuilder($register['res']['error_response'],'tl','register_error');
                    return ['code'=>1,'msg'=>'开户失败,请联系管理员','res'=>[]];
                }
                foreach ($userInfo['res'] as $info) {
                    if ($info['open_brh_id'] == env('TL_BRH_ID')) {
                        $register['res']['ppcs_regcifcardopen_add_response']['card_id'] = $info['card_id'];
                        $register['res']['ppcs_regcifcardopen_add_response']['cust_id'] = $info['cust_id'];
                        break;
                    }
                }
                if (!isset($register['res']['ppcs_regcifcardopen_add_response'])) {
                    workLogBuilder($register['res']['error_response'],'tl','register_error');
                    return ['code'=>1,'msg'=>'开户失败,请联系管理员','res'=>[]];
                }
            } else {
                workLogBuilder($register['res']['error_response'],'tl','register_error');
                return ['code'=>1,'msg'=>'开户失败,请联系管理员','res'=>[]];
            }
        }
        try {
            $walletInfo = [
                'user_id'   =>  $user_id,
                'card'      =>  $register['res']['ppcs_regcifcardopen_add_response']['card_id'],
                'tl_id'     =>  $register['res']['ppcs_regcifcardopen_add_response']['cust_id'],
            ];
            UserWallet::create($walletInfo);
            UserBindTime::create([
                'user_id'   =>  $user_id,
                'card'      =>  $register['res']['ppcs_regcifcardopen_add_response']['card_id'],
            ]);
        } catch (\Exception $exception) {
            workLogBuilder([
                'error'         =>  $exception->getMessage(),
                'return_data'   =>  $register['res']['ppcs_regcifcardopen_add_response']
            ],'tl','tl_info_save_error');
            return ['code'=>1,'msg'=>'开户失败,请联系管理员','res'=>[]];
        }
        return ['code'=>0,'msg'=>'开户成功','res'=>$walletInfo];
    }

    public function fetchUserInfo($mobile)
    {
        $userInfo = $this->physicalCardApi->pushApi('fetchUserInfo',$mobile);
        if ($userInfo['code']) return $userInfo;
        if (isset($userInfo['res']['error_response'])) {
            workLogBuilder($userInfo['res']['error_response'],'tl','fetch_user_info_error');
            return ['code'=>1, 'msg'=>'获取失败', 'res'=>[]];
        } else {
            if ($userInfo['res']['ppcs_customerinfoqj_query_response']['customer_info_qj_arrays']){
                return ['code'=>0, 'msg'=>'获取成功', 'res'=>$userInfo['res']['ppcs_customerinfoqj_query_response']['customer_info_qj_arrays']['customer_info_qj']];
            } else {
                return ['code'=>0, 'msg'=>'获取成功','res'=>[]];
            }
        }
    }

    public function getWalletInfo(UserWallet $wallet)
    {
        $time = (string)getMicroTime();
        $physicalCardInfo = $this->physicalCardApi->pushApi('fetchMemberCardInfo', $wallet->tl_id, $wallet->user_id.$time, $wallet->user_id);
        if ($physicalCardInfo['code']) return $physicalCardInfo;
        if (isset($physicalCardInfo['res']['error_response']))
        {
            if ( isset($physicalCardInfo['res']['error_response']['code'])
                && $physicalCardInfo['res']['error_response']['code'] == 12
            ) {
                return [
                    'total'         =>  0,
                    'physical_card' =>  0,
                    'virtual_card'  =>  0,
                    'card_num'      =>  0,
                ];
            }
            return ['code'=>1, 'msg'=> $this->getMsg('获取失败',$res),'res'=>[]];
        }
        $cardList = array_unique(array_column($physicalCardInfo['res']['ppcs_cifcardinfo_get_response']['card_info_qj_arrays']['card_info_qj'],'card_id'));
        $num = count($cardList);
        $physicalCardTotal = array_sum(array_column($physicalCardInfo['res']['ppcs_cifcardinfo_get_response']['card_info_qj_arrays']['card_info_qj'],'valid_balance'));
        $virtualCardTotal = 0;
        return [
            'total'         =>  ($physicalCardTotal + $virtualCardTotal)/100,
            'physical_card' =>  $physicalCardTotal/100,
            'virtual_card'  =>  $virtualCardTotal/100,
            'card_num'      =>  $num,
        ];
    }

    public function getPhysicalCardList(UserWallet $wallet)
    {
        $time = (string)getMicroTime();
        $lists = UserBindTime::where('user_id',$wallet->user_id)->get()->keyBy('card');
        $physicalCardInfo = $this->physicalCardApi->pushApi('fetchMemberCardInfo', $wallet->tl_id, $wallet->user_id.$time, $wallet->user_id);

        if ($physicalCardInfo['code']) return $physicalCardInfo;

        if (isset($physicalCardInfo['res']['error_response']))
        {
            if ( isset($physicalCardInfo['res']['error_response']['code'])
                && $physicalCardInfo['res']['error_response']['code'] == 12
            ) {
                 return ['code'=>0, 'msg'=>'获取成功', 'res'=> [] ];
            }
            return ['code'=>1, 'msg'=> $this->getMsg('获取失败',$res),'res'=>[]];
        }


        foreach ($physicalCardInfo['res']['ppcs_cifcardinfo_get_response']['card_info_qj_arrays']['card_info_qj'] as &$info)
        {
            $info['physical_img'] = Config::getPhysicalImg();
            $info['validity_date'] = date('Y-m-d',strtotime($info['validity_date']));
            $info['valid_balance'] = sprintf('￥ %.2f',$info['valid_balance']/100);
            $info['card_id'] = (string)$info['card_id']; //解决 js精度丢失导致number类型数字末尾变为0
            if (isset($lists[$info['card_id']])) $info['bind_time'] = $lists[$info['card_id']]->created_at->toDateTimeString();
            unset($info);
        }
        return ['code'=>0, 'msg'=>'获取成功', 'res'=>$physicalCardInfo['res']['ppcs_cifcardinfo_get_response']['card_info_qj_arrays']['card_info_qj']];
    }

    public function verifyCardPassword($card,$password)
    {
        $res = $this->physicalCardApi->pushApi('verifyPassword',$card,$password,(string)getMicroTime());
        if ($res['code']) return $res;
        if (isset($res['res']['error_response'])) {
            return ['code'=>1, 'msg'=>'卡号/密码错误'];
        } else {
            return ['code'=>0, 'msg'=>''];
        }
    }

    public function bindCard($card, $user_id)
    {
        $wallet = UserWallet::where('user_id',$user_id)->first();
        $res = $this->physicalCardApi->pushApi('cardAction', $user_id.getMicroTime(), $card, $wallet->tl_id, 'bind');

        if ($res['code']) return $res;
        if (isset($res['res']['error_response'])) {
            return ['code'=>1, 'msg'=> $this->getMsg('绑卡失败',$res) ];
        } else {
            UserBindTime::create([
                'user_id'   =>  $user_id,
                'card'      =>  $card,
            ]);
            return ['code'=>0, 'msg'=>''];
        }
    }

    public function untieCard($card, $user_id)
    {
        $wallet = UserWallet::where('user_id',$user_id)->first();
        $res = $this->physicalCardApi->pushApi('cardAction', $user_id.getMicroTime(), $card, $wallet->tl_id, 'untie');

        if ($res['code']) return $res;
        if (isset($res['res']['error_response'])) {
            return ['code'=>1, 'msg'=>'解绑失败'];
        } else {
            UserBindTime::where('card',$card)->first()->delete();
            return ['code'=>0, 'msg'=>''];
        }
    }

    public function pay(UserWallet $wallet, string $payment_id,string $type = '01')
    {
        $payment = Payment::where('payment_id',$payment_id)->first();
        $userInfo = $this->physicalCardApi->pushApi('fetchMemberCardInfo', $wallet->tl_id, $this->getPid(), $wallet->user_id, 0);

        if ($userInfo['code']) return $userInfo;

        if (isset($userInfo['res']['error_response'])) {
            return ['code'=>1, 'msg'=>'网络故障，请联系管理员'];
        }
        $cardList = $userInfo['res']['ppcs_cifcardinfo_get_response']['card_info_qj_arrays']['card_info_qj'];
        $card_ids = $amounts  = [];
        $amount = $payment->amount * 100;

        $last_names = array_column($cardList,'valid_balance');
        array_multisort($last_names,SORT_ASC,$cardList);

        foreach ($cardList as $key => $value)
        {
            if ($value['valid_balance'] > 0)
            {
                $remainder = $amount - $value['valid_balance'];
                $card_ids[] = $value['card_id'];
                if ($remainder <= 0)
                {
                    $amounts[] = $amount;
                    break; // 当 一张足够满足时，终止循环
                }
                if ($remainder > 0) $amounts[] = $value['valid_balance'];
                $amount = $remainder;
                unset($remainder);
            }
        }

        $num = count($card_ids);
        $data = [
            'order_id'      =>  $payment_id,
            'type'          =>  $type,
            'mer_tm'        =>  date('YmdHis'),
            'mer_order_id'  =>  $payment_id,
            'total_at'      =>  $payment->amount * 100,
            'total_qt'      =>  $num,
            'card_ids'      =>  implode('|',$card_ids),
            'amounts'       =>  implode('|',$amounts),
        ];
        $pay = $this->physicalCardApi->pushApi('pay',$data);
        testLog(['repay'=>$pay]);

        if (isset($pay['res']['error_response'])) {
            return ['code'=>1, 'msg'=>'支付失败：'.$this->getMsg('网络故障',$pay)];
        } elseif ($pay['res']['card_multicardpay_add_response']['pay_result_info']['stat'] == 1) {
            try {
                PaymentService::paySuccess([
                    'trade_no'      =>  $pay['res']['card_multicardpay_add_response']['pay_result_info']['pay_txn_id'],
                    'payment_id'    =>  $payment_id,
                    'pay_app'       =>  'WalletPhysical',
                    'user_id'       =>  $wallet->user_id,
                ]);
            } catch (\Exception $exception) {
                return ['code'=>1, 'msg'=>'支付失败'];
            }
            return ['code'=>0, 'msg'=>'支付成功'];
        } else {
            return ['code'=>1, 'msg'=>'支付失败'];
        }
    }

    public function virtualPay(UserWallet $wallet, string $payment_id,string $type = '01')
    {
        //todo 虚拟卡支付
        return ['code'=>1, 'msg'=>'支付失败'];
    }

    private function getPid()
    {
        $ts = explode(':',date('y:z:H:i:s'));
        $ts[1] = str_pad($ts[1],3,0,STR_PAD_LEFT);
        $seconds = $ts[2]*3600+$ts[3]*60+$ts[4];
        unset($ts[2],$ts[3],$ts[4]);
        $ts[] = $seconds;
        return implode('',$ts);
    }


    private function getMsg($msg,$res)
    {
        if (isset($res['res']['error_response']['sub_msg'])) {
            $sub_msg = $res['res']['error_response']['sub_msg'];
            $msg = str_replace("","业务逻辑错误:",$sub_msg);
        }
        return $msg;
    }
}
