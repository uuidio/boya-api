<?php
/**
 * @Filename        IndexController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Models\Coupon;
use ShopEM\Models\Goods;
use ShopEM\Models\RateScore;
use ShopEM\Models\RateTraderate;
use ShopEM\Models\StatShopItemStatics;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeAftersales;

class IndexController extends BaseController
{

    public function test()
    {
//        echo Carbon::createFromFormat('Y-m-d H:i:s','2018-12-31T16:00:00.000Z')->toDateTimeString();

//        $dt = Carbon::parse('2018-12-31T16:00:00.000Z');

        $coupon = Coupon::where('id', 1)
            ->where('start_at', '<=', Carbon::now()->toDateTimeString())
            ->where('end_at', '>=', Carbon::now()->toDateTimeString())
            ->get()->toArray();
        dd($coupon);

        print_r(Carbon::now()->toDateTimeString());
    }

    /**
     * 商家端首页数据
     *
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request) {
        $shop_id = $this->shop->id;
        $data = $request->only('timeType');
        $type = 'yesterday';
        $time_start = date("Y-m-d 00:00:00",strtotime("-1 day"));
        $time_end = date("Y-m-d 23:59:59",strtotime("-1 day"));

        if (isset($data['timeType']) && $data['timeType']) {
            switch ($data['timeType']) {
                case 'yesterday':
                    $time_start = date("Y-m-d 00:00:00",strtotime("-1 day"));
                    $time_end = date("Y-m-d 23:59:59",strtotime("-1 day"));
                    break;
                case 'beforeday':
                    $time_start = date("Y-m-d 00:00:00",strtotime("-2 day"));
                    $time_end = date("Y-m-d 23:59:59",strtotime("-2 day"));
                    $type = 'beforeday';
                    break;
                case 'week':
                    $time_start = date("Y-m-d 00:00:00",strtotime("-7 day"));
                    $time_end = date("Y-m-d 23:59:59",strtotime("-1 day"));
                    $type = 'week';
                    break;
                case 'month':
                    $time_start = date("Y-m-d 00:00:00",strtotime("-30 day"));
                    $time_end = date("Y-m-d 23:59:59",strtotime("-1 day"));
                    $type = 'month';
                    break;
            }
        }

        $cache_min = \Carbon\Carbon::now()->hour(1);
        $result = Cache::remember('cache_'.$type.'_operational_data_shop_id_' . $shop_id, $cache_min, function () use ($shop_id, $time_start, $time_end) {
            //$tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED'];
            $tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED','TRADE_CLOSED','TRADE_CLOSED_BY_SYSTEM'];
            //店铺评价评分
            $score_count = RateScore::where('shop_id', $shop_id)->where('disabled', 0)->count(); //店铺评价总数
            $tally_score_sum = RateScore::where('shop_id', $shop_id)->where('disabled', 0)->sum('tally_score'); //描述相符的总评分
            $result['score']['tally_score'] = $score_count ? sprintf('%.4f',$tally_score_sum/$score_count) : 5;; //描述相符的平均分
            $attitude_score_sum = RateScore::where('shop_id', $shop_id)->where('disabled', 0)->sum('attitude_score'); //服务态度的总评分
            $result['score']['attitude_score'] = $score_count ? sprintf('%.4f',$attitude_score_sum/$score_count) : 5; //服务态度的平均分
            $delivery_speed_score_sum = RateScore::where('shop_id', $shop_id)->where('disabled', 0)->sum('delivery_speed_score'); //发货速度的总评分
            $result['score']['delivery_speed_score'] = $score_count ? sprintf('%.4f',$delivery_speed_score_sum/$score_count) : 5; //发货速度的平均分
            $logistics_service_score_sum = RateScore::where('shop_id', $shop_id)->where('disabled', 0)->sum('logistics_service_score'); //物流服务的总评分
            $result['score']['logistics_service_score'] = $score_count ? sprintf('%.4f',$logistics_service_score_sum/$score_count) : 5; //物流服务的平均分

            $result['topFiveGoods'] = StatShopItemStatics::where('shop_id', $shop_id)->where('created_at', '<=', $time_start)
                ->where('updated_at', '>=', $time_start)
                ->orderBy('amountnum', 'DESC')
                ->take(5)->get(); //销量排行top5

            $result['countNewTrade'] = $countNewTrade = Trade::where('shop_id', $shop_id)
                ->where('pay_time', '>=', $time_start)
               // ->where('pay_time', '<',$time_end)->where('cancel_status', 'NO_APPLY_CANCEL')->count();
               ->where('pay_time', '<',$time_end)->count(); //新增订单数量

            $result['countNewTradeFee'] = $countNewTradeFee = Trade::where('shop_id', $shop_id)
                ->where('pay_time', '>=', $time_start)
                ->where('pay_time', '<',$time_end)
                //->where('cancel_status', 'NO_APPLY_CANCEL')
                ->whereIn('status',$tradeStatus)->sum('amount'); //昨日新增订单总额

            $result['avgPrice'] = $countNewTrade ? round($countNewTradeFee/$countNewTrade, 2) : 0;//昨日新增订单平均金额

            $result['countUnTradeFee'] = Trade::where('trades.shop_id', $shop_id)->where('trades.status', 'WAIT_BUYER_PAY')->count(); //待付款的订单数量
            $result['countReadysSend'] = Trade::where('trades.shop_id', $shop_id)->where('trades.status', 'WAIT_SELLER_SEND_GOODS')
                ->leftJoin('trade_cancels', 'trade_cancels.tid', '=', 'trades.tid')->where( function ($query){
                    $query->whereNull('cancel_id')->orWhereIn('trade_cancels.process', ['1','3']); })
                ->leftJoin('trade_aftersales', 'trade_aftersales.tid', '=', 'trades.tid')->where( function ($query){
                    $query->whereNull('aftersales_bn')->orWhereIn('trade_aftersales.status', ['2','3']); })
                ->count(); //待发货的订单数量
            $result['countReadyRec'] = Trade::where('trades.shop_id', $shop_id)->where('trades.status', 'WAIT_BUYER_CONFIRM_GOODS')
                ->leftJoin('trade_cancels', 'trade_cancels.tid', '=', 'trades.tid')->where( function ($query){
                    $query->whereNull('cancel_id')->orWhereIn('trade_cancels.process', ['1','3']); })
                ->leftJoin('trade_aftersales', 'trade_aftersales.tid', '=', 'trades.tid')->where( function ($query){
                    $query->whereNull('aftersales_bn')->orWhereIn('trade_aftersales.status', ['2','3']); })
                ->count(); //待收货的的订单数量

            $result['countRateUnreply'] = RateTraderate::where('shop_id', $shop_id)->where('is_reply', 0)->count(); //待回复数据
            $result['countRate'] = RateTraderate::where('shop_id', $shop_id)->count(); //获取商品评价数量

            $result['countRefund'] = TradeAftersales::where('shop_id', $shop_id)->where('aftersales_type', 'REFUND_GOODS')->where('progress', 0)->count(); //待处理的退货售后数量
            $result['countChanging'] = TradeAftersales::where('shop_id', $shop_id)->where('aftersales_type', 'EXCHANGING_GOODS')->where('progress', 0)->count(); //待处理的换货售后数量

            $result['countShopOnsaleGoods'] = Goods::where('shop_id', $shop_id)->where('goods_state', 1)->count(); //获取店铺上架商品数量
            $result['countShopInstockGoods'] = Goods::where('shop_id', $shop_id)->where('goods_state', 0)->count(); //获取店铺下架商品数量
            $result['countShopRefuseGoods'] = Goods::where('shop_id', $shop_id)->where('goods_verify', 0)->count(); //获取店审核失败商品数量
            return $result;
        });
        return $this->resSuccess($result);
    }

    /**
     * 今天实时概况
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function currentTime() {
        $shop_id = $this->shop->id;
        $cache_min = \Carbon\Carbon::now()->addMinute(3);
        $result = Cache::remember('cache_current_operational_data_shop_id_' . $shop_id, $cache_min, function () use ($shop_id) {
            $time_start = date("Y-m-d 00:00:00",time());
            $time_end = date("Y-m-d H:i:s");
//------------旧统计start----------
//            $tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED'];
//            $result['countNewTrade'] = Trade::where('shop_id', $shop_id)
//                ->where('created_at', '>=', $time_start)
//                ->where('created_at', '<',$time_end)->where('cancel_status', 'NO_APPLY_CANCEL')->count(); //今天新增订单数量
//            $result['countNewTradeFee'] = Trade::where('shop_id', $shop_id)
//                ->where('created_at', '>=', $time_start)
//                ->where('created_at', '<',$time_end)
//                ->where('cancel_status', 'NO_APPLY_CANCEL')
//                ->whereIn('status',$tradeStatus)->sum('amount'); //今天新增订单总额
//------------旧统计end----------
            $tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED','TRADE_CLOSED','TRADE_CLOSED_BY_SYSTEM'];
            $result['countNewTrade'] = Trade::where('shop_id', $shop_id)
                ->where('pay_time', '>=', $time_start)
                ->where('pay_time', '<',$time_end)->count(); //今天新增订单数量
            $result['countNewTradeFee'] = Trade::where('shop_id', $shop_id)
                ->where('pay_time', '>=', $time_start)
                ->where('pay_time', '<',$time_end)
                ->whereIn('status',$tradeStatus)->sum('amount'); //今天新增订单总额
            return $result;
        });
        return $this->resSuccess($result);
    }
}