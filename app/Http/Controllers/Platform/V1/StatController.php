<?php

/**
 * StatController.php
 * 报表模块
 * @Author: nlx
 * @Date:   2019-10-11 11:02:28
 */
namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use Illuminate\Http\Request;

use ShopEM\Services\Stats\UserShopData;
use ShopEM\Services\Stats\TradeData;
use ShopEM\Services\Stats\StoreListData;
use ShopEM\Services\Stats\GoodsListData;

class StatController extends BaseController
{
    //经营概况
    public function analysis(Request $request)
    {
        $statsTrade = new TradeData;
        $statsUserShop = new UserShopData;

        $data = $request->only('tradeFrom', 'dataType', 'timeType', 'time_start', 'time_end');

        $data['tradeFrom'] = isset($data['tradeFrom'])?$data['tradeFrom']:'all'; // 暂时默认全部
        try {
            $data['gm_id'] = $this->GMID;
            $pagedata = $statsTrade->getCommonData($data);
            $pagedata['operatTradeData'] = $statsTrade->getOperatData($data);
            $pagedata['operatUserData'] = $statsUserShop->getUserOperatData($data);

        } catch (\LogicException $e) {

            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess($pagedata);
    }


    // 会员排行
    public function userAnalysis(Request $request)
    {
        $statsUserShop = new UserShopData;
        $data = $request->only('userLimit', 'dataType', 'timeType', 'time_start', 'time_end');
        try {
            $data['gm_id'] = $this->GMID;
            $pagedata = $statsUserShop->getCommonData($data);

        } catch (\LogicException $e) {

            return $this->resFailed(701, $e->getMessage());
        }

        $pagedata['field']=[
            ['key' => 'user_id', 'dataIndex' => 'user_id', 'title' => '会员id'],
            ['key' => 'user_name', 'dataIndex' => 'user_name', 'title' => '会员名称'],
            ['key' => 'userfee', 'dataIndex' => 'userfee', 'title' => '下单额'],
            ['key' => 'userordernum', 'dataIndex' => 'userordernum', 'title' => '下单量'],
        ];

        return $this->resSuccess($pagedata);
    }

    //交易数据统计
    public function tradeAnalysis(Request $request)
    {
        $statsTrade = new TradeData;
        $data = $request->only('tradeFrom', 'dataType', 'timeType', 'time_start', 'time_end');
        try {
            $data['gm_id'] = $this->GMID;
            $pagedata = $statsTrade->getCommonData($data);

        } catch (\LogicException $e) {

            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess($pagedata);
    }

    //异步加载时间跨度选择器
    public function timeType(Request $request)
    {
        $statsTrade = new TradeData;
        $data = $request->only('time_start', 'time_end');
        try {

            $pagedata = $statsTrade->ajaxTimeType($data);

        } catch (\LogicException $e) {

            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess($pagedata);
    }

    //店铺数据统计
    public function storeListAnalysis(Request $request)
    {
        $statStore = new StoreListData;
        $data = $request->only('storeLimit', 'dataType', 'timeType', 'time_start', 'time_end', 'shopname');
        try {
            $data['gm_id'] = $this->GMID;
            $pagedata = $statStore->getCommonData($data);

        } catch (\LogicException $e) {

            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess($pagedata);
    }

    //商品数据统计
    public function goodsListAnalysis(Request $request)
    {
        $statGoods = new GoodsListData;
        $data = $request->only('storeLimit', 'dataType', 'timeType', 'time_start', 'time_end', 'cat_id', 'title');
        try {
            $data['gm_id'] = $this->GMID;
            $pagedata = $statGoods->getCommonData($data);

        } catch (\LogicException $e) {

            return $this->resFailed(701, $e->getMessage());
        }
        $pagedata['field']=[
            ['key' => 'goods_id', 'dataIndex' => 'goods_id', 'title' => '商品ID'],
            ['key' => 'shop_name', 'dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['key' => 'title', 'dataIndex' => 'title', 'title' => '商品标题'],
            ['key' => 'pic_path', 'dataIndex' => 'pic_path', 'title' => '图片地址'],
            ['key' => 'cat_name', 'dataIndex' => 'cat_name', 'title' => '所属分类名称'],
            ['key' => 'amountprice', 'dataIndex' => 'amountprice', 'title' => '已卖总金额'],
            ['key' => 'amountnum', 'dataIndex' => 'amountnum', 'title' => '已卖数量'],
        ];
        return $this->resSuccess($pagedata);
    }
}