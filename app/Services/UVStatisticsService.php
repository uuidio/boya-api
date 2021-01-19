<?php
/**
 * @Filename        UVTradeService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          Huiho
 */

namespace ShopEM\Services;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\UVTrade;
use ShopEM\Models\TradeDaySettleAccountDetail;
use ShopEM\Models\Shop;
use ShopEM\Models\Trade;


class UVStatisticsService
{
    /**
     *  当天统计数据
     *
     * @Author Huiho
     * @param $param  time_start ,time_end
     * @throws Exception
     */
    public function YesterdayData($param)
    {
        //获取店铺信息
        $shopInfo = $this->getShopInfo();

        $query_data['time_start'] = $param['time_start'];
        $query_data['time_end'] = $param['time_end'];

        foreach ($shopInfo as $key => &$value)
        {
            $query_data['shop_id'] = $value['id'];
            $query_data['gm_id'] = $value['gm_id'];
            $insert_data['trading_volume'] = $this->countTrades($query_data);
            $check_data = UVTrade::where(['shop_id' => $value['id'], 'gm_id' => $value['gm_id'],'trading_day'=>$param['time_insert']])->count();
            if ($check_data)
            {
                UVTrade::where(['shop_id' => $value['id'], 'gm_id' => $value['gm_id']])->update($insert_data);
            }
            else
            {
                $insert_data['trading_day'] = $param['time_insert'];
                $insert_data['shop_id'] = $query_data['shop_id'];
                $insert_data['gm_id'] = $query_data['gm_id'];
                UVTrade::create($insert_data);
            }
        }


    }

    /**
     * 统计交易人数
     * @Author Huiho
     * @param array $params
     * @return mixed
     */
    public function countTrades($params)
    {
        $count = Trade::whereNotIn('cancel_status',  ['SUCCESS','REFUND_PROCESS'])
                            ->whereNotIn('status',  ['TRADE_CLOSED', 'TRADE_CLOSED_BY_SYSTEM','WAIT_BUYER_PAY','TRADE_CLOSED_AFTER_PAY'])
                                ->where('pay_time', '>=', $params['time_start'])
                                    ->where('pay_time', '<=', $params['time_end'])
                                            ->where('shop_id', '=', $params['shop_id'])
                                                ->where('gm_id', '=', $params['gm_id'])
                                                ->groupBy('user_id')
                                                    ->get();

        if(!$count)
        {
            $result = 0;
        }
        else
        {
            $count = count($count);
            $result =$count;
        }

        return $result;
    }

    /**
     * 统计店铺
     * @Author Huiho
     * @param array $params
     * @return mixed
     */
    public function getShopInfo()
    {
        $shopInfo = Shop::where('id' , '>' , '0')->select('gm_id','id')->get()->toArray();
        return $shopInfo;
    }



}