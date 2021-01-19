<?php
/**
 * @Filename        TradePolymorphicRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          Huiho
 */

namespace ShopEM\Repositories;

use ShopEM\Models\TradeOrder;
use ShopEM\Models\Trade;
use Illuminate\Support\Facades\DB;

class TradePolymorphicRepository
{


    /*
    * 定义搜索过滤字段（确认收货列表）
    */
    protected $confirmFilterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'tid' => ['field' => 'trades.tid', 'operator' => '='],
        'user_id' => ['field' => 'trades.user_id', 'operator' => '='],
        'shop_id' => ['field' => 'trades.shop_id', 'operator' => '='],
        'created_start'  => ['field' => 'trades.created_at', 'operator' => '>='],
        'created_end'  => ['field' => 'trades.created_at', 'operator' => '<='],
        'confirm_at_start'  => ['field' => 'trades.confirm_at', 'operator' => '>='],
        'confirm_at_end'  => ['field' => 'trades.confirm_at', 'operator' => '<='],
    ];




    /*
     * 定义搜索过滤字段(成本结算报表)
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'tid' => ['field' => 'tid', 'operator' => '='],
        'oid' => ['field' => 'oid', 'operator' => '='],
        'goods_id' => ['field' => 'goods_id', 'operator' => '='],
        'user_id' => ['field' => 'user_id', 'operator' => '='],
        'created_start'  => ['field' => 'created_at', 'operator' => '>='],
        'created_end'  => ['field' => 'created_at', 'operator' => '<='],
        'pick_type'  => ['field' => 'pick_type', 'operator' => '='],
        'pick_statue'  => ['field' => 'pick_statue', 'operator' => '='],
    ];


    /**
     * 确认收货报表
     *
     * @return array
     */
    public function confirmOrderListFields($is_show='')
    {
        return
        [
            ['dataIndex' => 'gm_name', 'title' => '所属项目'],
            ['dataIndex' => 'shop_name', 'title' => '所属店铺'],
            ['dataIndex' => 'tid', 'title' => '交易订单号'],
            ['dataIndex' => 'amount', 'title' => '实付金额'],
            ['dataIndex' => 'confirm_at', 'title' => '确认收货时间'],
            ['dataIndex' => 'payed_time', 'title' => '支付时间'],

        ];
    }


    /**
     * 成本结算报表
     *
     * @return array
     */
    public function GoodsCostListFields($is_show='')
    {
        return
            [
                ['dataIndex' => 'gm_name', 'title' => '所属项目'],
                ['dataIndex' => 'shop_name', 'title' => '所属店铺'],
                ['dataIndex' => 'tid', 'title' => '订单号'],
                ['dataIndex' => 'oid', 'title' => '子订单'],
                ['dataIndex' => 'status_text', 'title' => '订单状态'],
                ['dataIndex' => 'goods_serial', 'title' => '商品货号'],
                ['dataIndex' => 'goods_name', 'title' => '商品名称'],
                ['dataIndex' => 'created_at', 'title' => '订单创建时间'],
                ['dataIndex' => 'confirm_at', 'title' => '确认收货时间'],
                ['dataIndex' => 'goods_price', 'title' => '销售价格'],
                ['dataIndex' => 'goods_cost', 'title' => '成本价'],
                ['dataIndex' => 'refund_fee_text', 'title' => '退款金额'],
                ['dataIndex' => 'refund_at', 'title' => '退款时间'],
                ['dataIndex' => 'profit', 'title' => '利润差额'],

            ];
    }


    /**
     * 自提商品核销列表
     *
     * @return array
     */
    public function selfExtractingListsFields($is_show='')
    {
        return
            [
                ['dataIndex' => 'gm_name', 'title' => '所属项目'],
                ['dataIndex' => 'shop_name', 'title' => '所属店铺'],
                ['dataIndex' => 'tid', 'title' => '订单号'],
                ['dataIndex' => 'oid', 'title' => '子订单'],
                ['dataIndex' => 'goods_serial', 'title' => '商品货号'],
                ['dataIndex' => 'goods_name', 'title' => '商品名称'],
                ['dataIndex' => 'created_at', 'title' => '订单创建时间'],
                ['dataIndex' => 'pick_statue_text', 'title' => '核销状态'],

            ];
    }


    /**
     * 销售日报
     *
     * @return array
     */
    public function dailySalesListFields($is_show='')
    {
        return
            [
                ['dataIndex' => 'data_at', 'title' => '日期'],
                ['dataIndex' => 'shop_name', 'title' => '店铺名称'],
                ['dataIndex' => 'sales_amount', 'title' => '销售订单笔数'],
                ['dataIndex' => 'payed_amount', 'title' => '销售订单实付金额'],
                ['dataIndex' => 'average_fee_amount', 'title' => '客单价'],
                ['dataIndex' => 'refund_amount', 'title' => '退货订单笔数'],
                ['dataIndex' => 'refund_payed_amount', 'title' => '退货订单实付金额'],
                ['dataIndex' => 'total_discount_fee', 'title' => '优惠总额'],
                ['dataIndex' => 'platform_act_fee_amount', 'title' => '平台优惠券金额'],
                ['dataIndex' => 'coupon_shop_fee_amount', 'title' => '商家优惠券金额'],
                ['dataIndex' => 'promotion_fee_amount', 'title' => '店铺满减满折金额'],
                ['dataIndex' => 'points_fee_amount', 'title' => '积分抵扣金额'],
                ['dataIndex' => 'consume_point_fee_amount', 'title' => '消耗积分'],
                ['dataIndex' => 'post_fee_amount', 'title' => '邮费'],
            ];
    }


    /**
     * 商品统计列表
     *
     * @return array
     */
    public function GoodSaleListFields($is_show='')
    {
        return
            [
                ['dataIndex' => 'id', 'title' => 'ID'],
                ['dataIndex' => 'goods_name', 'title' => '商品名称'],
                ['dataIndex' => 'shop_id', 'title' => '店铺ID'],
                ['dataIndex' => 'shop_name', 'title' => '所属店铺'],
                ['dataIndex' => 'goods_price', 'title' => '销售价格'],
                ['dataIndex' => 'sale_amount', 'title' => '销售总金额'],
                ['dataIndex' => 'sale_count', 'title' => '已售数量'],
                ['dataIndex' => 'goods_stock', 'title' => '剩余库存'],
            ];
    }




    /**
     * 后台表格列表显示字段
     *
     * @return array
     */
    public function listShowFields($is_show='')
    {
        return listFieldToShow($this->listFields($is_show));
    }

    /**
     * 确认收货订单查询
     */
    public function confirmSearch($request,$downData='')
    {
        $sum_result = [];
        $sum_data = ['amount'];

        $model = new Trade();
        $model = filterModel($model, $this->confirmFilterables, $request);

        if($downData){
            //下载提供数据
            $lists=$model->orderBy('id', 'desc')->whereNotNull('confirm_at')->get();

        }else{
            $search_model = $model->whereNotNull('confirm_at')->get();

            $lists = $model->orderBy('id', 'desc')->whereNotNull('confirm_at')->paginate($request['per_page']);
            if (isset($request['total_data_status']) && $request['total_data_status'])
            {
                $sum_result =$this->totalData($search_model,$sum_data);
            }
            $lists['total_fee_data'] =  [['value' => $sum_result['amount'], 'dataIndex' => 'amount', 'title' => '商品实付汇总']];

        }

        return $lists;
    }

    /**
     * 成本结算订单查询
     */
    public function search($request,$downData='')
    {
        $sum_result = [];
        $sum_data = ['goods_price','goods_cost','refund_fee_text','profit'];

        $model = new TradeOrder();
        $model = filterModel($model, $this->filterables, $request);

        if($downData)
        {
            //下载提供数据
            $lists=$model->orderBy('id', 'desc')->get();
            if (empty($lists)) {
                return $lists;
            } else {
                $lists = $lists->toArray();
                foreach ($lists as $key => &$value) {
                    $value['refund_fee_text'] = '-' . $value['refund_fee_text'];
                }
            }
        }
        else
        {
            $search_model = $model->get();

            $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);
            if (empty($lists)) {
                return $lists;
            } else {
                $lists = $lists->toArray();
                foreach ($lists['data'] as $key => &$value) {
                    $value['refund_fee_text'] = '-' . $value['refund_fee_text'];
                }
            }
            if (isset($request['total_data_status']) && $request['total_data_status'])
            {
                $sum_result =$this->totalData($search_model,$sum_data);
                $lists['total_fee_data'] =  [['value' => $sum_result['goods_price'], 'dataIndex' => 'goods_price', 'title' => '销售价格汇总'],
                    ['value' => $sum_result['goods_cost'], 'dataIndex' => 'goods_cost', 'title' => '成本汇总'],
                    ['value' => $sum_result['refund_fee_text'], 'dataIndex' => 'refund_fee_text', 'title' => '退款汇总'],
                    ['value' => $sum_result['profit'], 'dataIndex' => 'profit', 'title' => '利润汇总']
                ];
            }

        }

        return $lists;
    }


    /**
     * 核销订单查询
     */
    public function selfExtractingSearch($request,$downData='')
    {
//        $model = new Trade();
//        $model = filterModel($model, $this->filterables, $request);

        $model = DB::table('trade_orders as a')->leftJoin('trades as b' , 'a.tid' , '=' , 'b.tid')->select('b.pick_statue','b.shop_id','a.tid','a.oid','a.goods_name','a.created_at' ,'a.gm_id','a.sku_id')->where('pick_type','=','1');

        if(isset($request['shop_id'])&&!empty($request['shop_id']))
        {
            $model = $model->where('a.shop_id', '=' , $request['shop_id']);
        }

        if((isset($request['created_start'])&&!empty($request['created_start']))&&(isset($request['created_end'])&&!empty($request['created_end'])))
        {
            $model = $model->where('a.created_at','>=',$request['created_start'])->where('a.created_at','<=',$request['created_end']);
        }

        if(isset($request['pick_statue']))
        {
            $model = $model->where('pick_statue','=',$request['pick_statue']);
        }


        if($downData){
            //下载提供数据
            $lists = $model->orderBy('a.id', 'desc')->get();
        }else{
            $lists = $model->orderBy('a.id', 'desc')->paginate($request['per_page']);
        }
        if(empty($lists))
        {
            $lists = [];
        }
        else
        {
            $lists = $lists->toArray();
        }

        return $lists;
    }



    /**
     * 销售日报查询
     */
    public function dailySalesSearch($request , $downData='')
    {
        $serach_data = $request;
        $serach_data['down'] = $downData;
        $return =  $this->_countDailyData($serach_data);

        return $return;
    }


    private function _countDailyData($serach_data)
    {

        if(!isset($serach_data['filter_time']) ||  empty($serach_data['filter_time']))
        {
            $serach_data['filter_time'] = time();
        }
        else
        {
            $serach_data['filter_time'] = strtotime($serach_data['filter_time']);

        }

        $params = array(
            'time_start'  => date('Y-m-d 00:00:00',$serach_data['filter_time']),
            'time_end'    => date('Y-m-d 23:59:59', $serach_data['filter_time']),
            'filter_time' => date('Y-m-d ', $serach_data['filter_time']),
            'gm_id'       => $serach_data['gm_id'] ?? '',
            'per_page'    => $serach_data['per_page'] ?? '',
            'down'        => $serach_data['down'],
            'shop_id'        => $serach_data['shop_id'] ?? '',
            'total_data_status'        => $serach_data['total_data_status'] ?? '',
        );


        $statService = new   \ShopEM\Services\TradePolymorphicService();
        $return = $statService->tradeDay($params);

        return $return;
    }



    /**
     * 商品统计列表查询
     */
    public function GoodSaleSearch($request,$downData='')
    {
        $sum_result = [];
        $sum_data = ['sale_amount'];

        $model = DB::table('goods_skus as a')->leftJoin('shops as c' , 'a.shop_id' , '=' , 'c.id')->select('a.goods_name','a.shop_id','c.shop_name','a.goods_price','a.goods_stock','a.id');

//        $sale_sum_sql = DB::table('trade_orders')->whereIn('status' ,['TRADE_FINISHED'])->whereNotIn('after_sales_status' ,['SUCCESS'])->whereNull('after_sales_status');
//        $sale_sum_sql = DB::table('trade_orders')->whereIn('status' ,['TRADE_FINISHED'])->where('after_sales_status' ,'!=','SUCCESS')->orWhereNull('after_sales_status');

//        $sale_count_sql =  DB::table('trade_orders')->where('status' ,'=' ,'TRADE_FINISHED')->whereNotIn('after_sales_status' ,['SUCCESS']);

        if(isset($request['gm_id'])&&!empty($request['gm_id']))
        {
            $model = $model->where('a.gm_id', '=' , $request['gm_id']);
        }

        if(isset($request['shop_id'])&&!empty($request['shop_id']))
        {
            $model = $model->where('a.shop_id', '=' , $request['shop_id']);
        }
        if($downData){
            //下载提供数据
            $lists = $model->orderBy('a.id', 'desc')->get();
        }
        else
        {
            $search_model = $model->get();
            $lists = $model->orderBy('a.id', 'desc')->paginate($request['per_page']);

        }

        if(empty($lists))
        {
            $lists = [];
        }
        else
        {
            $lists = $lists->toArray();
            if($downData)
            {
                //下载提供数据
                foreach ($lists as $key => &$value)
                {
                    $sale_sum_sql = DB::select("select sum(`goods_price`) as amount from `em_trade_orders` where `status` in ('TRADE_FINISHED') and (`after_sales_status` != 'SUCCESS' or `after_sales_status` is null) and `sku_id`= $value->id");
                    $sale_count_sql = DB::select("select count('*') as sale_count from `em_trade_orders` where `status` in ('TRADE_FINISHED') and (`after_sales_status` != 'SUCCESS' or `after_sales_status` is null) and `sku_id`= $value->id");
                    $value->sale_amount = $sale_sum_sql[0]->amount ?? 0;
                    $value->sale_count = $sale_count_sql[0]->sale_count ;
                }
            }
            else
            {
                foreach ($lists['data'] as $key => &$value)
                {
                    $sale_sum_sql = DB::select("select sum(`goods_price`) as amount from `em_trade_orders` where `status` in ('TRADE_FINISHED') and (`after_sales_status` != 'SUCCESS' or `after_sales_status` is null) and `sku_id`= $value->id");
                    $sale_count_sql = DB::select("select count('*') as sale_count from `em_trade_orders` where `status` in ('TRADE_FINISHED') and (`after_sales_status` != 'SUCCESS' or `after_sales_status` is null) and `sku_id`= $value->id");
                    $value->sale_amount = $sale_sum_sql[0]->amount ?? 0;
                    $value->sale_count = $sale_count_sql[0]->sale_count;
                }

                if (isset($request['total_data_status']) && $request['total_data_status'])
                {
                    $sum_result =$this->totalData($search_model,$sum_data ,1);

                    $lists['total_fee_data'] = [
                        ['value' => $sum_result['sale_amount'], 'dataIndex' => 'sale_amount', 'title' => '销售总金额'],
                    ];
                }

            }


        }

        return $lists;

    }

    /**
     * 统计方法
     *
     * @Author Huiho
     * @param $request
     * @return mixed
     */
    public function totalData($model,$sum_data ,$type = '')
    {
        if($type)
        {
            foreach ($model as $key => &$value)
            {
                $sale_sum_sql = DB::select("select sum(`goods_price`) as amount from `em_trade_orders` where `status` in ('TRADE_FINISHED') and (`after_sales_status` != 'SUCCESS' or `after_sales_status` is null) and `sku_id`= $value->id");
                $value->sale_amount = $sale_sum_sql[0]->amount ?? 0;
            }
        }

        foreach ($sum_data as $key =>&$value)
        {
            $result[$value] = $model->sum($value);
            $result[$value] = round($result[$value],2);
        }

        return $result;
    }


}