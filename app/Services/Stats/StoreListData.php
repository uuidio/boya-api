<?php

/**
 * StoreListData.php
 * 店铺数据统计
 * @Author: nlx
 * @Date:   2019-10-11 15:04:08
 */
namespace ShopEM\Services\Stats;

use Illuminate\Support\Facades\DB;
use ShopEM\Services\Stats\CommonData;
use ShopEM\Models\StatPlatformShop;
use ShopEM\Models\GmPlatform;
class StoreListData
{
	
	public function __construct()
	{
		$this->com_service = new CommonData();
	}
     /**
     * 获取公共数据
     * data  页面传过来的数据
     * @return array
     */
    public function getCommonData($data)
    {
    	if (isset($data['time_start']) || isset($data['time_end'])) 
        {
            if(strtotime($data['time_start'])>=strtotime($data['time_end']))
            {
                throw new \LogicException('开始时间必须小于结束时间');
            }
        }

        if(isset($data['timeType']))
        {
            $timeRange = $this->com_service->getTimeRangeByType($data['timeType']);
            //$timeRange = $this->_getTimeRangeByType($data['timeType']);
            $timeStart = strtotime($timeRange['time_start']);
            $timeEnd = strtotime($timeRange['time_end']);
        }
        else
        {
            $timeStart = strtotime($data['time_start']);
            $timeEnd = strtotime($data['time_end']);
        }

        $dataType = isset($data['dataType'])?$data['dataType']:'num';
        $limit = isset($data['storeLimit'])?$data['storeLimit']:5;
        $shopName = isset($data['shopname'])?$data['shopname']:'';
        $gm_id = isset($data['gm_id'])?$data['gm_id']:0;

        if($dataType=='num')
        {
            $pagedata['typeDataText'] = "数量";
        }
        else
        {
            $pagedata['typeDataText'] = "金额";
        }
        //获取店铺排行数据
        $storeListInfo = $this->_getStoreListData($dataType,$timeStart,$timeEnd,$shopName,$limit,$gm_id);
       //echo '<pre>';print_r($tradeData);exit();
        $pagedata['storeListData'] = $storeListInfo;
        $pagedata['time_start'] = date('Y-m-d',$timeStart);
        $pagedata['time_end'] = date('Y-m-d',$timeEnd);
        return $pagedata;
    }

    /**
     * @brief  获取交易数据
     * @param $dataType     数据类型  是件数num,还是钱money,string
     * @param $timeStart    查询的开始时间 2015-03-01
     * @param $timeEnd      查询的结束时间2015-03-03
     * @param $shopName     模糊搜索店铺名
     * @param $limit
     * @return mixed
     */
    private function _getStoreListData($dataType,$timeStart,$timeEnd,$shopName,$limit,$gm_id=0)
    {
        if($dataType=='num')
        {
            $orderBy = 'shopaccountnum';
        }
        if($dataType=='money')
        {
            $orderBy = 'shopaccountfee';
        }

        if(!$limit)
        {
            $limit = -1;
        }
        $fileds = ['shop_id','shopname','shopaccountfee','shopaccountnum','created_at'];
        //echo '<pre>';print_r($orderBy);exit();

        $model = StatPlatformShop::where('created_at', '>=', date('Y-m-d H:i:s',$timeStart))
		                ->where('created_at', '<',date('Y-m-d H:i:s',$timeEnd));

		if (!empty(trim($shopName))) 
		{
		    $model = $model->where('shopname','like', '%'.trim($shopName).'%');
		}
        if($gm_id>0)
        {
            $model = $model->where('gm_id','=', $gm_id);
        }                
		if ($limit>0) 
		{
		    $model = $model->offset(0)->limit($limit);
		}                
		$storeListData = $model->select(
						DB::raw('any_value(shopname) as shopname, sum(shopaccountfee) as shopaccountfee,sum(shopaccountnum) as shopaccountnum,gm_id'))
						->orderByDesc($orderBy)
						->groupBy('shop_id')
						->get();

        if($gm_id == 0)
        {
             // 增加项目名称
            foreach ($storeListData as $key => $value) {
                $gm_name = GmPlatform::select('platform_name')->find($value->gm_id);
                if(empty($gm_name)){
                    $gm_name['platform_name'] = '';
                }
                $storeListData[$key]['gm_name'] = $gm_name['platform_name'];
            }
        }
       
        return $storeListData;
    }


}