<?php

namespace ShopEM\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Concerns\ToCollection;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeRefundLog;
use ShopEM\Models\TradeRefunds;
use ShopEM\Models\WechatTradeCheck;

class WechatProjectImport implements ToCollection
{
    protected $fields;
    protected $headData;
    protected $data;
    protected $time;
    protected $gm_id;

    public function __construct($time = '',$gm_id = 0)
    {
        $this->fields = [
            '项目名称' => 'gm_id',
            '店铺名称' => 'shop_id',
            '交易类型' => 'trade_type',
            '订单编号' => 'tid',
            '子订单号' => 'oid',
            '退款申请编号' => 'refund_bn',
            '支付金额' => 'payed_fee',
            '退款金额' => 'refund_fee',
        ];
        $this->time = $time;
        $this->gm_id = $gm_id;
    }

    public function collection(Collection $rows)
    {
        $this->handleHeadData($rows[0]); // 处理表头
        //如果需要去除表头
        unset($rows[0]);
        $rows = $this->isNullArray($rows); // 过滤空数组
        $this->handleData($rows); // 处理数据
        // 数据导入
        $this->createData();
        return 123;
    }

    public function createData()
    {
        DB::beginTransaction();
        try {
            $repeat = $abnormal = $success_num = $error_num = 0;
            $repeat_ids = $abnormal_ids = [];
            $cache = [1 => [], 2 => []];
            foreach ($this->data as $key => $row) {
                if (trim($row['trade_type']) == '交易') {
                    $trade_type = 'TRADE';
                } else {
                    $trade_type = 'REFUND';
                }

                switch ($trade_type) {
                    case 'TRADE':
                        $data = WechatTradeCheck::where('tid',$row['tid'])
                            ->where('trade_type',$trade_type)
                            ->select('id','status','deal_status')
                            ->first();

                        if ($data) {
                            if ($data->status == 1 && $data->deal_status == 1) {
                                $data->status = 3;
                                $data->deal_status = 2;
                                $data->deal_at = now()->toDateTimeString();
                                $data->abnormal_reason = '';
                                $data->save();
                                $cache[1][] = $data->id;
                                $success_num ++;
                            } elseif ($data->deal_status == 2 || $data->deal_status == 5) {
                                $repeat ++;
                                $repeat_ids[] = $row['tid'];
                                break;
                            } else {
                                $data->status = 4;
                                $data->deal_status = 4;
                                $data->deal_at = now()->toDateTimeString();
                                $data->abnormal_reason = 'MISMATCH';
                                $data->save();
                                $error_num ++;
                                $cache[2][] = $data->id;
                            }
                        } else {
//                            $data = [
//                                'trade_type'        =>  $trade_type,
//                                'payment_id'        =>  '0000000000',
//                                'payed_fee'         =>  $row['payed_fee'],
//                                'tid'               =>  $row['tid'],
//                                'status'            =>  4,
//                                'deal_status'       =>  4,
//                                'abnormal_reason'   =>  'MISMATCH',
//                            ];
//                            $res = WechatTradeCheck::create($data);
//                            $cache[2][] = $res->id;
                            $abnormal ++;
                            $abnormal_ids[] = $row['tid'];
                        }
                        break;

                    case 'REFUND':
                        $data = WechatTradeCheck::where('tid',$row['tid'])
                            ->where('trade_type',$trade_type)
                            ->where('oid',$row['oid'])
                            ->select('id','status','deal_status')
                            ->first();

                        if ($data) {
                            if ($data->status == 1 && $data->deal_status == 1) {
                                $data->status = 3;
                                $data->deal_status = 2;
                                $data->deal_at = now()->toDateTimeString();
                                $data->abnormal_reason = '';
                                $data->save();
                                $success_num ++;
                                $cache[1][] = $data->id;
                            } elseif ($data->deal_status == 2 || $data->deal_status == 5) {
                                $repeat ++;
                                $repeat_ids[] = $row['tid'];
                                break;
                            } else {
                                $data->status = 4;
                                $data->deal_status = 4;
                                $data->deal_at = now()->toDateTimeString();
                                $data->abnormal_reason = 'MISMATCH';
                                $data->save();
                                $error_num ++;
                                $cache[2][] = $data->id;
                            }
                        } else {
//                            $data = [
//                                'trade_type'        =>  $trade_type,
//                                'payment_id'        =>  '0000000000',
//                                'payed_fee'         =>  $row['payed_fee'],
//                                'tid'               =>  $row['tid'],
//                                'oid'               =>  $row['oid'],
//                                'refund_bn'         =>  $row['refund_bn'],
//                                'refund_fee'        =>  $row['refund_fee'],
//                                'status'            =>  4,
//                                'deal_status'       =>  4,
//                                'abnormal_reason'   =>  'MISMATCH',
//                            ];
//                            $res = WechatTradeCheck::create($data);
//                            $cache[2][] = $res->id;
                            $abnormal_ids[] = $row['tid'];
                            $abnormal ++;
                        }
                        break;
                }
            }
            foreach ($cache as $key => $value) {
                Cache::put($key.'_wechat_project_import_'.$this->gm_id,implode(',',$value));
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            storageLog('项目对账error:' . $e->getMessage());
            return false;
        }

        if($this->time) {
            Redis::set($this->time, json_encode(['success' => $success_num, 'error' => $error_num, 'abnormal' => $abnormal, 'abnormal_ids' => implode(',',$abnormal_ids), 'repeat' => $repeat, 'repeat_ids' => implode(',',$repeat_ids)]));
        }
        return true;
    }

    /**
     * 处理表头
     * @Author RJie
     * @param $row
     */
    public function handleHeadData($row)
    {
        $this->headData = [];
        foreach ($row as $k => $r) {
            foreach ($this->fields as $_r => $_k) {
                if (trim($r) == $_r) {
                    $this->headData[$k] = $_k;
                }
            }
        }
    }


    /**
     * 处理数据
     * @Author RJie
     * @param $rows
     */
    public function handleData($rows)
    {
        $this->data = [];
        foreach ($rows as $key => $value) {
            foreach ($value as $_key => $_val) {
                $this->data[$key][$this->headData[$_key]] = $_val;
            }
        }
    }

    /**
     * 过滤空数组
     * @Author RJie
     * @param $array
     * @return array
     */
    public function isNullArray($array)
    {
        $arr = [];
        foreach ($array as $row) {
            $isNull = array_filter(json_decode(json_encode($row), true));
            if (count($isNull) <= 0) continue;

            $arr[] = $row;
        }
        return $arr;
    }
}
