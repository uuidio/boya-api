<?php
/**
 * @Filename        TradePaymentRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Repositories;

use ShopEM\Models\Trade;
use ShopEM\Models\TradePaybill;

class TradePaymentRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'trade_paybills.gm_id', 'operator' => '='],
        'tid' => ['field' => 'trade_paybills.tid', 'operator' => '='],
        'payment_id' => ['field' => 'trade_paybills.payment_id', 'operator' => '='],
        'created_start'  => ['field' => 'trade_paybills.created_at', 'operator' => '>='],
        'created_end'  => ['field' => 'trade_paybills.created_at', 'operator' => '<='],
        'payed_time_start'  => ['field' => 'trade_paybills.payed_time', 'operator' => '>='],
        'payed_time_end'  => ['field' => 'trade_paybills.payed_time', 'operator' => '<='],

    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'gm_name', 'title' => '项目名称','width'=>150],
            ['dataIndex' => 'shop_name', 'title' => '店铺名称','width'=>150],
            ['dataIndex' => 'tid', 'title' => '订单号','width'=>150],
            ['dataIndex' => 'payment_id', 'title' => '商户订单号','width'=>150],
            ['dataIndex' => 'trade_no', 'title' => '微信订单号','width'=>150],
            ['dataIndex' => 'amount', 'title' => '支付金额','width'=>150],
            ['dataIndex' => 'status_text', 'title' => '支付状态','width'=>150],
            ['dataIndex' => 'pay_app_name', 'title' => '支付方式','width'=>150],
            ['dataIndex' => 'created_at', 'title' => '支付开始时间','width'=>150],
            ['dataIndex' => 'payed_at', 'title' => '支付完成时间','width'=>150],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 订单查询
     *
     * @Author moocde <mo@mocode.cn>
     * @param $request
     * @return mixed
     */
    public function search($request , $downData = '')
    {
        $sum_result = [];
        $sum_data = ['amount'];
        $model = new TradePaybill();
        $model = filterModel($model, $this->filterables, $request);

        if (isset($request['shop_id']) && $request['shop_id'])
        {
            $model = $model->select('trade_paybills.*', 'trades.shop_id')->leftJoin('trades' , 'trade_paybills.tid' , '=' , 'trades.tid')->where('trades.shop_id' , '=' , $request['shop_id']);
        }

        if($downData){
            //下载提供数据
            $lists=$model->orderBy('trade_paybills.id', 'desc')->get();
        }else{
            $search_model = $model->get();

            $lists = $model->orderBy('trade_paybills.id', 'desc')->paginate($request['per_page']);
            if (isset($request['total_data_status']) && $request['total_data_status'])
            {
                $sum_result =$this->totalData($search_model,$sum_data);
            }
            $lists['total_fee_data'] =  [['value' => $sum_result['amount'], 'dataIndex' => 'amount', 'title' => '商品实付汇总']];

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
    public function totalData($model,$sum_data)
    {
        $result = [];
        foreach ($sum_data as $key =>&$value)
        {
            $result[$value] = $model->sum($value);
            $result[$value] = round($result[$value],2);
        }

        return $result;
    }


}