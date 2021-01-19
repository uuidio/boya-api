<?php
/**
 * @Filename StatController.php
 *  统计类报表
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Http\Controllers\Seller\BaseController;
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
        $data['shop_id'] = $this->shop->id;

        $data['tradeFrom'] = isset($data['tradeFrom'])?$data['tradeFrom']:'all'; // 暂时默认全部
        try {

            $pagedata = $statsTrade->getCommonData($data);
            $pagedata['operatTradeData'] = $statsTrade->getOperatData($data);
            $pagedata['operatUserData'] = $statsUserShop->getUserOperatData($data);

        } catch (\LogicException $e) {

            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess($pagedata);
    }




    //交易数据统计
    public function tradeAnalysis(Request $request)
    {
        $statsTrade = new TradeData;
        $data = $request->only('tradeFrom', 'dataType', 'timeType', 'time_start', 'time_end');
        $data['shop_id'] = $this->shop->id;
        try {

            $pagedata = $statsTrade->getShopCommonData($data);

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
        $data['shop_id'] = $this->shop->id;
        try {

            $pagedata = $statGoods->getShopCommonData($data);

        } catch (\LogicException $e) {

            return $this->resFailed(701, $e->getMessage());
        }
        $pagedata['field']=[
//            ['key' => 'shop_name', 'dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['key' => 'goods_id', 'dataIndex' => 'goods_id', 'title' => '商品ID'],
            ['key' => 'title', 'dataIndex' => 'title', 'title' => '商品标题'],
            ['key' => 'pic_path', 'dataIndex' => 'pic_path', 'title' => '图片地址'],
            ['key' => 'cat_name', 'dataIndex' => 'cat_name', 'title' => '所属分类名称'],
            ['key' => 'amountprice', 'dataIndex' => 'amountprice', 'title' => '已卖总金额'],
            ['key' => 'amountnum', 'dataIndex' => 'amountnum', 'title' => '已卖数量'],
//            ['key' => 'refundnum', 'dataIndex' => 'refundnum', 'title' => '退货数量'],
//            ['key' => 'changingnum', 'dataIndex' => 'changingnum', 'title' => '换货数量'],
        ];
        return $this->resSuccess($pagedata);
    }

}