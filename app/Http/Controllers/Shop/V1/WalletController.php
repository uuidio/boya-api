<?php

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Controller;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\TlBindCardRequest;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\UserWallet;
use ShopEM\Services\TlPay\PhysicalCardApiSdkService;
use ShopEM\Services\TlPay\WalletService;

class WalletController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function memberInfo()
    {
        if ($wallet = UserWallet::where('user_id',$this->user->id)->first()) {
            return $this->resSuccess((new WalletService())->getWalletInfo($wallet));
        } else {
            return $this->resFailed(500,'还未开通电子钱包');
        }
    }

    public function registerMember()
    {
        $register = (new WalletService())->registerMember($this->user->id,$this->user->mobile);
        if ($register['code']) return $this->resFailed(500,$register['msg']);
        return $this->resSuccess();
    }

    public function bindCard(TlBindCardRequest $request)
    {
        $wallet = new WalletService();
        $check = $wallet->verifyCardPassword($request->get('card'),$request->get('password'));
        if ($check['code']) return $this->resFailed(500,$check['msg']);
        if (request('mode','bind') == 'bind') {
            $res = $wallet->bindCard($request->get('card'),$this->user->id);
        } else {
            $res = $wallet->untieCard($request->get('card'),$this->user->id);
        }
        if ($res['code']) {
            return $this->resFailed(500,$res['msg']);
        }
        return $this->resSuccess();
    }

    public function physicalList()
    {
        $wallet = UserWallet::where('user_id',$this->user->id)->first();
        if ($wallet) {
            $physicalCardInfo = (new WalletService())->getPhysicalCardList($wallet);
            if ($physicalCardInfo['code']) return $this->resFailed(500,$physicalCardInfo['msg']);
            return $this->resSuccess($physicalCardInfo['res']);
        } else {
            return $this->resSuccess([]);
        }
    }

    public function fetchPushLog()
    {
        $key = '1tb4QnQfWiZg6S6JLQsnWeAQgjKYwwqUEOYbWvsPGYeKTzwUWCRotNMLQQzgZeOqYGtvXQDRuHvakWSxd1pC9dXYLFLUXh5SapPs';
        $time = request('time','');
        $random = request('ran','');
        $sign = request('sign','');
        if (empty($time) || empty($random) || empty($sign))  return $this->resSuccess(false);
        $ran = mb_substr($random,0,2);
        $use_key = mb_substr($key,$ran,6);
        $str = md5($time).md5($random).md5($use_key);
        if (md5($str) != $sign) {
            return $this->resSuccess(false);
        }
        if ($time = request('date','')) {
            return $this->resSuccess(workLogParser("tl/push_$time.log",request('filter_v',''), request('filter_k',''), request('fetch','')));
        } else {
            return $this->resSuccess(workLogParser('tl/push_'.date('Ymd').'.log',request('filter_v',''), request('filter_k',''), request('fetch','')));
        }
    }

    public function payOnline($app)
    {
        if (empty($payment_id = request('payment_id','')) || empty($app)) return $this->resFailed(414,'参数有误！');

        $wallet = UserWallet::where('user_id',$this->user->id)->first();
        switch ($app)
        {
            case 'physical':
                $res = (new WalletService())->pay($wallet,$payment_id);
                break;

            case 'virtual':
                $res = (new WalletService())->virtualPay($wallet,$payment_id);
                break;

            default:
                return $this->resFailed(500,'支付方式有误');
        }
        if ($res['code']) {
            return $this->resFailed(500,$res['msg']);
        }
        return $this->resSuccess();
    }

    public function history()
    {
        $wallet = UserWallet::where('user_id',$this->user->id)->first();
        $page = request('page',1);
        $begin = request('begin',date('Ymd'));
        $end = request('end',date('Ymd',strtotime('-3 month')));
        $list = (new PhysicalCardApiSdkService())->pushApi('payRecord', $wallet->tl_id ,$page,$begin,$end);
        if (isset($list['res']['error_response'])) {
            if (strpos($list['res']['error_response']['sub_msg'], '查询结果为空') !== false) {
                return $this->resSuccess([
                    'total' =>  0,
                    'page'  =>  1,
                    'last_page' => 1,
                    'data'  =>  [],
                ]);
            } else {
                return $this->resFailed(500, '网络故障，请联系管理员');
            }
        } else {

            $txn_log = $list['res']['ppcs_txnlog_search_response']['txn_log_arrays']['txn_log'];

            $payment_ids_arr = array_column($txn_log,'access_ref_seq_id');
            $trade_arr = TradePaybill::whereIn('payment_id',$payment_ids_arr)->get()->toArray();
            $payment_trade = [];
            foreach ($trade_arr as $key => $value)
            {
                $payment_trade[$value['payment_id']][] = $value['tid'];
            }
            $datas = [];
            foreach ($txn_log as $key => $value)
            {
                $data = [];
                $data['trade_time'] = date('Y-m-d H:i:s',strtotime( $value['int_txn_dt'].$value['int_txn_tm'] ));
                $data['payment_id'] = $value['access_ref_seq_id'];
                $data['is_online_trade'] = isset($payment_trade[$value['access_ref_seq_id']])? true : false;

                $amount = floatval($value['txn_at']/100);
                // $data['amount'] = $this->_getTxnCdTexT($value['txn_cd'],true).$amount;
                $data['amount'] = $amount;
                $trade_type_text = $data['is_online_trade'] ? '线上商城-' : '线下实体店-';
                $data['trade_type_text'] = $trade_type_text.$this->_getTxnCdTexT($value['txn_cd']);
                $data['trade_ids'] = $payment_trade[$value['access_ref_seq_id']]??[];

                $datas[] = $data;
            }


            return $this->resSuccess([
                'total' =>  $list['res']['ppcs_txnlog_search_response']['total'],
                'page'  =>  $page,
                'last_page' => ceil($list['res']['ppcs_txnlog_search_response']['total'] / 20),
                'data'  =>  $datas,
            ]);
        }
    }

    private function _getTxnCdTexT($txn_cd, $use_symbol=false)
    {
        $symbol = '+';
        switch ($txn_cd) {
            case 'B0010':
                $txn_cd_text = '消费';
                $symbol = '-';
                break;
            case 'B0011':
                $txn_cd_text = '消费撤销';
                break;
            case 'B0012':
                $txn_cd_text = '消费冲正';
                break;
            case 'B0013':
                $txn_cd_text = '消费撤销冲正';
                break;
            case 'B0020':
                $txn_cd_text = '网上支付';
                $symbol = '-';
                break;
            case 'B0065':
                $txn_cd_text = '提现';
                $symbol = '-';
                break;
            case 'B0071':
                $txn_cd_text = '退货申请确认';
                break;
            case 'B0171':
                $txn_cd_text = '直接退货';
                break;
            case 'B0073':
                $txn_cd_text = '直接退货';
                break;
            case 'C0010':
                $txn_cd_text = '充值';
                break;
            default:
                $txn_cd_text = '使用';
                break;
        }
        if ($use_symbol) return $symbol;

        return $txn_cd_text;
    }
}
