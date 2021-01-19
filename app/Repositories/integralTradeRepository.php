<?php
/**
 * @Filename        integralTradeRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          Huiho
 */

namespace ShopEM\Repositories;

use ShopEM\Models\Trade;
use ShopEM\Models\GmPlatform;

class integralTradeRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'tid' => ['field' => 'trades.tid', 'operator' => '='],
        'user_id' => ['field' => 'trades.user_id', 'operator' => '='],
        'shop_id' => ['field' => 'trades.shop_id', 'operator' => '='],
        'receiver_name' => ['field' => 'trades.receiver_name', 'operator' => '='],
        'receiver_tel' => ['field' => 'trades.receiver_tel', 'operator' => '='],
        'status' => ['field' => 'trades.status', 'operator' => '='],
        'created_start'  => ['field' => 'trades.created_at', 'operator' => '>='],
        'created_end'  => ['field' => 'trades.created_at', 'operator' => '<='],
        'pay_start'  => ['field' => 'trades.pay_time', 'operator' => '>='],
        'pay_end'  => ['field' => 'trades.pay_time', 'operator' => '<='],
        'activity_sign'  => ['field' => 'trades.activity_sign', 'operator' => '='],
        'activity_sign_id'  => ['field' => 'trades.activity_sign_id', 'operator' => '='],
        'gm_id'  => ['field' => 'trades.gm_id', 'operator' => '='],
    ];

    //状态转换
    private $tradeStatus = [
        1 => 'WAIT_BUYER_PAY',    // 已下单等待付款
        2 => 'WAIT_SELLER_SEND_GOODS',    // 已付款等待发货
        3 => 'WAIT_BUYER_CONFIRM_GOODS',  // 已发货等待确认收货
        4 => 'TRADE_FINISHED',    // 已完成
    ];

    /**
     * 查询字段
     *
     * @return array
     */
    public function listFields($is_show = '')
    {
        return [
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'user_mobile', 'title' => '下单手机号'],
            ['dataIndex' => 'amount', 'title' => '实付金额'],
            ['dataIndex' => 'total_fee', 'title' => '商品总金额'],
            ['dataIndex' => 'consume_point_fee', 'title' => '买家消耗积分','hide'=>isshow_models($is_show,['normal'])],
            ['dataIndex' => 'consume_point_fee', 'title' => '买家消耗牛币','hide'=>isshow_models($is_show,['self'])],
            ['dataIndex' => 'pick_type_name', 'title' => '提货方式'],
            ['dataIndex' => 'receiver_name', 'title' => '收货人姓名'],
            ['dataIndex' => 'receiver_tel', 'title' => '收货人电话'],
            ['dataIndex' => 'receiver_addr', 'title' => '收货人地址'],
            ['dataIndex' => 'status', 'title' => '订单状态'],
            ['dataIndex' => 'pay_time', 'title' => '付款时间'],
            ['dataIndex' => 'created_at', 'title' => '订单创建时间'],
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
     * 订单查询
     *
     * @param $request
     * @return mixed
     */
    public function search($request,$downData='')
    {

        $sum_result = [];
        $sum_data = ['amount','total_fee','consume_point_fee'];

        //如果status传的是数字的话，需要转换成对应的状态值
        if (isset($request['status'])) {
            if (isset($this->tradeStatus[$request['status']])) {
                $request['status'] = $this->tradeStatus[$request['status']];
            }
            $request['no_cancel'] = true;
        }
        if (isset($request['mobile'])) {
            $user = \ShopEM\Models\UserAccount::where('mobile',$request['mobile'])->first();
            if (!$user) {
                return [];
            }
            $request['user_id'] = $user->id;
        }

        $model = new Trade();
        $model = filterModel($model, $this->filterables, $request);
        if (isset($request['status']) && $request['status'] == 'TRADE_FINISHED') {
            $model = $model->where('buyer_rate', 0);
        }

        if (isset($request['goods_name']) && $request['goods_name']) {
            $goods_name = $request['goods_name'];
            $model = $model->whereIn('trades.tid', function ($query) use ($goods_name) {
                $query->select('tid')
                    ->from('trade_orders')
                    ->where('goods_name', 'like', '%' . $goods_name . '%');
            });
        }

        if (isset($request['is_point']) && $request['is_point']) {
            $model = $model->where(function ($query) {
                $query->where('obtain_point_fee', '!=', '0')->orWhere('consume_point_fee', '!=', '0');
            });
        }

        if (isset($request['no_cancel']) && $request['no_cancel']) {
            $model = $model->select('trades.*')->leftJoin('trade_cancels', 'trade_cancels.tid', '=', 'trades.tid')->where( function ($query){
                $query->whereNull('cancel_id')->orWhereIn('trade_cancels.process', ['1','3']);
            });
            $model = $model->leftJoin('trade_aftersales', 'trade_aftersales.tid', '=', 'trades.tid')->where( function ($query){
                $query->whereNull('aftersales_bn')->orWhereIn('trade_aftersales.status', ['2','3']);
            });
        }

        if($downData){
            //下载提供数据
            $lists=$model->orderBy('id', 'desc')->get();
        }else{
            $search_model = $model->get();
            $lists = $model->orderBy('updated_at', 'desc')->paginate($request['per_page']);
        }

        //返回使用过积分抵扣的实际金额
        if ($lists) {
            $lists = $lists->toArray();

        }

        if (isset($request['total_data_status']) && $request['total_data_status'])
        {
            $type = '积分';
            if ($request['gm_id'] == GmPlatform::gmSelf())
            {
                $type = '牛币';
            }
            $sum_result =$this->totalData($search_model,$sum_data);
            $lists['total_fee_data'] =  [
                ['value' => $sum_result['amount'], 'dataIndex' => 'amount', 'title' => '商品实付汇总'],
                ['value' => $sum_result['total_fee'],  'dataIndex'=> 'total_fee', 'title' => '商品总金额汇总'],
                ['value' => $sum_result['consume_point_fee'],  'dataIndex'=> 'consume_point_fee', 'title' => '买家消耗'.$type.'汇总'],
                ];
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