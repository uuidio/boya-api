<?php
/**
 * @Filename        IndexController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author         swl
 */

namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Models\PageView;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeRefunds;
use ShopEM\Models\GmPlatform;

class IndexController extends BaseController
{
    /**
     * 平台端首页数据
     *
     * @Author swl
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request) {
        $data = $request->only('timeType');
//        $type = 'yesterday';
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


//        $cache_min = \Carbon\Carbon::now()->hour(1);
        $gm_id = $request->get('gm_id');
//        ------------旧统计start-2----------
//        $ids = GmPlatform::pluck('gm_id');
//        // Cache::forget('cache_group_'.$type.'_operational_data');
//        $result = Cache::remember('cache_group_'.$type.'_operational_data', $cache_min, function () use ($time_start, $time_end,$ids) {
//            $all = array('countNewTrade'=>0,'countNewTradeFee'=>0,'avgPrice'=>0,'countChanging'=>0,'refundNum'=>0,'refundMoney'=>0);
//            foreach ($ids as $key => $gm_id) {
////------------旧统计start-1----------
//                //$tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED'];
////                $result['countNewTrade'] = $countNewTrade = Trade::where('pay_time', '>=', $time_start)
////                    ->where('pay_time', '<',$time_end)->where('cancel_status', 'NO_APPLY_CANCEL')->where('gm_id','=',$gm_id)->count(); //新增订单数量
////
////                $result['countNewTradeFee'] = $countNewTradeFee = Trade::where('pay_time', '>=', $time_start)
////                    ->where('pay_time', '<',$time_end)->where('pay_time', '<',$time_end)->where('cancel_status', 'NO_APPLY_CANCEL')->where('gm_id','=',$gm_id)->whereIn('status',$tradeStatus)->sum('amount'); //新增订单总额
////
////                $result['avgPrice'] = $countNewTrade ? round($countNewTradeFee/$countNewTrade, 2) : 0;//新增订单平均金额
////                $result['countChanging'] = TradeRefunds::where('gm_id',$gm_id)->whereIn('status', ['3', '5', '6'])->count(); //待处理的退款订单数量
////------------旧统计end-1----------
//                $tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED','TRADE_CLOSED','TRADE_CLOSED_BY_SYSTEM'];
//                $result['countNewTrade'] = $countNewTrade = Trade::where('pay_time', '>=', $time_start)
//                    ->where('pay_time', '<',$time_end)->where('gm_id','=',$gm_id)->count(); //新增订单数量
//
//                $result['countNewTradeFee'] = $countNewTradeFee = Trade::where('pay_time', '>=', $time_start)
//                    ->where('pay_time', '<',$time_end)->where('gm_id','=',$gm_id)->whereIn('status',$tradeStatus)->sum('amount'); //新增订单总额
//
//                $result['avgPrice'] = $countNewTrade ? round($countNewTradeFee/$countNewTrade, 2) : 0;//新增订单平均金额
//                $result['countChanging'] = TradeRefunds::where('gm_id',$gm_id)->whereIn('status', ['3', '5', '6'])->count(); //待处理的退款订单数量
//
//                $result['refundNum'] = TradeRefunds::where('gm_id',$gm_id)->where('status', '1')->where('refund_at', '>=', $time_start)->where('refund_at', '<',$time_end)->count();//退款订单数量
//                $result['refundMoney'] = TradeRefunds::where('gm_id',$gm_id)->where('status', '1')->where('refund_at', '>=', $time_start)->where('refund_at', '<',$time_end)->sum('refund_fee');//退款金额
//
//                $PageViewcount = PageView::where(['obj_id'=>$gm_id,'type'=>'index_fit'])->where('created_at', '>=', $time_start)->where('created_at', '<',$time_end)->count();//统计首页访问量
//
//                $all['countNewTrade'] = $all['countNewTrade'] + $result['countNewTrade'];
//                $all['countNewTradeFee'] = $all['countNewTradeFee'] + $result['countNewTradeFee'];
//                $all['avgPrice'] = $all['avgPrice'] + $result['avgPrice'];
//                $all['countChanging'] =   $all['countChanging'] + $result['countChanging'];
//                $all['refundNum'] =   $all['refundNum'] + $result['refundNum'];  //退款总数
//                $all['refundMoney'] =  bcadd($all['refundMoney'],$result['refundMoney'],2);// 退款总额
//                $all['countPangeView'] = $PageViewcount;//首页访问量
//
//
//            }
//            return $all;
//        });
//       /------------旧统计end-2----------
        $all = array('countNewTrade'=>0,'countNewTradeFee'=>0,'avgPrice'=>0,'countChanging'=>0,'refundNum'=>0,'refundMoney'=>0);
        $tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED','TRADE_CLOSED','TRADE_CLOSED_BY_SYSTEM'];
        $result['countNewTrade'] = $countNewTrade = Trade::where('pay_time', '>=', $time_start)
                    ->where('pay_time', '<',$time_end)->where('gm_id','=',$gm_id)->count(); //新增订单数量

        $result['countNewTradeFee'] = $countNewTradeFee = Trade::where('pay_time', '>=', $time_start)
                    ->where('pay_time', '<',$time_end)->where('gm_id','=',$gm_id)->whereIn('status',$tradeStatus)->sum('amount'); //新增订单总额

        $result['avgPrice'] = $countNewTrade ? round($countNewTradeFee/$countNewTrade, 2) : 0;//新增订单平均金额
        $result['countChanging'] = TradeRefunds::where('gm_id',$gm_id)->whereIn('status', ['3', '5', '6'])->count(); //待处理的退款订单数量

        $result['refundNum'] = TradeRefunds::where('gm_id',$gm_id)->where('status', '1')->where('refund_at', '>=', $time_start)->where('refund_at', '<',$time_end)->count();//退款订单数量
        $result['refundMoney'] = TradeRefunds::where('gm_id',$gm_id)->where('status', '1')->where('refund_at', '>=', $time_start)->where('refund_at', '<',$time_end)->sum('refund_fee');//退款金额

        $PageViewcount = PageView::where(['obj_id'=>$gm_id,'type'=>'index_fit'])->where('created_at', '>=', $time_start)->where('created_at', '<',$time_end)->count();//统计首页访问量

        $all['countNewTrade'] = $all['countNewTrade'] + $result['countNewTrade']; 
        $all['countNewTradeFee'] = $all['countNewTradeFee'] + $result['countNewTradeFee'];
        $all['avgPrice'] = $all['avgPrice'] + $result['avgPrice'];
        $all['countChanging'] =   $all['countChanging'] + $result['countChanging'];
        $all['refundNum'] =   $all['refundNum'] + $result['refundNum'];  //退款总数
        $all['refundMoney'] =  bcadd($all['refundMoney'],$result['refundMoney'],2);// 退款总额
        $all['countPangeView'] = $PageViewcount;//首页访问量
        return $this->resSuccess($all);
    }


     /**
     * 今天实时概况
     * @Author swl
     * @return \Illuminate\Http\JsonResponse
     */
    public function currentTime(Request $request) {
        $cache_min = \Carbon\Carbon::now()->addMinute(1);
        $gm_id = $request->get('gm_id');

        if(!empty($gm_id)){
            //单个项目
            $result = Cache::remember('cache_platform_current_operational_data_gmid_'.$gm_id, $cache_min, function () use ($gm_id) {
                $time_start = date("Y-m-d 00:00:00",time());
                $time_end = date("Y-m-d H:i:s");
                $tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED','TRADE_CLOSED','TRADE_CLOSED_BY_SYSTEM'];
                $result['countNewTrade'] = $countNewTrade = Trade::where('pay_time', '>=', $time_start)
                    ->where('pay_time', '<',$time_end)->where('gm_id','=',$gm_id)->count(); //当天新增订单数量
                $result['countNewTradeFee'] = $countNewTradeFee = Trade::where('pay_time', '>=', $time_start)
                    ->where('pay_time', '<',$time_end)->where('gm_id','=',$gm_id)->where('pay_time', '<',$time_end)->whereIn('status',$tradeStatus)->sum('amount'); //当天新增订单总额
                $result['countPangeView'] = $PageViewcount = PageView::where(['obj_id'=>$gm_id,'type'=>'index_fit'])->where('created_at', '>=', $time_start)->where('created_at', '<',$time_end)->count();//统计首页访问量

                return $result;
            });
        }else{
            //整个集团
            // $ids = GmPlatform::pluck('gm_id');
            // Cache::forget('cache_group_current_operational_data');
            $result = Cache::remember('cache_group_current_operational_data', $cache_min, function ()
            {
                $all = array('countNewTrade'=>0,'countNewTradeFee'=>0,'countPangeView'=>0);
                $time_start = date("Y-m-d 00:00:00",time());
                $time_end = date("Y-m-d H:i:s");
                $tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED','TRADE_CLOSED','TRADE_CLOSED_BY_SYSTEM'];

                $all['countNewTrade'] = $countNewTrade = Trade::where('pay_time', '>=', $time_start)
                        ->where('pay_time', '<',$time_end)->count(); //新增订单数量

                $all['countNewTradeFee'] = $countNewTradeFee = Trade::where('pay_time', '>=', $time_start)
                        ->where('pay_time', '<',$time_end)->whereIn('status',$tradeStatus)->sum('amount'); //新增订单总额

                $all['countPangeView'] = $PageViewcount = PageView::where(['type'=>'index_fit'])->where('created_at', '>=', $time_start)->where('created_at', '<',$time_end)->count();//统计首页访问量
                
                return $all;
            });
        }
       
        return $this->resSuccess($result);
    }

    //增加时间筛选
    public function selectDetail(Request $request){
        $data = $request->all();
        // dd($data);
        $time_start = $data['time_start'];
        $time_end = $data['time_end'];
        $ids = GmPlatform::pluck('gm_id');
        $all = array('countNewTrade'=>0,'countNewTradeFee'=>0,'avgPrice'=>0,'countChanging'=>0,'refundNum'=>0,'refundMoney'=>0);
        foreach ($ids as $key => $gm_id) {
            $tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED','TRADE_CLOSED','TRADE_CLOSED_BY_SYSTEM'];
            $result['countNewTrade'] = $countNewTrade = Trade::where('pay_time', '>=', $time_start)
                ->where('pay_time', '<',$time_end)->where('gm_id','=',$gm_id)->count(); //新增订单数量

            $result['countNewTradeFee'] = $countNewTradeFee = Trade::where('pay_time', '>=', $time_start)
                ->where('pay_time', '<',$time_end)->where('gm_id','=',$gm_id)->whereIn('status',$tradeStatus)->sum('amount'); //新增订单总额

            $result['avgPrice'] = $countNewTrade ? round($countNewTradeFee/$countNewTrade, 2) : 0;//新增订单平均金额
            $result['countChanging'] = TradeRefunds::where('gm_id',$gm_id)->whereIn('status', ['3', '5', '6'])->count(); //待处理的退款订单数量
        
            $result['refundNum'] = TradeRefunds::where('gm_id',$gm_id)->where('status', 1)->where('refund_at', '>=', $time_start)->where('refund_at', '<',$time_end)->count();//退款订单数量
            $result['refundMoney'] = TradeRefunds::where('gm_id',$gm_id)->where('status', 1)->where('refund_at', '>=', $time_start)->where('refund_at', '<',$time_end)->sum('refund_fee');//退款金额
            $PageViewcount = PageView::where(['obj_id'=>$gm_id,'type'=>'index_fit'])->where('created_at', '>=', $time_start)->where('created_at', '<',$time_end)->count();//统计首页访问量
            $all['countNewTrade'] = $all['countNewTrade'] + $result['countNewTrade'];
            $all['countNewTradeFee'] = $all['countNewTradeFee'] + $result['countNewTradeFee'];
            $all['avgPrice'] = $all['avgPrice'] + $result['avgPrice'];   
            $all['countChanging'] =   $all['countChanging'] + $result['countChanging'];   
            $all['refundNum'] =   $all['refundNum'] + $result['refundNum'];  //退款总数   
            $all['refundMoney'] =  bcadd($all['refundMoney'],$result['refundMoney'],2);// 退款总额
            $all['countPangeView'] = $PageViewcount;//首页访问量

        }
        return $this->resSuccess($all);
    }
}