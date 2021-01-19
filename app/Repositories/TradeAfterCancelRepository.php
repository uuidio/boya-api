<?php
/**
 * @Filename TradeAfterCancelRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use ShopEM\Models\TradeCancel;

class TradeAfterCancelRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id' => ['field' => 'trade_cancels.id', 'operator' => '='],
        'cancel_id' => ['field' => 'trade_cancels.cancel_id', 'operator' => '='],
        'tid' => ['field' => 'trade_cancels.tid', 'operator' => '='],
        'user_id' => ['field' => 'trade_cancels.user_id', 'operator' => '='],
        'shop_id' => ['field' => 'trade_cancels.shop_id', 'operator' => '='],
        'process' => ['field' => 'trade_cancels.process', 'operator' => '='],
        'refunds_status' => ['field' => 'trade_cancels.refunds_status', 'operator' => '='],
        'gm_id' => ['field' => 'trade_cancels.gm_id', 'operator' => '='],
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
            ['dataIndex' => 'cancel_id', 'title' => '取消订单id'],
            ['dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group'])],
            ['dataIndex' => 'shop_name', 'title' => '店铺'],
            ['dataIndex' => 'user_name', 'title' => '会员'],
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'pay_type_name', 'title' => '支付类型'],
            ['dataIndex' => 'refund_fee', 'title' => '实退金额'],
            ['dataIndex' => 'reason', 'title' => '取消原因'],
            ['dataIndex' => 'shop_reject_reason', 'title' => '商家拒绝理由'],
            ['dataIndex' => 'cancel_from_name', 'title' => '取消类型'],
            ['dataIndex' => 'process_name', 'title' => '处理进度'],
            ['dataIndex' => 'refunds_status_text', 'title' => '退款状态'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],
            ['dataIndex' => 'updated_at', 'title' => '最后操作时间'],
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
    public function search($request)
    {
        $sum_result = [];
        $sum_data = ['refund_fee'];

        $model = new TradeCancel();
        $model = filterModel($model, $this->filterables, $request);
        $model->select('trade_cancels.*')
            ->join('trades', 'trades.tid', '=', 'trade_cancels.tid');

        $search_model = $model->get();

        $lists = $model->orderBy('trade_cancels.created_at', 'desc')->orderBy('trade_cancels.id', 'desc')->paginate($request['per_page']);



        $process_text = [
            0 => '提交申请',
            1 => '取消处理',
            2 => '退款处理',
            3 => '完成',
        ];
        $refunds_status_text = [
            'WAIT_CHECK' => '等待审核',
            'WAIT_REFUND' => '等待退款',
            'SHOP_CHECK_FAILS' => '商家审核不通过',
            'FAILS' => '退款失败',
            'SUCCESS' => '退款成功',
        ];


        $cancel_status_text = [
            'WAIT_CHECK' => '等待审核',
            'WAIT_REFUND' => '等待退款',
            'SHOP_CHECK_FAILS' => '商家审核不通过',
            'FAILS' => '取消失败',
            'SUCCESS' => '取消成功',
        ];

        if ($lists) {
            foreach ($lists as $key => $value) {
                $lists[$key]['process_text'] = $process_text[$value['process']];

                $lists[$key]['refunds_status_text'] = $cancel_status_text[$value['refunds_status']];
                if ($value['is_refund']) {
                    $lists[$key]['refunds_status_text'] = $refunds_status_text[$value['refunds_status']];
                }
            }
            if (isset($request['total_data_status']) && $request['total_data_status'])
            {
                $sum_result = $this->totalData($search_model,$sum_data);
                $lists['total_fee_data'] =  [['value' => $sum_result['refund_fee'], 'dataIndex' => 'refund_fee', 'title' => '实退金额汇总']];
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