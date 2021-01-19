<?php

/**
 * GoodsListData.php
 * 商品数据报表
 * @Author: nlx
 * @Date:   2019-10-11 15:41:54
 */
namespace ShopEM\Services\Stats;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\StatShopItemStatics;
use ShopEM\Services\Stats\CommonData;
use ShopEM\Models\StatPlatformItemStatics;
use ShopEM\Models\GmPlatform;

class GoodsListData
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
            $timeStart = strtotime($timeRange['time_start']);
            $timeEnd = strtotime($timeRange['time_end']);
        }
        else
        {
            $timeStart = strtotime($data['time_start']);
            $timeEnd = strtotime($data['time_end']);
        }
        $catId = isset($data['cat_id'])?$data['cat_id']:0;
        $title = isset($data['title'])?$data['title']:'';

        $dataType = isset($data['dataType'])?$data['dataType']:'num';
        $limit = isset($data['storeLimit'])?$data['storeLimit']:5;
        $gm_id = isset($data['gm_id'])? $data['gm_id'] : 0;
        //获取商品排行数据
        $goodsListInfo = $this->_getStoreListData($dataType,$timeStart,$timeEnd,$title,$limit,$catId,$gm_id);
        
        //获取商品类目排行
        $catNameListData = $this->_getCatName($gm_id);
       //echo '<pre>';print_r($tradeData);exit();
        $pagedata['goodsListData'] = $goodsListInfo;
        $pagedata['catListData'] = $catNameListData;
        $pagedata['time_start'] = date('Y-m-d',$timeStart);
        $pagedata['time_end'] = date('Y-m-d',$timeEnd);
        //echo '<pre>';print_r($pagedata);exit();
        return $pagedata;
    }



    /**
     * 获取店铺公共数据
     * data  页面传过来的数据
     * @return array
     */
    public function getShopCommonData($data)
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
        $catId = isset($data['cat_id'])?$data['cat_id']:0;
        $title = isset($data['title'])?$data['title']:'';

        $dataType = isset($data['dataType'])?$data['dataType']:'num';
        $limit = isset($data['storeLimit'])?$data['storeLimit']:5;
        $shop_id=$data['shop_id'];
        $gm_id = isset($data['gm_id'])? $data['gm_id'] : 0;
        //获取商品排行数据
        $goodsListInfo = $this->_getShopStoreListData($dataType,$timeStart,$timeEnd,$title,$limit,$catId,$shop_id,$gm_id);
        //获取商品类目排行
        $catNameListData = $this->_getCatName($gm_id);
        //echo '<pre>';print_r($tradeData);exit();
        $pagedata['goodsListData'] = $goodsListInfo;
        $pagedata['catListData'] = $catNameListData;
        $pagedata['time_start'] = date('Y-m-d',$timeStart);
        $pagedata['time_end'] = date('Y-m-d',$timeEnd);
        //echo '<pre>';print_r($pagedata);exit();
        return $pagedata;
    }

    /**
     * @brief  获取所有类目
     * 
     * @return array
     */
    private function _getCatName($gm_id=0)
    {
        $mdlDesktopItemStat = new StatPlatformItemStatics;
        $fileds = ['cat_id','cat_name'];
        if ($gm_id>0) 
        {
            $mdlDesktopItemStat = $mdlDesktopItemStat::where('gm_id',$gm_id);
        }
        $catListData = $mdlDesktopItemStat->select($fileds)->get()->toArray();
        if(count($catListData)>0){
            $catNameList = $this->array_unique_fb($catListData);
        }else{
            $catNameList=[];
        }
        //echo '<pre>';print_r($catNameList);exit();
        return $catNameList;
    }

    //数组去掉重复值
    public function array_unique_fb($array2D)
    {

        foreach ($array2D as $v)
        {
            $v=join(',',$v);  //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
            $temp[]=$v;
        }
        $temp=array_unique($temp);    //去掉重复的字符串,也就是重复的一维数组

        foreach ($temp as $k => $v)
        {
            $array=explode(',',$v); //再将拆开的数组重新组装
            
            $cat[$k]['cat_id'] =$array[0];
            $cat[$k]['cat_name'] =$array[1];

        }
        return $cat;
    }

    /**
     * @brief  获取交易数据
     * @Author Administrator
     * @param $dataType     数据类型  是件数num,还是钱money,string
     * @param $timeStart    查询的开始时间 2015-03-01
     * @param $timeEnd      查询的结束时间2015-03-03
     * @param $title        商品名称
     * @param $limit
     * @param $catId
     * @return mixed
     */
    private function _getStoreListData($dataType,$timeStart,$timeEnd,$title,$limit,$catId,$gm_id=0)
    {
        if($dataType=='num')
        {
            $orderBy = 'amountnum';
        }
        if($dataType=='money')
        {
            $orderBy = 'amountprice';
        }
        if(!$limit)
        {
            $limit = -1;
        }
        $fileds = 'shop_id,item_id,title,pic_path,shop_name,amountnum,amountprice,cat_id,cat_name,createtime';
        //echo '<pre>';print_r($orderBy);exit();

        $model = StatPlatformItemStatics::where('created_at', '>=', date('Y-m-d H:i:s',$timeStart))
		                ->where('created_at', '<',date('Y-m-d H:i:s',$timeEnd));

		if ($catId>0) 
		{
		    $model = $model->where('cat_id','=',intval($catId));
		} 
		if (!empty(trim($title))) 
		{
		    $model = $model->where('title','like', '%'.trim($title).'%');
		}
        if($gm_id>0)
        {
            $model = $model->where('gm_id','=', $gm_id);
        }                
		if ($limit>0) 
		{
		    $model = $model->offset(0)->limit($limit);
		}                
		$goodsListData = $model->select(
						DB::raw('gm_id,any_value(shop_name) as shop_name,any_value(title) as title,any_value(pic_path) as pic_path,any_value(goods_id) as goods_id,any_value(cat_id) as cat_id,any_value(cat_name) as cat_name, sum(amountprice) as amountprice, sum(amountnum) as amountnum'))
						->orderByDesc($orderBy)
						->groupBy('goods_id')
						->get()
						->toArray();
        
        if($gm_id == 0)
        {
             // 增加项目名称
            foreach ($goodsListData as $key => $value) {
                $gm_name = GmPlatform::select('platform_name')->find($value['gm_id']);
                if(empty($gm_name)){
                    $gm_name['platform_name'] = '';
                }
                $goodsListData[$key]['gm_name'] = $gm_name['platform_name'];
            }
        }   
		// foreach ($goodsListData as $key => $value)
  //       {
  //           $goodsListData[$key]['itemUrl'] = url::action("topc_ctl_item@index",array('item_id'=>$value['item_id']));
  //       }
        //echo '<pre>';print_r($goodsListData);exit();
        return $goodsListData;
    }




    /**
     * @brief  获取店铺交易数据
     * @Author Administrator
     * @param $dataType     数据类型  是件数num,还是钱money,string
     * @param $timeStart    查询的开始时间 2015-03-01
     * @param $timeEnd      查询的结束时间2015-03-03
     * @param $title        商品名称
     * @param $limit
     * @param $catId
     * @return mixed
     */
    private function _getShopStoreListData($dataType,$timeStart,$timeEnd,$title,$limit,$catId,$shop_id,$gm_id=0)
    {
        if($dataType=='num')
        {
            $orderBy = 'amountnum';
        }
        if($dataType=='money')
        {
            $orderBy = 'amountprice';
        }
        if(!$limit)
        {
            $limit = -1;
        }


        $model = StatShopItemStatics::where('created_at', '>=', date('Y-m-d H:i:s',$timeStart))
            ->where('created_at', '<',date('Y-m-d H:i:s',$timeEnd))->where('shop_id','=',$shop_id);

        if ($catId>0)
        {
            $model = $model->where('cat_id','=',intval($catId));
        }
        if (!empty(trim($title)))
        {
            $model = $model->where('title','like', '%'.trim($title).'%');
        }
        if($gm_id>0)
        {
            $model = $model->where('gm_id','=', $gm_id);
        }
        if ($limit>0)
        {
            $model = $model->offset(0)->limit($limit);
        }
        $goodsListData = $model->select(
            DB::raw('any_value(title) as title,any_value(pic_path) as pic_path,any_value(goods_id) as goods_id, sum(amountprice) as amountprice, sum(amountnum) as amountnum,sum(refundnum) as refundnum ,sum(changingnum) as changingnum'))
            ->orderByDesc($orderBy)
            ->groupBy('goods_id')
            ->get()
            ->toArray();

        return $goodsListData;
    }
}