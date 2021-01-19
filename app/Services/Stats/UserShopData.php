<?php

/**
 * 会员、商家统计数据
 * UserShopData.php
 * @Author: nlx
 * @Date:   2019-10-09 11:15:38
 */
namespace ShopEM\Services\Stats;

use Illuminate\Support\Facades\DB;
use ShopEM\Services\Stats\CommonData;
use ShopEM\Models\StatPlatformUser;
use ShopEM\Models\StatPlatformUserOrder;

class UserShopData
{
	public function __construct()
    {
        // parent::__construct();
        $this->com_service = new CommonData();
    }

     protected $user_num = [
        'accountuser'=>'会员总数',
        'newuser'=>'新增会员数',
    ];
    protected $shop_num = [
        'selleraccount'=>'商家总数',
        'sellernum'=>'新增商家数',
    ];


     //给经营概况统计数据
    public function getUserOperatData($data)
    {
        if(isset($data['timeType']))
        {
            $timeRange = $this->com_service->getTimeRangeByType($data['timeType']);
            //$timeRange = $this->_getTimeRangeByType($data['timeType']);
            $timeStart = $timeRange['time_start'];
            $timeEnd = $timeRange['time_end'];
        }
        else
        {
            $timeStart = $this->com_service->getTime($data['time_start']);
            $timeEnd = $this->com_service->getTime($data['time_end']);
        }
        $mdlDesktopStatUser = new StatPlatformUser;

        $filter = array(
            ['created_at','>=',$timeStart],
            ['created_at','<',$timeEnd],
        );
        $gm_id = isset($data['gm_id'])?$data['gm_id']:0;
        if ($gm_id > 0) 
        {
            $filter = array_merge($filter,[['gm_id','=',$gm_id]]);
        }
        $fileds = ['newuser','accountuser','sellernum','selleraccount'];

        $statUserData = $mdlDesktopStatUser::where($filter)
	                    ->select($fileds)
	                    ->orderBy('created_at')
	                    ->get()
	                    ->toArray();

        $operatData = [];            
        foreach ($statUserData as $key => $value)
        {
            foreach ($value as $k => $v)
            {
            	if (!isset($operatData[$k])) $operatData[$k] = 0;
                $operatData[$k] += $v;
            }
            $operatData['accountuser'] = $value['accountuser'];
            $operatData['selleraccount'] = $value['selleraccount'];
        }
        //echo '<pre>';print_r($operatData);exit();
        return $operatData;
    }
     /**
      * 会员排行
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
            $timeStart = strtotime($timeRange['time_start']);
            $timeEnd = strtotime($timeRange['time_end']);
        }
        else
        {
            $timeStart = strtotime($data['time_start']);
            $timeEnd = strtotime($data['time_end']);
        }

        $dataType = isset($data['dataType'])?$data['dataType']:'num';
        $limit = isset($data['userLimit'])?$data['userLimit']:5;
        $gm_id = isset($data['gm_id'])? $data['gm_id'] : 0;

        //获取店铺排行数据
        $userListInfo = $this->_getUserListData($dataType,$timeStart,$timeEnd,$limit,$gm_id);

        if($dataType=='num')
        {
            $pagedata['typeDataText'] = "数量";
        }
        else
        {
            $pagedata['typeDataText'] = "金额";
        }
       //echo '<pre>';print_r($tradeData);exit();
        $pagedata['userListData'] = $userListInfo;
        $pagedata['time_start'] = date('Y-m-d',$timeStart);
        $pagedata['time_end'] = date('Y-m-d',$timeEnd);
        //echo '<pre>';print_r($pagedata);exit();
        return $pagedata;
    }


    /**
     * @brief  获取交易数据
     * $dataType 数据类型  是件数num,还是钱money,string
     * $timeStart 查询的开始时间 2015-03-01
     * $timeEnd 查询的结束时间2015-03-03
     * 
     * @return array
     */
    private function _getUserListData($dataType,$timeStart,$timeEnd,$limit=0,$gm_id=0)
    {
        if($dataType=='num')
        {
            $orderBy = 'userordernum';
        }
        if($dataType=='money')
        {
            $orderBy = 'userfee';
        }

        if(!$limit)
        {
            $limit = -1;
        }
        $fileds = ['user_id','username','userordernum','userfee','experience','created_at'];

        $model = StatPlatformUserOrder::where('created_at', '>=', date('Y-m-d H:i:s',$timeStart))
		                ->where('created_at', '<',date('Y-m-d H:i:s',$timeEnd+1))
		                ->select(DB::raw('any_value(user_id) as user_id,any_value(user_name) as user_name,sum(userfee) as userfee,sum(userordernum) as userordernum'));
		if($gm_id>0)
        {
            $model = $model->where('gm_id','=', $gm_id);
        }                 
		if ($limit>0) 
		{
		    $model = $model->offset(0)->limit($limit);
		}                
		$userListData = $model->orderByDesc($orderBy)->groupBy('user_id')->get();

    
        return $userListData;
    }


}