<?php
/**
 * @Filename        IndexController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeRefunds;

class IndexController extends BaseController
{
    public function test()
    {
        $app = app();
        $routes = $app->routes->getRoutes();
        foreach ($routes as $k=>$value){
            $path[$k]['uri'] = $value->uri;
            $path[$k]['methods'] = $value->methods;
            $path[$k]['middleware'] = $value->action['middleware'];
            $path[$k]['as'] = empty($value->action['as']) ? null : $value->action['as'];
        }
        dd($path);

        return 111;
    }

    /**
     * 平台端首页数据
     *
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request) {
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
        $result = Cache::remember('cache_platform_'.$type.'_operational_data_gmid_'.$this->GMID, $cache_min, function () use ($time_start, $time_end) {
//------------旧统计start----------
//$tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED'];
//            $result['countNewTrade'] = $countNewTrade = Trade::where('pay_time', '>=', $time_start)
//                ->where('pay_time', '<',$time_end)->where('cancel_status', 'NO_APPLY_CANCEL')->where('gm_id','=',$this->GMID)->count(); //昨日新增订单数量
//
//            $result['countNewTradeFee'] = $countNewTradeFee = Trade::where('pay_time', '>=', $time_start)
//                ->where('pay_time', '<',$time_end)->where('pay_time', '<',$time_end)->where('cancel_status', 'NO_APPLY_CANCEL')->where('gm_id','=',$this->GMID)->whereIn('status',$tradeStatus)->sum('amount'); //昨日新增订单总额
//
//            $result['avgPrice'] = $countNewTrade ? round($countNewTradeFee/$countNewTrade, 2) : 0;//昨日新增订单平均金额
//            $result['countChanging'] = TradeRefunds::where('gm_id',$this->GMID)->whereIn('status', ['3', '5', '6'])->count();//待处理的退款订单数量
//------------旧统计end----------
            $tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED','TRADE_CLOSED','TRADE_CLOSED_BY_SYSTEM'];
            $result['countNewTrade'] = $countNewTrade = Trade::where('pay_time', '>=', $time_start)
                ->where('pay_time', '<',$time_end)->where('gm_id','=',$this->GMID)->count(); //昨日新增订单数量

            $result['countNewTradeFee'] = $countNewTradeFee = Trade::where('pay_time', '>=', $time_start)
                ->where('pay_time', '<',$time_end)->where('gm_id','=',$this->GMID)->whereIn('status',$tradeStatus)->sum('amount'); //昨日新增订单总额

            $result['avgPrice'] = $countNewTrade ? round($countNewTradeFee/$countNewTrade, 2) : 0;//昨日新增订单平均金额
            $result['countChanging'] = TradeRefunds::where('gm_id',$this->GMID)->whereIn('status', ['3', '5', '6'])->count(); //待处理的退款订单数量

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
        $cache_min = \Carbon\Carbon::now()->addMinute(3);
        $result = Cache::remember('cache_platform_current_operational_data_gmid_'.$this->GMID, $cache_min, function () {
            $time_start = date("Y-m-d 00:00:00",time());
            $time_end = date("Y-m-d H:i:s");

//------------旧统计start----------
//            $tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED'];
//            $result['countNewTrade'] = $countNewTrade = Trade::where('pay_time', '>=', $time_start)
//                ->where('pay_time', '<',$time_end)->where('gm_id','=',$this->GMID)->where('cancel_status', 'NO_APPLY_CANCEL')->count(); //昨日新增订单数量
//            $result['countNewTradeFee'] = $countNewTradeFee = Trade::where('pay_time', '>=', $time_start)
//                ->where('pay_time', '<',$time_end)->where('gm_id','=',$this->GMID)->where('pay_time', '<',$time_end)->where('cancel_status', 'NO_APPLY_CANCEL')->whereIn('status',$tradeStatus)->sum('amount'); //昨日新增订单总额
//------------旧统计end----------
            $tradeStatus = ['WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED','TRADE_CLOSED','TRADE_CLOSED_BY_SYSTEM'];
            $result['countNewTrade'] = $countNewTrade = Trade::where('pay_time', '>=', $time_start)
                ->where('pay_time', '<',$time_end)->where('gm_id','=',$this->GMID)->count(); //当天新增订单数量
            $result['countNewTradeFee'] = $countNewTradeFee = Trade::where('pay_time', '>=', $time_start)
                ->where('pay_time', '<',$time_end)->where('gm_id','=',$this->GMID)->where('pay_time', '<',$time_end)->whereIn('status',$tradeStatus)->sum('amount'); //当天新增订单总额

            return $result;
        });
        return $this->resSuccess($result);
    }
}