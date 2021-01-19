<?php
/**
 * @Filename        integralTradeRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          Huiho
 */

namespace ShopEM\Repositories;

use ShopEM\Models\WechatTradeCheck;

class WechatTradeCheckRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'status' => ['field' => 'status', 'operator' => '='],
        'deal_status' => ['field' => 'deal_status', 'operator' => '='],
        'trade_type' => ['field' => 'trade_type', 'operator' => '='],
        'import_status' => ['field' => 'import_status', 'operator' => '='],
        'trade_at_start' => ['field' => 'trade_at', 'operator' => '>='],
        'trade_at_end' => ['field' => 'trade_at', 'operator' => '<='],
        'refund_at_start' => ['field' => 'refund_at', 'operator' => '>='],
        'refund_at_end' => ['field' => 'refund_at', 'operator' => '<='],
        'updated_at_start' => ['field' => 'updated_at', 'operator' => '<='],
        'updated_at_end' => ['field' => 'updated_at', 'operator' => '<='],
        'abnormal_reason_text' => ['field' => 'abnormal_reason', 'operator' => '='],
    ];


    /**
     * 查询字段
     *
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'gm_name', 'title' => '项目'],
            ['dataIndex' => 'shop_name', 'title' => '商铺'],
            ['dataIndex' => 'trade_at', 'title' => '微信交易时间'],
            ['dataIndex' => 'refund_at', 'title' => '微信退款时间'],
            ['dataIndex' => 'trade_type_text', 'title' => '交易状态'],
            ['dataIndex' => 'tid', 'title' => '订单编号'],
            ['dataIndex' => 'oid', 'title' => '子订单编号'],
            ['dataIndex' => 'refund_bn', 'title' => '退款单号'],
            ['dataIndex' => 'payment_id', 'title' => '商户订单号'],
            ['dataIndex' => 'trade_no', 'title' => '微信支付单号'],
            ['dataIndex' => 'payed_fee', 'title' => '支付金额'],
            ['dataIndex' => 'refund_fee', 'title' => '退款金额'],
            ['dataIndex' => 'status_text', 'title' => '对账状态'],
            ['dataIndex' => 'deal_status_text', 'title' => '返款状态'],
            ['dataIndex' => 'check_at', 'title' => '对账时间'],
            ['dataIndex' => 'deal_at', 'title' => '返款时间'],
            ['dataIndex' => 'abnormal_reason_text', 'title' => '异常原因'],
        ];
    }


    /**
     * 查询字段
     *
     * @return array
     */
    public function abnormalListFields()
    {
        return
        [
            ['dataIndex' => 'trade_type_text', 'title' => '交易类型'],
            ['dataIndex' => 'refund_bn', 'title' => '退款单号'],
            ['dataIndex' => 'payment_id', 'title' => '商户订单号'],
            ['dataIndex' => 'trade_no', 'title' => '微信支付单号'],
            ['dataIndex' => 'payed_fee', 'title' => '支付金额'],
            ['dataIndex' => 'refund_fee', 'title' => '退款金额'],
            ['dataIndex' => 'abnormal_reason_text', 'title' => '异常原因'],
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

    //状态转换
    private $tradeStatus = [
        1 => 'REPEAT',    // 重复
        2 => 'EMPTY',    // 数据为空
        3 => 'MISMATCH',  // 数据不匹配
        4 => 'NOT_TRADE',    // 无交易订单
        5 => 'MISMATCH_AMOUNT',    // 无交易订单
    ];

    /**
     * 订单查询
     *
     * @param $request
     * @param string $downData
     * @param int $count
     * @return mixed
     */
    public function search($request,$downData='', $count = 0)
    {
        //如果status传的是数字的话，需要转换成对应的状态值
        if (isset($request['abnormal_reason_text'])) {
            if (isset($this->tradeStatus[$request['abnormal_reason_text']]))
            {
                $request['abnormal_reason_text'] = $this->tradeStatus[$request['abnormal_reason_text']];
            }
        }

        $model = new WechatTradeCheck();
        $model = filterModel($model, $this->filterables, $request);

        if ($count) return $model->count();

        if($downData)
        {
            $lists = $model->orderBy('updated_at', 'desc')->get();
        }
        else
        {
            $lists = $model->orderBy('updated_at', 'desc') ->paginate($request['per_page']);

        }

        return $lists;
    }


}
