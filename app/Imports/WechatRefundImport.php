<?php

namespace ShopEM\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use ShopEM\Models\WechatTradeCheck;
use Illuminate\Support\Facades\DB;



class WechatRefundImport implements ToCollection
{
    protected $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params ='')
    {

    }

    public function collection(Collection $rows)
    {
        //如果需要去除表头
        unset($rows[0]);
        unset($rows[1]);
        unset($rows[2]);
        unset($rows[3]);
        unset($rows[4]);
        //$rows 是数组格式
        $this->createData($rows);
    }

    /**
     * 数据存储
     * @Author Huiho
     * @param $rows
     */
    public function createData($rows)
    {
        try
        {
            /**
             * 一.数据重组
             * 二.导入核对
             * 三.重复导入（1.纯重复 ，2.失败重复）
             *
             */
            foreach ($rows as $row)
            {

                $refund_bn = trim($row[2],'`');

                $refund_at = trim($row[4]);

                $check_refund_at = strtotime($refund_at);
                $check_refund_at = date('Y-m-d' ,$check_refund_at);

                $refund_fee = trim($row[5]);
                $payment_id = trim($row[8],'`');
                $trade_fee = trim($row[12]);
                $repeat_data = DB::table('wechat_trade_checks')->where('refund_bn', '=', $refund_bn)->whereIn('deal_status', ['1','3'])->where('import_status', '1')->exists();

                //如果没重复正常
                if (!$repeat_data)
                {
                    $value_data = DB::table('wechat_trade_checks')->where('refund_bn', '=', $refund_bn)->whereIn('status', ['1'])->where('import_status', '1')->exists();

                    if($value_data){
                        //核对操作
                        $check_refund = DB::table('trade_refunds')->where('refund_bn', '=', $refund_bn)->where('refund_fee', '=', $refund_fee)->whereDate('refund_at', '=', $check_refund_at)->exists();

                        //获取商城数据
                        $refundInfo = DB::table('trade_refunds')->where('refund_bn', '=', $refund_bn)->count();

                        if ($check_refund)
                        {
                            $update_data = [
                                'refund_bn' => $refund_bn,
                                'refund_at' => $refund_at,
                                'finish_at' => $refund_at,
                                'deal_status' => '1',
                                'refund_fee' => $refund_fee,
                                'trade_type' => 'REFUND',
                                //'status' => '1',  //成功
                                'handler' => 'PLATFORM',
                            ];

                            DB::table('wechat_trade_checks')->where('payment_id','=',$payment_id)->where('status','=','1')->update($update_data);
//                        DB::table('wechat_trade_checks')->where('deal_status','=','0')->where('payment_id','=',$payment_id)->where('status','=','1')->update($update_data);
                        }
                        else
                        {
                            if ($refundInfo)
                            {
                                $abnormal_reason = 'MISMATCH';  //数据不匹配
                            } else {
                                $abnormal_reason = 'EMPTY';    //数据为空
                            }

                            $update_data = [
                                'refund_bn' => $refund_bn,
                                'refund_at' => $refund_at,
                                'deal_status' => '2',
                                'refund_fee' => $refund_fee,
                                'trade_type' => 'REFUND',
                                'handler' => 'PLATFORM',
                                'abnormal_reason' => $abnormal_reason,
                            ];

                            DB::table('wechat_trade_checks')->where('payment_id','=',$payment_id)->where('status','=','1')->update($update_data);
                        }
                    }
                    else
                    {
                        $create_data = [
                            'trade_type' => 'REFUND',
                            'refund_bn' => $refund_bn,
                            'refund_at' => $refund_at,
                            'finish_at' => $refund_at,
                            'payment_id' => $payment_id,
                            'payed_fee' => $trade_fee,
                            'refund_fee' => $refund_fee,
                            //'shop_id' => '',
                            //'gm_id' => '',
                            //'tid' => '',
                            'import_status' => '0',    //导入失败
                            'handler' => 'PLATFORM',
                            'abnormal_reason' => 'NOT_TRADE',
                        ];
                        WechatTradeCheck::create($create_data);
                    }
                }
                //重复
                else
                {
                    $create_data = [
                        'trade_type' => 'REFUND',
                        'refund_bn' => $refund_bn,
                        'refund_at' => $refund_at,
                        'finish_at' => $refund_at,
                        'payment_id' => $payment_id,
                        'payed_fee' => $trade_fee,
                        'refund_fee' => $refund_fee,
                        //'shop_id' => '',
                        //'gm_id' => '',
                        //'tid' => '',
                        'import_status' => '0',    //导入失败
                        'handler' => 'PLATFORM',
                        'abnormal_reason' => 'REPEAT',
                    ];
                    WechatTradeCheck::create($create_data);
                }
//            $check = WechatRefund::where('payment_id','=',trim($row[1]))->exists();
//            if(!$check)
//            {
//                $import_status = '1' ;
//            }
//            else
//            {
//                $import_status = '2' ;  //重复
//            }
//            $create_data = [
//                'apply_refund_at' => $apply_time,
//                'refund_bn' => $refund_bn,
//                'refund_tid' => $refund_tid,
//                'wehchat_refund_status' => $wehchat_refund_status,
//                'refund_at' => $refund_at,
//                'refund_fee' => $refund_fee,
//                'refund_cash_coupon' => $refund_cash_coupon,
//                'wechat_tid' => $wechat_tid,
//                'payment_id' => $payment_id,
//                'wechat_mch_id' => $wechat_mch_id,
//                'sub_mch_id' => $sub_mch_id,
//                'trade_fee' => $trade_fee,
//                'app_id' => $app_id,
//                'order_user' => $order_user,
//                'refund_account' => $refund_account,
//                'refund_type' => $refund_type,
//                'import_status' => $import_status,
//            ];
//            WechatRefund::create($create_data);
            }
        }
        catch (Exception $e)
        {
            throw new \LogicException($e);
        }
        return true;
    }

}
