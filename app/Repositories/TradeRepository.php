<?php
/**
 * @Filename        TradeRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeCancel;
use ShopEM\Models\Shop;

class TradeRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'tid'              => ['field' => 'trades.tid', 'operator' => '='],
        'user_id'          => ['field' => 'trades.user_id', 'operator' => '='],
        'shop_id'          => ['field' => 'trades.shop_id', 'operator' => '='],
        'receiver_name'    => ['field' => 'trades.receiver_name', 'operator' => '='],
        'receiver_tel'     => ['field' => 'trades.receiver_tel', 'operator' => '='],
        'status'           => ['field' => 'trades.status', 'operator' => '='],
        'status_in'           => ['field' => 'trades.status', 'operator' => 'in'],
        'created_start'    => ['field' => 'trades.created_at', 'operator' => '>='],
        'created_end'      => ['field' => 'trades.created_at', 'operator' => '<='],
        'pay_start'        => ['field' => 'trades.pay_time', 'operator' => '>='],
        'pay_end'          => ['field' => 'trades.pay_time', 'operator' => '<='],
        'activity_sign'    => ['field' => 'trades.activity_sign', 'operator' => '='],
        'activity_sign_arr'=> ['field' => 'trades.activity_sign', 'operator' => 'in'],
        'activity_sign_id' => ['field' => 'trades.activity_sign_id', 'operator' => '='],
        'is_virtual_trade' => ['field' => 'trades.is_virtual_trade', 'operator' => '='],
        'gm_id'            => ['field' => 'trades.gm_id', 'operator' => '='],
        'pick_type'        => ['field' => 'trades.pick_type', 'operator' => '='],
        'virtual_status'   => ['field' => 'trades.virtual_status', 'operator' => '='],
        'is_distribution'  => ['field' => 'trades.is_distribution', 'operator' => '='],
        'invoices'         => ['field' => 'invoices', 'operator' => '='],
    ];

    //状态转换
    private $tradeStatus = [
        1 => 'WAIT_BUYER_PAY',    // 已下单等待付款
        2 => 'WAIT_SELLER_SEND_GOODS',    // 已付款等待发货
        3 => 'WAIT_BUYER_CONFIRM_GOODS',  // 已发货等待确认收货
        4 => 'TRADE_FINISHED',    // 已完成
        5 => 'TRADE_CLOSED',//已关闭(退款关闭订单)
        6 => 'TRADE_CLOSED_BY_SYSTEM',// 已关闭(卖家或买家主动关闭)
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields($is_show='')
    {
        return [
            ['dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group']),'width'=>150],
            ['dataIndex' => 'payment_id', 'title' => '商户订单号','width'=>150],
            ['dataIndex' => 'tid', 'title' => '订单号','width'=>150],
            ['dataIndex' => 'status_text', 'title' => '订单状态','width'=>150],
            ['dataIndex' => 'activity_sign_text', 'title' => '活动类型','width'=>150],
            ['dataIndex' => 'group_status_text', 'title' => '拼团状态','width'=>150],
            ['dataIndex' => 'cancel_text', 'title' => '取消状态','width'=>150],
            ['dataIndex' => 'user_mobile', 'title' => '下单手机号','width'=>150],
            ['dataIndex' => 'amount_text', 'title' => '实付金额','width'=>150],
            ['dataIndex' => 'total_fee', 'title' => '商品总金额','width'=>150],
            ['dataIndex' => 'pick_type_name', 'title' => '提货方式','width'=>150],
            ['dataIndex' => 'receiver_name', 'title' => '收货人姓名','width'=>150],
            ['dataIndex' => 'receiver_tel', 'title' => '收货人电话','width'=>150],
            ['dataIndex' => 'receiver_addr', 'title' => '收货人地址','width'=>150],
            ['dataIndex' => 'pay_time', 'title' => '付款时间','width'=>150],
            ['dataIndex' => 'created_at', 'title' => '订单创建时间','width'=>150],
            ['dataIndex' => 'confirm_at', 'title' => '用户确认收货时间/商品核销时间','width'=>150],
            ['dataIndex' => 'add_remarks', 'title' => '追加备注','width'=>150],

        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields($is_show='')
    {
        return listFieldToShow($this->listFields($is_show));
    }

    /**
     * 订单查询
     *
     * @Author moocde <mo@mocode.cn>
     * @param $request
     * @return mixed
     */
    public function search($request,$downData='')
    {
        $sum_result = [];
        $sum_data = ['total_fee','amount','consume_point_fee','obtain_point_fee'];

        //如果status传的是数字的话，需要转换成对应的状态值
        if (isset($request['status'])) {
            if (in_array($request['status'], [5,6])) 
            {
                $request['status_in'] = ['TRADE_CLOSED','TRADE_CLOSED_BY_SYSTEM'];
                unset($request['status']);
            }
            elseif (isset($this->tradeStatus[$request['status']])) 
            {
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
        if (isset($request['shop_name'])) {
            $shop_id = Shop::where('shop_name', $request['shop_name'])->value('id');
            if (!$shop_id) {
                return [];
            }
            $request['shop_id'] = $shop_id;
        }//转换shop_id

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
            $lists=$model->orderBy('id', 'desc')->distinct()->get();
        }else{
            $search_model = $model->get();
            $lists = $model->orderBy('updated_at', 'desc')->distinct()->paginate($request['per_page']);
        }


        //返回使用过积分抵扣的实际金额
        if ($lists) {
            $lists = $lists->toArray();
            /*foreach ($lists['data'] as $k => $v) {
                $lists['data'][$k]['amount'] = round($v['amount']- $v['point_discount_fee'], 2);
            }*/
        }

        if (isset($request['total_data_status']) && $request['total_data_status'] == 'trade_main')
        {
            $sum_result =$this->totalData($search_model,$sum_data);
            $lists['total_fee_data'] =  [['value' => $sum_result['amount'], 'dataIndex' => 'amount', 'title' => '商品实付汇总'], ['value' => $sum_result['total_fee'],  'dataIndex'=> 'total_fee', 'title' => '商品总金额汇总']];
        }

        if (isset($request['total_data_status']) && $request['total_data_status'] == 'trade_point')
        {
            $sum_result =$this->totalData($search_model,$sum_data);
            $lists['total_fee_data'] =  [
                ['value' => $sum_result['amount'], 'dataIndex' => 'amount', 'title' => '消费金额汇总'],
                ['value' => $sum_result['consume_point_fee'],  'dataIndex'=> 'consume_point_fee', 'title' => '使用积分汇总'],
                ['value' => $sum_result['obtain_point_fee'],  'dataIndex'=> 'obtain_point_fee', 'title' => '积分增加汇总'],
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
