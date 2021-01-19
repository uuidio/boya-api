<?php

namespace ShopEM\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Excel;
use ShopEM\Models\TradeRefunds;
use ShopEM\Models\WechatTradeCheck;


class WechatTradeImport implements ToCollection
{
    protected $time;
    protected $gm_id;

    public function __construct($time = '',$gm_id = 0)
    {
        $this->time = $time;
        $this->gm_id = $gm_id;
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
     * @param $rows
     * @return bool
     */
    public function createData($rows)
    {
        DB::beginTransaction();
        try {
            /**
             * 一.数据重组
             * 二.导入对账
             * 三.重复导入（1.纯重复 ，2.失败重复）
             *
             */
            $abnormal = $success_num = $error_num = 0;
            $abnormal_ids = [];
            $cache = [1 => [], 2 => []];
            foreach ($rows as $row) {
                //日期转化（可能会相差几秒）
                //$row[0] = intval((trim($row[0]) - 25569) * 3600 * 24);
                //$time =  gmdate('Y-m-d H:i:s', $row[0]);
                //$time =  gmdate('Y-m-d', $row[0]);
                $time = trim($row[0]);
                $time = gmdate('Y-m-d H:i:s', intval(($time - 25569) * 3600 * 24));
                $check_time = strtotime($time);
                $check_time = date('Y-m-d', $check_time);

                $trade_no = trim($row[1], '`');  // 微信支付单号
                $payment_id = trim($row[2], '`');  // 商户订单号
                $scenes = trim($row[3]);
                $trade_no = trim($trade_no);
                $payment_id = trim($payment_id);
                $wehchat_trade_status = trim($row[4]);  // 交易类型
                $trade_fee = trim($row[5]); // 交易金额

                $trade_type = '';
                if (strstr($wehchat_trade_status,'已支付')) {
                    $trade_type = 'TRADE';
                }
                if (strstr($wehchat_trade_status,'退款')) {
                    $trade_type = 'REFUND';
                }
                if (empty($trade_type)) {
                    $abnormal ++;
                    $abnormal_ids[] = $payment_id;
                    continue;
                }

                switch ($trade_type) {  //  支付单对账的粒度是tid，交易金额=所有同支付单的支付金额之和，退款单对账的粒度是oid，交易金额=退款金额
                    case 'TRADE':
                        $datas = WechatTradeCheck::where('payment_id',$payment_id)
                            ->where('trade_no',$trade_no)
                            ->where('trade_type',$trade_type)
                            ->select('id','payment_id','payed_fee','trade_type')->get();

                        if (count($datas) == 0) {  // 数据库记录不存在，对账失败
//                            $data = [
//                                'trade_type'        =>  $trade_type,
//                                'payed_fee'         =>  $trade_fee,
//                                'trade_no'          =>  $trade_no,
//                                'payment_id'        =>  $payment_id,
//                                'trade_at'          =>  $check_time,
//                                'status'            =>  2,
//                                'deal_status'       =>  4,
//                                'abnormal_reason'   =>  'MISMATCH',
//                            ];
//                            $res = WechatTradeCheck::create($data);
//                            $cache[2][] = $res->id;
                            $abnormal ++;
                            $abnormal_ids[] = $payment_id;
                            continue;
                        }


                        $import = [];
                        $sum = 0;
                        foreach ($datas as $data) {
                            $sum += $data->payed_fee;
                            $import[] = $data->id;
                        }

                        if ($scenes !== '公众号支付') {
                            WechatTradeCheck::where('payment_id',$payment_id)->update([
                                'status'    =>  2,
                                'deal_status'    =>  4,
                                'check_at'  =>  now()->toDateTimeString(),
                                'abnormal_reason'   =>  'MISMATCH',
                            ]);
                            $cache[2] = array_merge($cache[2],$import);
                            $error_num ++;
                            continue;
                        }
                        if ($sum == $trade_fee) {

                            WechatTradeCheck::where('payment_id',$payment_id)->update([
                                'status'    =>  1,
                                'deal_status'    =>  1,
                                'check_at'  =>  now()->toDateTimeString(),
                                'abnormal_reason'   =>  '',
                            ]);
                            $cache[1] = array_merge($cache[1],$import);
                            $success_num ++;

                        } else {

                            WechatTradeCheck::where('payment_id',$payment_id)->update([
                                'status'    =>  2,
                                'deal_status'    =>  4,
                                'check_at'  =>  now()->toDateTimeString(),
                                'abnormal_reason'   =>  'MISMATCH_AMOUNT',
                            ]);
                            $cache[2] = array_merge($cache[2],$import);
                            $error_num ++;

                        }
                        break;

                    case 'REFUND':
                        $data = WechatTradeCheck::where('payment_id',$payment_id)
                            ->where('trade_no',$trade_no)
                            ->where('trade_type',$trade_type)
                            ->where('refund_fee',$trade_fee)
                            ->select('id','payment_id','payed_fee','trade_type')->first();
                        if (empty($data)) {  // 数据库记录不存在，对账失败
//                            $data = [
//                                'trade_type'        =>  $trade_type,
//                                'refund_fee'         =>  $trade_fee,
//                                'trade_no'          =>  $trade_no,
//                                'payment_id'        =>  $payment_id,
//                                'refund_at'          =>  $check_time,
//                                'status'            =>  2,
//                                'deal_status'       =>  4,
//                                'abnormal_reason'   =>  'MISMATCH',
//                            ];
//                            $res = WechatTradeCheck::create($data);
//                            $cache[2][] = $res->id;
                            $abnormal ++;
                            $abnormal_ids[] = $payment_id;
                            continue;
                        } else {
                            if ($scenes !== '公众号支付') {
                                $data->status = 2;
                                $data->deal_status = 4;
                                $data->check_at = now()->toDateTimeString();
                                $data->abnormal_reason = 'MISMATCH';
                                $data->save();
                                $error_num ++;
                                $cache[2][] = $data->id;
                                continue;
                            } else {
                                $data->status = 1;
                                $data->deal_status = 1;
                                $data->check_at = now()->toDateTimeString();
                                $data->abnormal_reason = '';
                                $data->save();
                                $success_num ++;
                                $cache[1][] = $data->id;
                            }
                        }
                        break;
                }


            }
            foreach ($cache as $key => $value) {
                Cache::put($key.'_wechat_trade_import_'.$this->gm_id,implode(',',$value));
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            storageLog('对账导入error：' . $e->getMessage());
            throw new \LogicException($e);
        }
        if($this->time) {
            Redis::set($this->time, json_encode(['success' => $success_num, 'error' => $error_num, 'abnormal' => $abnormal, 'abnormal_ids' => implode(',',$abnormal_ids)]));
        }
        return true;
    }
}
