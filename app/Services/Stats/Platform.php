<?php

/**
 * Platform.php
 * @Author: nlx
 * @Date:   2019-10-08 18:36:34
 */
namespace ShopEM\Services\Stats;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\Goods;
use ShopEM\Models\GoodsClass;
use ShopEM\Models\Shop;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeOrder;


class Platform
{
    /**
     * 得到昨日新添加会员以及会员总数,店铺，店铺数，商家，商家数
     * @param null
     * @return null
     */
    public function getMemeberInfo(array $params)
    {
        $userAccountMd = new \ShopEM\Models\UserAccount;
        $sellerAccountMd = new \ShopEM\Models\SellerAccount;
        $shopMd = new \ShopEM\Models\Shop;

        $filter = array(
            ['created_at','>=',$params['time_start']],
            ['created_at','<',$params['time_end']],
        );

        $userAllcount = $userAccountMd->count();
        $userIncreCount = $userAccountMd::where($filter)->count();

        $sellerAccount = $sellerAccountMd::where('gm_id',$params['gm_id'])->count();
        $filter = array_merge($filter,[['gm_id', '=', $params['gm_id']]]);
        $sellerNum = $sellerAccountMd::where($filter)->count();

        $shopfilter = array(
            ['created_at','>=',$params['time_start']],
            ['created_at','<',$params['time_end']],
            ['shop_state','=','1'],
        );

        $shopaccount = $shopMd::where('gm_id',$params['gm_id'])->count();
        $shopfilter = array_merge($shopfilter,[[ 'gm_id', '=', $params['gm_id'] ]] );
        $shopnum = $shopMd::where($shopfilter)->count();

        $rows['accountuser'] = $userAllcount;
        $rows['newuser'] = $userIncreCount;
        $rows['selleraccount'] = $sellerAccount;
        $rows['sellernum'] = $sellerNum;
        $rows['shopaccount'] = $shopaccount;
        $rows['shopnum'] = $shopnum;
        $rows['created_at'] = $params['time_insert'];
        $rows['gm_id'] = $params['gm_id'];
        return $rows;
    }

    /**
     * 得到昨日会员下单排行榜
     * @param null
     * @return null
     */
    public function getMemeberOrderInfo(array $params)
    {
        $rows = Trade::where('pay_time', '>=', $params['time_start'])
            ->where('pay_time', '<',$params['time_end'])
            ->where('gm_id', '=',$params['gm_id'])
            ->where(['status' => "TRADE_FINISHED"])
            ->select(DB::raw('count(*) as userordernum ,sum(amount) as userfee,any_value(user_id) as user_id'))
            ->orderByDesc('userfee')
            ->groupBy('user_id')
            ->get();

        $userAccountMd = new \ShopEM\Models\UserAccount;
        // $userExperMd = new \ShopEM\Models\UserExperience;
        $result = [];
        foreach ($rows as $key => $value)
        {
            $user = $userAccountMd::where('id',$value['user_id'])->first();
            // $experience = $userExperMd::where('user_id',$value['user_id'])->first();
            $experience = $user->experience;
            if(!empty($user->mobile))
            {
                $result[$key]['user_name'] = $user->mobile;
            }
            else
            {
                $result[$key]['user_name'] = $user->login_account;
            }

            $result[$key]['user_id'] = $value['user_id'];
            $result[$key]['userordernum'] = $value['userordernum'];
            $result[$key]['userfee'] = $value['userfee'];

            $result[$key]['experience'] = $experience;
            $result[$key]['created_at'] = $params['time_insert'];
            $result[$key]['gm_id']      = $params['gm_id'];

        }

        //echo '<pre>';print_r($userName);exit();
        return $result;

    }


    /**
     * 平台统计
     * 得到规定时间内的新添加的订单数、额，以完成的订单数、额,以退款订单数，额
     * @param null
     * @return null
     */
    public function getTradeInfo(array $params)
    {
        //新添加的订单数、额，
        $newTradeInfo = Trade::where('created_at', '>=', $params['time_start'])->where('created_at', '<',
            $params['time_end'])->where('gm_id','=',$params['gm_id'])->select(DB::raw('count(*) as new_trade ,sum(amount) as new_fee'))->get();
        $newTrade = [];
        foreach ($newTradeInfo as $key => $value) {
            $newTrade['wap'] = $value;
        }

        //完成的订单数、额
        $completeTradeInfo = Trade::where('end_time', '>=', $params['time_start'])->where('end_time', '<',
            $params['time_end'])->where(['status' => "TRADE_FINISHED"])->where('gm_id','=',$params['gm_id'])->select(DB::raw('count(*) as complete_trade ,sum(amount) as complete_fee'))->get();
        $completeTrade = [];
        foreach ($completeTradeInfo as $key => $value) {
            $completeTrade['wap'] = $value;
        }

        //退款的订单数、额
        $refundTradeInfo = Trade::where('trade_refund_logs.finish_time', '>=', $params['time_start'])
            ->leftJoin('trade_refund_logs', 'trade_refund_logs.tid', '=', 'trades.tid')
            ->where('trade_refund_logs.finish_time', '<',
                $params['time_end'])->where(['trade_refund_logs.status' => "succ"])->where('trades.shop_id','>',0)->select(DB::raw('count(em_trade_refund_logs.id) as refunds_num ,sum(em_trade_refund_logs.cur_money) as refunds_fee'))->get();
        $refundTrade = [];
        foreach ($refundTradeInfo as $key => $value) {
            $refundTrade['wap'] = $value;
        }

        $newTrade['pc'] = "";
        $newTrade['wap'] = "";
        $newTrade['app'] = "";
        $data = [];
        //整合数据
//        $type = array('pc', 'wap', 'app');
        //暂时不分,先那么来吧
        $type = array('wap');
        foreach ($type as $key) {
            $data[$key]['new_trade'] = isset($newTrade[$key]['new_trade']) ? $newTrade[$key]['new_trade'] : 0;
            $data[$key]['new_fee'] = isset($newTrade[$key]['new_fee']) ? $newTrade[$key]['new_fee'] : 0;
            $data[$key]['complete_trade'] = isset($completeTrade[$key]['complete_trade']) ? $completeTrade[$key]['complete_trade'] : 0;
            $data[$key]['complete_fee'] = isset($completeTrade[$key]['complete_fee']) ? $completeTrade[$key]['complete_fee'] : 0;
            $data[$key]['refunds_num'] = isset($refundTrade[$key]['refunds_num']) ? $refundTrade[$key]['refunds_num'] : 0;
            $data[$key]['refunds_fee'] = isset($refundTrade[$key]['refunds_fee']) ? $refundTrade[$key]['refunds_fee'] : 0;
            $data[$key]['stats_trade_from'] = $key;
            $data[$key]['created_at'] = $params['time_insert'];
            $data[$key]['gm_id'] = $params['gm_id'];
        }


        /*  $data['all']['new_trade'] = $newTrade['pc']['new_trade'] + $newTrade['wap']['new_trade'] + $newTrade['app']['new_trade'];
          $data['all']['new_fee'] = $newTrade['pc']['new_fee'] + $newTrade['wap']['new_fee'] + $newTrade['app']['new_fee'];


          $data['all']['complete_trade'] = $completeTrade['pc']['complete_trade'] + $completeTrade['wap']['complete_trade'] + $completeTrade['app']['complete_trade'];
          $data['all']['complete_fee'] = $completeTrade['pc']['complete_fee'] + $completeTrade['wap']['complete_fee'] + $completeTrade['app']['complete_fee'];


          $data['all']['refunds_num'] = $refundTrade['pc']['refunds_num'] + $refundTrade['wap']['refunds_num'] + $refundTrade['app']['refunds_num'];
          $data['all']['refunds_fee'] = $refundTrade['pc']['refunds_fee'] + $refundTrade['wap']['refunds_fee'] + $refundTrade['app']['refunds_fee'];
*/

        $data['all']['new_trade'] = isset($newTrade['wap']['new_trade']) ? $newTrade['wap']['new_trade'] : 0;
        $data['all']['new_fee'] = isset($newTrade['wap']['new_fee']) ? $newTrade['wap']['new_fee'] : 0;


        $data['all']['complete_trade'] = isset($completeTrade['wap']['complete_trade']) ? $completeTrade['wap']['complete_trade'] : 0;
        $data['all']['complete_fee'] = isset($completeTrade['wap']['complete_fee']) ? $completeTrade['wap']['complete_fee'] : 0;


        $data['all']['refunds_num'] = isset($refundTrade['wap']['refunds_num']) ? $refundTrade['wap']['refunds_num'] : 0;
        $data['all']['refunds_fee'] = isset($refundTrade['wap']['refunds_fee']) ? $refundTrade['wap']['refunds_fee'] : 0;

        $data['all']['created_at'] = $params['time_insert'];
        $data['all']['gm_id']      = $params['gm_id'];
        $data['all']['stats_trade_from'] = 'all';
//        echo '<pre>';
//        print_r($data);
//        exit();
        return $data;
    }


    /**
     * 得到昨日店铺排行榜
     * @param null
     * @return null
     */
    public function getShopOrderInfo(array $params)
    {
        $rows = Trade::where('pay_time', '>=', $params['time_start'])
            ->where('pay_time', '<',$params['time_end'])
            ->where('gm_id', '=',$params['gm_id'])
            ->where(['status' => "TRADE_FINISHED"])
            ->select(DB::raw('count(*) as shopaccountnum ,sum(amount) as shopaccountfee,any_value(shop_id) as shop_id'))
            ->groupBy('shop_id')->get();

        $shopMdl = new \ShopEM\Models\Shop;

        $result = [];
        foreach ($rows as $key => $value)
        {
            $shop = $shopMdl::where('id',$value['shop_id'])->select('shop_name')->first();
            if ($shop) {

                $result[$key]['shop_id'] = $value['shop_id'];
                $result[$key]['shopaccountnum'] = $value['shopaccountnum'];
                $result[$key]['shopaccountfee'] = $value['shopaccountfee'];
                $result[$key]['shopname'] = $shop->shop_name;
                $result[$key]['created_at'] = $params['time_insert'];
                $result[$key]['gm_id'] = $params['gm_id'];
            }
        }

//        echo '<pre>';print_r($rows);exit();
        return $result;

    }


    /**
     * 得到昨日商品排行榜
     * @param null
     * @return null
     */
    public function getItemOrderInfo(array $params)
    {
        //只要是付了款的订单就统计
        $rows = TradeOrder::where('pay_time', '>=', $params['time_start'])
            ->where('pay_time', '<',$params['time_end'])
            ->where('gm_id', '=',$params['gm_id'])
//                ->where(['status' => "TRADE_FINISHED"])
            ->select(DB::raw('sum(quantity) as amountnum ,sum(amount) as amountprice,any_value(shop_id) as shop_id,any_value(goods_id) as goods_id,any_value(goods_name) as title,any_value(goods_image) as pic_path,any_value(gc_id) as cat_id'))
            ->groupBy('goods_id')->get();

        if(count($rows) >0 ){
            $rows=$rows->toArray();
        }else{
            return  false ;
        }
//        $goods_arr = array_column($rows, 'goods_id');
        $shop_arr = array_column($rows, 'shop_id');

        //获取店铺信息
        $shop_all = Shop::whereIn('id',$shop_arr)->select('id','shop_name')->get()->toArray();
        $shop_all = resetKey($shop_all,'id');


        $result = [];
        foreach ($rows as $key => $value)
        {

            $goods_info=Goods::find($value['goods_id']);
            //获取类目信息
            $goods_id = $value['goods_id'];
            $cat_id = $value['cat_id'];
            $catInfo =GoodsClass::find($cat_id);

            //获取店铺信息
            $shop_id = $value['shop_id'];
            $result[$key]['shop_id'] = $shop_id;
            $result[$key]['shop_name'] = $shop_all[$shop_id]['shop_name'];

            $result[$key]['cat_id'] = isset($catInfo->id)?$catInfo->id:0;
            $result[$key]['cat_name'] = isset($catInfo->gc_name)?$catInfo->gc_name:'';

            $result[$key]['goods_id'] = $goods_id;
            $result[$key]['title'] = $goods_info['goods_name'];
            $result[$key]['pic_path'] = $value['pic_path'];
            $result[$key]['itemurl'] = $goods_info['goods_image'];

            $result[$key]['amountnum'] = $value['amountnum'];
            $result[$key]['amountprice'] = $value['amountprice'];
            $result[$key]['created_at'] = $params['time_insert'];
            $result[$key]['gm_id'] = $params['gm_id'];
        }

        return $result;

    }
}