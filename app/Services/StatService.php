<?php
/**
 * @Filename        StatService.php
 *  统计报表
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */
namespace ShopEM\Services;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\StatPlatformTradeStatics;
use ShopEM\Models\StatPlatformItemStatics;
use ShopEM\Models\StatPlatformShop;
use ShopEM\Models\StatShopTradeStatics;
use ShopEM\Models\StatShopItemStatics;
use ShopEM\Models\StatPlatformUser;
use ShopEM\Models\StatPlatformUserOrder;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeAftersales;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradeRefunds;

class StatService
{
    /**
     * 平台商家报表返回数据
     *
     * @Author hfh_wind
     * @param $params
     */
    public function platformTaskData($params)
    {
        $server = new \ShopEM\Services\Stats\Platform;
        // 得到规定时间内的新添加的会员和会员总数,商家数，商家总数，店铺数，店铺总数 保存
        $memberInfo = $server->getMemeberInfo($params);
        $statuId = StatPlatformUser::where(['created_at' => $memberInfo['created_at']])->first();
        if (!empty($statuId)) {
            StatPlatformUser::where(['id' => $statuId->id])->update($memberInfo);
        } else {
            StatPlatformUser::insert($memberInfo);
        }
        


        // 得到规定时间内的会员下单排行榜数量 保存
        $memberOrderInfo = $server->getMemeberOrderInfo($params);

        foreach ($memberOrderInfo as $key => $value) {
            $desktopStatId = StatPlatformUserOrder::where([
                'created_at'=> $value['created_at'],
                'user_id'   =>$value['user_id'],
                'gm_id'     =>$value['gm_id'],
            ])->first();
            if (!empty($desktopStatId)) {
                StatPlatformUserOrder::where(['id' => $desktopStatId->id])->update($value);
            } else {
                StatPlatformUserOrder::insert($value);
            }
        }

        //平台运营商交易统计
        // 得到规定时间内的新添加的订单数、额，以完成的订单数、额,以退款订单数，额  保存
        $tradeInfo = $server->getTradeInfo($params);

        foreach ($tradeInfo as $key => $value) {
            $desktopStatId = StatPlatformTradeStatics::where([
                'created_at' => $value['created_at'],
                'stats_trade_from'=>$key,
                'gm_id'     => $value['gm_id'],
            ])->first();
            if (!empty($desktopStatId)) {
                StatPlatformTradeStatics::where(['id' => $desktopStatId->id])->update($value);
            } else {
                StatPlatformTradeStatics::insert($value);
            }
        }

        //店铺销售排行统计
        //得到规定时间内店铺排行数据并保存到数据库
        $statShopInfo = $server->getShopOrderInfo($params);

        foreach ($statShopInfo as $key => $value) {
            $StatshopId = StatPlatformShop::where([
                'created_at' => $value['created_at'],
                'shop_id'    => $value['shop_id'],
                'gm_id'     => $value['gm_id'],
            ])->first();
            if (!empty($StatshopId)) {
                StatPlatformShop::where(['id' => $StatshopId->id])->update($value);
            } else {
                StatPlatformShop::insert($value);
            }
        }


        //得到规定时间内商品排行数据并保存到数据库
        $statItemInfo = $server->getItemOrderInfo($params);
        if($statItemInfo){
            foreach ($statItemInfo as $key => $value) {
                $StatItemId = StatPlatformItemStatics::where([
                    'created_at' => $value['created_at'],
                    'goods_id'   => $value['goods_id'],
                    'gm_id'     => $value['gm_id'],
                ])->first();

                if (!empty($StatItemId)) {
                    StatPlatformItemStatics::where(['id' => $StatItemId->id])->update($value);
                } else {
                    StatPlatformItemStatics::insert($value);
                }
            }
        }


    }


    /**
     * 商家报表返回数据
     *
     * @Author hfh_wind
     * @param $params
     */

    public function shopTaskData($params)
    {
        $server = new \ShopEM\Services\Stats\Shop;

        // 得到所有的商家id和热门商品
        $hotGoods = $server->hotGoods($params);
        // 得到所有的商家id和退货商品
        $refundGoods = $server->refundGoods($params);
        // 得到所有的商家id和换货商品
        $changingGoods = $server->changingGoods($params);

        // 得到所有的商家id和新增订单数,新增订单额
        $newTrade = $server->newTrade($params);
        // 得到所有的商家id和待付款订单数,待付款订单额
        $readyTrade = $server->readyTrade($params);
        // 得到所有的商家id和以付款订单数,以付款订单额
        $alreadyTrade = $server->alreadyTrade($params);
        // 得到所有的商家id和待发货订单数量,待发货订单额
        $readySendTrade = $server->readySendTrade($params);
        // 得到所有的商家id和待收货订单数量,待收货订单额
        $alreadySendTrade = $server->alreadySendTrade($params);

        // 得到所有的商家id和已完成订单数量,已完成订单额
        $completeTrade = $server->completeTrade($params);

        // 得到所有的商家id和已取消的订单数量,已取消订单额
        $cancleTrade = $server->cancleTrade($params);
        // 得到所有的商家id和退货退款订单的订单数量,退货退款订单的订单额
        $refundTrade = $server->refundTrade($params);
        // 得到所有的商家id和已换货订单的订单数量
        $exchangingTrade = $server->exchangingTrade($params);
        // 得到所有的商家id和拒收订单数量，拒收订单额
        $rejectTrade = $server->rejectTrade($params);

        $data = $server->getData($newTrade, $readyTrade, $readySendTrade, $alreadySendTrade, $completeTrade, $cancleTrade, $alreadyTrade, $refundTrade, $exchangingTrade, $rejectTrade, $params);

        $goodsData = $server->getGoodsData($hotGoods, $refundGoods, $changingGoods, $params);

        foreach ($data as $value) {
            if ($value['shop_id'] > 0) 
            {
                $gm_id = \ShopEM\Models\Shop::where('id',$value['shop_id'])->value('gm_id');
                $rows= StatShopTradeStatics::where(['shop_id'=>$value['shop_id'],'created_at'=>$value['created_at']])->first();
                if (!empty($rows)) {
                    StatShopTradeStatics::where(['id'=>$rows->id])->update($value);
                }else{
                    $value['gm_id'] = $gm_id;
                    StatShopTradeStatics::create($value);
                }

            }
        }


        foreach ($goodsData as $key => $value) {
            $gm_id = \ShopEM\Models\Shop::where('id',$value['shop_id'])->value('gm_id');

            $shopItemrows= StatShopItemStatics::where(['shop_id'=>$value['shop_id'],'created_at'=>$value['created_at'],'goods_id'=>$value['goods_id']])->first();
            if (!empty($shopItemrows)) {
                StatShopItemStatics::where(['id'=>$rows->id])->update($value);
            }else{
                $value['gm_id'] = $gm_id;
                StatShopItemStatics::create($value);
            }
        }
    }



}