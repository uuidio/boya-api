<?php
/**
 * @Filename TradeAfterRefundRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;


use ShopEM\Models\TradeRefunds;

class TradeAfterRefundRepository
{

    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'id' => ['field' => 'id', 'operator' => '='],
        'refund_bn' => ['field' => 'refund_bn', 'operator' => '='],
        'tid' => ['field' => 'tid', 'operator' => '='],
        'oid' => ['field' => 'oid', 'operator' => '='],
        'user_id' => ['field' => 'user_id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
//        'status' => ['field' => 'status', 'operator' => '='],
        'created_start'  => ['field' => 'created_at', 'operator' => '>='],
        'created_end'  => ['field' => 'created_at', 'operator' => '<='],
        'refund_at_start'  => ['field' => 'refund_at', 'operator' => '>='],
        'refund_at_end'  => ['field' => 'refund_at', 'operator' => '<='],
    ];

    /**
     * 查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields($is_show='')
    {
        return [
            //['dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group'])],
            ['dataIndex' => 'gm_name', 'title' => '所属项目'],
            ['dataIndex' => 'refund_bn', 'title' => '退款申请编号'],
            ['dataIndex' => 'aftersales_bn', 'title' => '售后申请编号'],
            ['dataIndex' => 'payment_id', 'title' => '商户订单号'],
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'oid', 'title' => '子订单号'],
            ['dataIndex' => 'refunds_type_name', 'title' => '退款类型'],
            ['dataIndex' => 'trade_type', 'title' => '订单类型'],
            ['dataIndex' => 'status_name', 'title' => '状态'],
            ['dataIndex' => 'refunds_reason', 'title' => '申请退款原因'],
            ['dataIndex' => 'order_price', 'title' => '订单金额'],
            ['dataIndex' => 'total_price', 'title' => '应退金额'],
            ['dataIndex' => 'refund_fee', 'title' => '实退金额'],
            ['dataIndex' => 'points_fee', 'title' => '积分抵扣金额'],
            ['dataIndex' => 'consume_point_fee', 'title' => '抵扣的积分'],
            ['dataIndex' => 'coupon_fee', 'title' => '优惠支付金额'],
            ['dataIndex' => 'refund_point', 'title' => '实退积分'],
            ['dataIndex' => 'return_freight_name', 'title' => '是否退运费'],
            ['dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],
            ['dataIndex' => 'updated_at', 'title' => '最后操作时间'],
            ['dataIndex' => 'refund_at', 'title' => '退款时间'],

        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author hfh_wind
     * @return array
     *
     */
    public function listShowFields($is_show='')
    {
        return listFieldToShow($this->listFields($is_show));
    }

    /**
     * 订单查询
     *
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function search($request,$downData='')
    {

        $sum_result = [];
        $sum_data = ['order_price','total_price','refund_fee','points_fee','consume_point_fee','coupon_fee','refund_point'];

        if (isset($request['mobile'])) {
            $user = \ShopEM\Models\UserAccount::where('mobile',$request['mobile'])->first();
            if (!$user) {
                return [];
            }
            $request['user_id'] = $user->id;
        }

        $model = new TradeRefunds();
        $model = filterModel($model, $this->filterables, $request);
        $status = $request['status'] ?? 0;
        if ($status == 1) {
            $model = $model->whereIn('status',['3','5','6']);
        } else if ($status == 2) {
            $model = $model->where('status','1');
        } else {
            $model = $model->whereIn('status',['1','2','3','5', '6']);
        }
        if($downData){
            //下载提供数据
            $lists=$model->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();
        }else{
            $search_model = $model->get();

            $lists = $model->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($request['per_page']);
            
            if ($lists) {
                $lists = $lists->toArray();
            }
            if (isset($request['total_data_status']) && $request['total_data_status'])
            {
                $sum_result =$this->totalData($search_model,$sum_data);
                $lists['total_fee_data'] =  [
                    ['value' => $sum_result['order_price'], 'dataIndex' => 'order_price', 'title' => '订单金额汇总'],
                    ['value' => $sum_result['total_price'], 'dataIndex' => 'total_price', 'title' => '应退金额汇总'],
                    ['value' => $sum_result['refund_fee'], 'dataIndex' => 'refund_fee', 'title' => '实退金额汇总'],
                    ['value' => $sum_result['points_fee'], 'dataIndex' => 'points_fee', 'title' => '积分抵扣金额汇总'],
                    ['value' => $sum_result['consume_point_fee'], 'dataIndex' => 'consume_point_fee', 'title' => '抵扣的积分汇总'],
                    ['value' => $sum_result['coupon_fee'], 'dataIndex' => 'coupon_fee', 'title' => '优惠支付金额汇总'],
                    ['value' => $sum_result['refund_point'], 'dataIndex' => 'refund_point', 'title' => '实退积分汇总'],
                ];
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