<?php
/**
 * @Filename        TradePointController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\TradeRefunds;
use ShopEM\Repositories\TradeRepository;

class TradePointController extends BaseController
{
    /**
     * [lists 订单积分明细列表]
     * @Author djw
     * @param  Request $request [请求对象]
     * @return [type]           [description]
     */
    public function lists(Request $request, TradeRepository $tradeRepository)
    {
        $input_data = $request->all();

        $input_data['per_page'] = config('app.per_page');
        $input_data['status'] = 4;
        $input_data['is_point'] = true;
        $input_data['gm_id'] = $this->GMID;
        $input_data['total_data_status'] = 'trade_point';
        $lists = $tradeRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        foreach ($lists['data'] as $key => &$value) {
            $value['remark'] = $value['obtain_point_fee'] ? '确认收货增积分' : '--';
            $value['obtain_point_fee'] = $value['obtain_point_fee'] ?: '--';
            $value['consume_point_fee'] = $value['consume_point_fee'] ?: '--';
            $trade_order = [
                'data'=>$value['trade_order'],
                'field'=>[
                    ['key' => 'oid', 'dataIndex' => 'oid', 'title' => '子订单号'],
                    ['key' => 'goods_image', 'dataIndex' => 'goods_image', 'title' => '商品主图','scopedSlots'=>['customRender'=>'goods_image']],
                    ['key' => 'goods_name', 'dataIndex' => 'goods_name', 'title' => '商品名称'],
                    ['key' => 'goods_price', 'dataIndex' => 'goods_price', 'title' => '商品价格'],
                    ['key' => 'quantity', 'dataIndex' => 'quantity', 'title' => '购买数量'],
                    ['key' => 'sku_info', 'dataIndex' => 'sku_info', 'title' => 'SKU信息'],
                    ['key' => 'refund_info.refund_point', 'dataIndex' => 'refund_info.refund_point', 'title' => '积分退还'],
                    ['key' => 'refund_info.refund_time', 'dataIndex' => 'refund_info.refund_time', 'title' => '退还时间'],
                    ['key' => 'refund_info.refunds_reason', 'dataIndex' => 'refund_info.refunds_reason', 'title' => '退还原因'],
                ],
            ];
            $value['refund_order'] = $trade_order;
        }
        unset($value);
        $field = [
            ['dataIndex' => 'user_mobile', 'title' => '手机号'],
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'amount', 'title' => '消费金额'],
            ['dataIndex' => 'consume_point_fee', 'title' => '使用积分'],
            ['dataIndex' => 'pay_time', 'title' => '订单支付时间'],
            ['dataIndex' => 'obtain_point_fee', 'title' => '积分增加'],
            ['dataIndex' => 'remark', 'title' => '增积分来源'],
            ['dataIndex' => 'end_time', 'title' => '增加积分时间'],
            ['dataIndex' => 'shop_id', 'title' => '下单店铺ID'],
            ['dataIndex' => 'shop_info.shop_name', 'title' => '下单店铺名称'],
            ['dataIndex' => 'push_crm_text', 'title' => 'CRM推送状态'],
        ];

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $field,
            'total_fee_data' => $total_fee_data,
        ]);
    }



    /**
     * 订单积分列表导出
     *
     * @Author hfh_wind
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradePointDown(Request $request, TradeRepository $Repository)
    {
        $input_data = $request->all();

        $input_data['per_page'] = config('app.per_page');
        $input_data['status'] = 4;
        $input_data['is_point'] = true;
        $input_data['gm_id'] = $this->GMID;
        
        $lists = $Repository->search($input_data,1);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        foreach ($lists as $key => &$value) {
            $value['remark'] = $value['obtain_point_fee'] ? '确认收货增积分' : '--';
            $value['obtain_point_fee'] = $value['obtain_point_fee'] ?: '--';
            $value['consume_point_fee'] = $value['consume_point_fee'] ?: '--';
        }
        $title = [
            ['dataIndex' => 'user_mobile', 'title' => '手机号'],
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'amount', 'title' => '消费金额'],
            ['dataIndex' => 'consume_point_fee', 'title' => '使用积分'],
            ['dataIndex' => 'pay_time', 'title' => '订单支付时间'],
            ['dataIndex' => 'obtain_point_fee', 'title' => '积分增加'],
            ['dataIndex' => 'remark', 'title' => '增积分来源'],
            ['dataIndex' => 'end_time', 'title' => '增加积分时间'],
            ['dataIndex' => 'shop_id', 'title' => '下单店铺ID'],
            ['dataIndex' => 'shop_info.shop_name', 'title' => '下单店铺名称']
        ];

        $return['trade']['tHeader']= array_column($title,'title'); //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['trade']['list']= $lists; //表头

        return $this->resSuccess($return);
    }
}
