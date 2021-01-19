<?php

/**
 * 订单报表数据
 * TradeData.php
 * @Author: nlx
 * @Date:   2019-10-09 11:15:26
 */

namespace ShopEM\Services\Stats;

use ShopEM\Models\StatShopTradeStatics;
use ShopEM\Services\Stats\CommonData;
use ShopEM\Models\StatPlatformTradeStatics;

class TradeData
{
    public function __construct()
    {
        // parent::__construct();
        $this->com_service = new CommonData();
    }

    protected $selectTimeType = [
        'byday'   => '86400',
        'byweek'  => '604800',
        'bymonth' => '2952000'
    ];
    protected $selectStatus = [
        'byday'   => false,
        'byweek'  => false,
        'bymonth' => false
    ];

    protected $trade_num = [
        'new_trade'      => '新增订单数',
        'refunds_num'    => '已退款的订单数量',
        'complete_trade' => '已完成订单数量'
    ];
    protected $trade_money = [
        'new_fee'      => '新增订单额',
        'refunds_fee'  => '已退款的订单额',
        'complete_fee' => '已完成订单额'
    ];

    //给经营概况统计数据
    public function getOperatData($data)
    {
        if (isset($data['timeType'])) {
            $timeRange = $this->com_service->getTimeRangeByType($data['timeType']);
            //$timeRange = $this->_getTimeRangeByType($data['timeType']);
            $timeStart = $timeRange['time_start'];
            $timeEnd = $timeRange['time_end'];
        } else {
            $timeStart = $this->com_service->getTime($data['time_start']);
            $timeEnd = $this->com_service->getTime($data['time_end']);
        }

        $mdlDesktopTradeStat = new StatPlatformTradeStatics;

        $tradeFrom = $data['tradeFrom'] ? $data['tradeFrom'] : 'all';
        $filter = array(
            ['created_at', '>=', $timeStart],
            ['created_at', '<', $timeEnd],
            ['stats_trade_from', '=', $tradeFrom],
        );
        $gm_id = isset($data['gm_id'])? $data['gm_id'] : 0;
        if ($gm_id > 0) 
        {
            $filter = array_merge($filter,[['gm_id','=',$gm_id]]);
        }
        
        $fileds = ['complete_fee', 'refunds_fee', 'new_fee', 'new_trade', 'refunds_num', 'complete_trade'];
        $tradeData = $mdlDesktopTradeStat::where($filter)
            ->select($fileds)
            ->orderBy('created_at')
            ->get()
            ->toArray();

        $operatData = [];
        foreach ($tradeData as $key => $value) {
            foreach ($value as $k => $v) {
                if (!isset($operatData[$k])) {
                    $operatData[$k] = 0;
                }

                $operatData[$k] += $v;
            }
        }
        //echo '<pre>';print_r($operatData);exit();
        return $operatData;
    }

    //获取公共数据
    /**
     * data  页面传过来的数据
     * @return array
     */
    public function getCommonData($data)
    {
        if (isset($data['time_start']) || isset($data['time_end'])) {
            if (strtotime($data['time_start']) >= strtotime($data['time_end'])) {
                throw new \LogicException('开始时间必须小于结束时间');
            }
        }
        if (isset($data['timeType'])) {
            $timeRange = $this->com_service->getTimeRangeByType($data['timeType']);
            $timeStart = strtotime($timeRange['time_start']);
            $timeEnd = strtotime($timeRange['time_end']);
            //echo '<pre>';print_r($timeRange);exit();
        } else {
            $timeStart = strtotime($data['time_start']);
            $timeEnd = strtotime($data['time_end']);
        }
        $selectTimeType = isset($data['selectTimeType']) ? $data['selectTimeType'] : 'byday';//时间跨度
        //获取时间段
//        $categories = $this->com_service->getCategories($timeStart,$timeEnd,$selectTimeType);
//        $pagedata['timeRange'] = $categories;
        // $pagedata['timeRange'] = $categories;
//dd(11);
        //获取交易数据
        $dataType = isset($data['dataType']) ? $data['dataType'] : 'num';//类型（money  or  num）
        $tradeFrom = isset($data['tradeFrom']) ? $data['tradeFrom'] : 'all';//终端（all,pc,wap）

        $gm_id = isset($data['gm_id'])? $data['gm_id'] : 0;
        $tradeInfo = $this->_getTradeData($dataType, $timeStart, $timeEnd, $tradeFrom, $selectTimeType, $gm_id);
//        $tradeData = $this->_getSeriesData($tradeInfo['tradeInfo'],$dataType); //订单类型分组
//        dd($tradeData);
        //echo '<pre>';print_r($tradeInfo);exit();
        $pagedata['tradeData'] = $tradeInfo;
        //$pagedata['selectStatus'] = $tradeInfo['selectStatus'];
        // $pagedata['tradeData'] = $tradeData;

        if ($dataType == 'num') {
            // $pagedata['typeData'] = json_encode("数量");
            $pagedata['typeDataText'] = "数量";
        } else {
            // $pagedata['typeData'] = json_encode("金额");
            $pagedata['typeDataText'] = "金额";
        }
        $pagedata['time_start'] = date('Y-m-d', $timeStart);
        $pagedata['time_end'] = date('Y-m-d', $timeEnd);
        return $pagedata;
    }


    /**
     *  获取店铺公共数据
     *
     * @Author hfh_wind
     * @param $data
     * @return mixed
     */
    public function getShopCommonData($data)
    {
        if (isset($data['time_start']) || isset($data['time_end'])) {
            if (strtotime($data['time_start']) >= strtotime($data['time_end'])) {
                throw new \LogicException('开始时间必须小于结束时间');
            }
        }
        if (isset($data['timeType'])) {
            $timeRange = $this->com_service->getTimeRangeByType($data['timeType']);
            $timeStart = strtotime($timeRange['time_start']);
            $timeEnd = strtotime($timeRange['time_end']);

        } else {
            $timeStart = strtotime($data['time_start']);
            $timeEnd = strtotime($data['time_end']);
        }
        $selectTimeType = isset($data['selectTimeType']) ? $data['selectTimeType'] : 'byday';//时间跨度

        //获取交易数据
        $dataType = isset($data['dataType']) ? $data['dataType'] : 'num';//类型（money  or  num）
        $tradeFrom = isset($data['tradeFrom']) ? $data['tradeFrom'] : 'all';//终端（all,pc,wap）
        $shop_id = $data['shop_id'];
        $tradeInfo = $this->_getShopTradeData($dataType, $timeStart, $timeEnd, $tradeFrom, $selectTimeType, $shop_id);
//        $tradeData = $this->_getSeriesData($tradeInfo['tradeInfo'],$dataType); //订单类型分组
//        dd($tradeData);
        //echo '<pre>';print_r($tradeInfo);exit();
        $pagedata['tradeData'] = $tradeInfo;

        if ($dataType == 'num') {
            $pagedata['typeDataText'] = "数量";
        } else {
            $pagedata['typeDataText'] = "金额";
        }
        $pagedata['time_start'] = date('Y-m-d', $timeStart);
        $pagedata['time_end'] = date('Y-m-d', $timeEnd);
        return $pagedata;
    }


    /**
     * @brief  重新组织交易数据给报表
     * $tradeInfo 已经查询出来的交易数据 array
     * $dataType 数据类型  是件数num,还是钱money,string
     * @return array
     */
    private function _getSeriesData($tradeInfo, $dataType)
    {
        if ($dataType == 'num') {
            $lineText = $this->trade_num;
        }
        if ($dataType == 'money') {
            $lineText = $this->trade_money;
        }

        foreach ($lineText as $key => $value) {
            $data[$key]['name'] = $value;
            foreach ($tradeInfo as $k => $v) {
//dd($tradeInfo);
                $data[$key]['data'][$k]['count'] = (double)$tradeInfo[$k][$key] ? (double)$tradeInfo[$k][$key] : 0;
                $data[$key]['data'][$k]['time'] = $tradeInfo[$k]['created_at'] ? $tradeInfo[$k]['created_at'] : 0;
            }
        }

        foreach ($data as $key => $value) {
            $tradeData[] = $value;
        }

        return $tradeData;
    }

    /**
     * @brief  获取交易数据
     * $dataType 数据类型  是件数num,还是钱money,string
     * $timeStart 查询的开始时间 2015-03-01
     * $timeEnd 查询的结束时间2015-03-03
     * $tradeFrom 来自哪个终端(all,pc,wap) string
     * $selectTimeType 时间跨度选择器（按天，按周，按月）
     * @return array
     */
    private function _getTradeData($dataType, $timeStart, $timeEnd, $tradeFrom, $selectTimeType = null, $gm_id=0)
    {
        $mdlDesktopTradeStat = new StatPlatformTradeStatics;

        $filter = array(
            ['created_at', '>=', date('Y-m-d H:i:s', $timeStart)],
            ['created_at', '<', date('Y-m-d H:i:s', $timeEnd + 1)],
            ['stats_trade_from', '=', $tradeFrom],
        );
        if ($gm_id>0) 
        {
            $filter = array_merge($filter,[['gm_id', '=', $gm_id]]);
        }
        
        if ($dataType == 'num') {
            $fileds = ['new_trade', 'refunds_num', 'complete_trade', 'created_at'];
        } else {
            $fileds = ['new_fee', 'refunds_fee', 'complete_fee', 'created_at'];
        }
        $tradeData = $mdlDesktopTradeStat::where($filter)
            ->select($fileds)
            ->orderBy('created_at')
            ->get()
            ->toArray();
        //echo '<pre>';print_r($tradeData);exit();
        //补充数据——交易数据报表 没有天数的数据
        $tradeAddData = $this->dataAdd($tradeData, $dataType, $timeStart, $timeEnd, $selectTimeType);

        $trade['tradeInfo'] = $tradeAddData['tradeInfo'];

        return $trade;
    }


    /**
     * @brief  获取店铺交易数据
     * $dataType 数据类型  是件数num,还是钱money,string
     * $timeStart 查询的开始时间 2015-03-01
     * $timeEnd 查询的结束时间2015-03-03
     * $tradeFrom 来自哪个终端(all,pc,wap) string
     * $selectTimeType 时间跨度选择器（按天，按周，按月）
     * @return array
     */
    private function _getShopTradeData($dataType, $timeStart, $timeEnd, $tradeFrom, $selectTimeType = null, $shop_id)
    {
        $mdlShopTradeStat = new StatShopTradeStatics();

        $filter = array(
            ['created_at', '>=', date('Y-m-d H:i:s', $timeStart)],
            ['created_at', '<', date('Y-m-d H:i:s', $timeEnd + 1)],
//            ['stats_trade_from','=',$tradeFrom],
            ['shop_id', '=', $shop_id],
        );

        if ($dataType == 'num') {
            $fileds = [
                'new_trade',
                'ready_trade',
                'alreadytrade',
                'ready_send_trade',
                'already_send_trade',
                'cancle_trade',
                'complete_trade',
                'refund_trade',
                'reject_trade',
                'changing_trade',
                'created_at'
            ];
        } else {
            $fileds = [
                'new_fee',
                'ready_fee',
                'alreadyfee',
                'ready_send_fee',
                'already_send_fee',
                'cancle_fee',
                'complete_fee',
                'refund_fee',
                'reject_fee',
                'total_refund_fee',
                'created_at'
            ];
        }
        $tradeData = $mdlShopTradeStat::where($filter)
            ->select($fileds)
            ->orderBy('created_at')
            ->get()
            ->toArray();

        //补充数据——交易数据报表 没有天数的数据
        $tradeAddData = $this->dataAdd($tradeData, $dataType, $timeStart, $timeEnd, $selectTimeType, $type = 'shop');

        $trade['tradeInfo'] = $tradeAddData['tradeInfo'];

        return $trade;
    }


    /**
     * @brief  补充数据——交易数据报表
     * $tradeData 已经查询出来的交易数据 array
     * $dataType 数据类型  是件数num,还是钱money,string
     * $timeStart 查询的开始时间 2015-03-01
     * $timeEnd 查询的结束时间2015-03-03
     * $selectTimeType 时间跨度选择器（按天，按周，按月）
     * @return
     */
    public function dataAdd($tradeData, $dataType, $timeStart, $timeEnd, $selectTimeType, $type = '')
    {   
        $tradeInfo = [];
        //把时间作为键
        foreach ($tradeData as $key => $value) {
            foreach ($value as $k => $v) {
                $tradeInfo[date('Y-m-d', strtotime($value['created_at']))][$k] = $v;
            }
        }
        //获取时间段数组
        $categories = $this->com_service->getCategories($timeStart, $timeEnd);
        //echo '<pre>';print_r($categories);exit();
        //给没有数据的天数添加默认数据
        foreach ($categories as $key => $value) {
            if ($type == 'shop') {
                if (!isset($tradeInfo[$value]) && $dataType == 'num') {
                    $tradeInfo[$value]['new_trade'] = 0;
                    $tradeInfo[$value]['ready_trade'] = 0;
                    $tradeInfo[$value]['alreadytrade'] = 0;
                    $tradeInfo[$value]['ready_send_trade'] = 0;
                    $tradeInfo[$value]['already_send_trade'] = 0;
                    $tradeInfo[$value]['cancle_trade'] = 0;
                    $tradeInfo[$value]['complete_trade'] = 0;
                    $tradeInfo[$value]['refund_trade'] = 0;
                    $tradeInfo[$value]['reject_trade'] = 0;
                    $tradeInfo[$value]['changing_trade'] = 0;
                    $tradeInfo[$value]['created_at'] = $value;

                }
                if (!isset($tradeInfo[$value]) && $dataType == 'money') {
                    $tradeInfo[$value]['new_trade'] = 0;
                    $tradeInfo[$value]['ready_trade'] = 0;
                    $tradeInfo[$value]['alreadytrade'] = 0;
                    $tradeInfo[$value]['ready_send_trade'] = 0;
                    $tradeInfo[$value]['already_send_trade'] = 0;
                    $tradeInfo[$value]['cancle_trade'] = 0;
                    $tradeInfo[$value]['complete_trade'] = 0;
                    $tradeInfo[$value]['refund_trade'] = 0;
                    $tradeInfo[$value]['reject_trade'] = 0;
                    $tradeInfo[$value]['changing_trade'] = 0;
                    $tradeInfo[$value]['created_at'] = $value;
                }
            }
        }

        //排序
        $createtime = array();
        foreach ($tradeInfo as $trade) {
            $createtime[] = $trade['created_at'];
        }

        array_multisort($createtime, SORT_ASC, $tradeInfo);
        //echo '<pre>';print_r($tradeInfo);exit();
        $tradeList = $this->getTradeList($tradeInfo, $selectTimeType);
        // $data['selectStatus'] = $this->selectStatus;
        $data['tradeInfo'] = $tradeList;


        return $data;
    }


    //时间跨度处理
    public function getTradeList($tradeInfo, $selectTimeType)
    {
        if ($selectTimeType == 'byday') {
            return $tradeInfo;
        }
        if ($selectTimeType == 'byweek') {
            $tradeList = array_chunk($tradeInfo, 7);
        }
        if ($selectTimeType == 'bymonth') {
            $tradeList = array_chunk($tradeInfo, 30);
        }
        $tradedata = $this->getTradeArray($tradeList);
        //echo '<pre>';print_r($tradedata);exit();
        return $tradedata;
    }

    //数据组织
    public function getTradeArray($data)
    {

        foreach ($data as $key => $value) {
            foreach ($value as $k => $v) {
                $selectTime = date("Y-m-d", $value[0]['created_at']) . '/' . date("Y-m-d",
                        $value[count($value) - 1]['created_at']);

                $trade[$selectTime]['new_trade'] += $v['new_trade'];
                $trade[$selectTime]['refunds_num'] += $v['refunds_num'];
                $trade[$selectTime]['complete_trade'] += $v['complete_trade'];
            }
        }
        return $trade;

    }


    //异步加载时间跨度选择器
    public function ajaxTimeType($postdata)
    {
        $timeStart = strtotime($postdata['time_start']);
        $timeEnd = strtotime($postdata['time_end']);
        $poorTime = $timeEnd - $timeStart;//时间差
        if ($poorTime < $this->selectTimeType['byweek']) {
            $this->selectStatus['byday'] = true;
        } elseif ($poorTime < $this->selectTimeType['bymonth']) {
            $this->selectStatus['byday'] = true;
            $this->selectStatus['byweek'] = true;
        } elseif ($poorTime > $this->selectTimeType['bymonth']) {
            $this->selectStatus['byday'] = true;
            $this->selectStatus['byweek'] = true;
            $this->selectStatus['bymonth'] = true;
        }
        $pagedata = $this->selectStatus;
        //echo '<pre>';print_r($pagedata);exit();
        return $pagedata;
    }


}