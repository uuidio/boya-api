<?php
/**
 * @Filename        TradeDayDetailRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\TradeDaySettleAccountDetail;

class TradeDayDetailRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'tid' => ['field' => 'tid', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'settle_type' => ['field' => 'settle_type', 'operator' => '='],
        'settle_time_start' => ['field' => 'settle_time', 'operator' => '>='],
        'settle_time_end' => ['field' => 'settle_time', 'operator' => '<='],
    ];

    /**
     * 查询字段
     *
     * @Author hfh
     * @return array
     */
    public function listFields()
    {
        //根据前端要求修改返回的数据格式
        return [
            ['dataIndex' => 'id', 'title' => 'ID','width'=>150],
            ['dataIndex' => 'tid', 'title' => '订单号','width'=>150],
            ['dataIndex' => 'shop_name', 'title' => '店铺名称','width'=>150],
            ['dataIndex' => 'pay_time', 'title' => '支付时间','width'=>150],
            ['dataIndex' => 'pay_type_name', 'title' => '支付方式','width'=>150],
            ['dataIndex' => 'goods_price_amount', 'title' => '商品总金额','width'=>150],
            ['dataIndex' => 'shop_act_fee', 'title' => '店铺优惠','width'=>150],
            ['dataIndex' => 'platform_act_fee', 'title' => '平台优惠','width'=>150],
            ['dataIndex' => 'points_fee', 'title' => '积分抵扣金额','width'=>150],
            ['dataIndex' => 'points', 'title' => '使用积分','width'=>150],
            ['dataIndex' => 'post_fee', 'title' => '邮费','width'=>150],
//            ['dataIndex' => 'payed', 'title' => '实付','width'=>150],
            ['dataIndex' => 'shop_rate', 'title' => '店铺扣点比例','width'=>150],
            ['dataIndex' => 'point_rate', 'title' => '积分比例','width'=>150],
            ['dataIndex' => 'shop_rate_fee', 'title' => '店铺扣点金额','width'=>150],
            ['dataIndex' => 'refund_fee_text', 'title' => '退款金额','width'=>150],
            ['dataIndex' => 'settlement_fee', 'title' => '结算金额','width'=>150],
            ['dataIndex' => 'settle_type_text', 'title' => '结算类型','width'=>150],
            ['dataIndex' => 'settle_time', 'title' => '结算时间','width'=>150],
            ['dataIndex' => 'payed', 'title' => '实付金额','width'=>150],
//            ['dataIndex' => 'created_at', 'title' => '创建时间','width'=>150],
            ['dataIndex' => 'goods_cost_amount', 'title' => '成本价汇总','width'=>150],
            ['dataIndex' => 'profit_amount', 'title' => '利润额汇总','width'=>150],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author hfh
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 获取列表数据
     *
     * @Author hfh
     * @param int $shop_id
     * @return mixed
     */
    public function search($request,$downData='')
    {
        $sum_result = [];
        $sum_data = ['goods_price_amount','shop_act_fee','platform_act_fee','points_fee','post_fee','shop_rate_fee','refund_fee','settlement_fee','payed','goods_cost_amount','profit_amount'];

        $Model = new TradeDaySettleAccountDetail();
        $Model = filterModel($Model, $this->filterables, $request);

        if($downData){
            //下载提供数据
            $return=$Model->orderBy('id', 'desc')->get();
        }else{
            $search_model = $Model->get();

            $return=$Model->orderBy('id', 'desc')->paginate($request['per_page']);
            if (isset($request['total_data_status']) && $request['total_data_status'])
            {
                $sum_result =$this->totalData($search_model,$sum_data);
            }
            $return['total_fee_data'] =  [
                ['value' => $sum_result['goods_price_amount'], 'dataIndex' => 'goods_price_amount', 'title' => '商品总金额汇总'],
                ['value' => $sum_result['shop_act_fee'], 'dataIndex' => 'shop_act_fee', 'title' => '店铺优惠汇总'],
                ['value' => $sum_result['platform_act_fee'], 'dataIndex' => 'platform_act_fee', 'title' => '平台优惠汇总'],
                ['value' => $sum_result['points_fee'], 'dataIndex' => 'points_fee', 'title' => '积分抵扣金额汇总'],
                ['value' => $sum_result['post_fee'], 'dataIndex' => 'post_fee', 'title' => '抵扣的积分汇总'],
                ['value' => $sum_result['shop_rate_fee'], 'dataIndex' => 'shop_rate_fee', 'title' => '店铺扣点金额汇总'],
                ['value' => $sum_result['refund_fee'], 'dataIndex' => 'refund_fee', 'title' => '退款金额汇总'],
                ['value' => $sum_result['payed'], 'dataIndex' => 'payed', 'title' => '实付金额汇总'],
                ['value' => $sum_result['goods_cost_amount'], 'dataIndex' => 'goods_cost_amount', 'title' => '成本价汇总'],
                ['value' => $sum_result['profit_amount'], 'dataIndex' => 'profit_amount', 'title' => '利润额汇总'],
            ];
        }

        return $return;
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