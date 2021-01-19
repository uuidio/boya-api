<?php


/**
 * @Author: swl
 * @Date:   2020-03-09 
 */
namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use EasyWeChat\Payment\Notify\Refunded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Requests\Platform\refundsPayRequest;
use ShopEM\Http\Requests\Platform\refundsActRequest;
use ShopEM\Models\TradeAftersales;
use ShopEM\Models\TradeCancel;
use ShopEM\Models\TradeOrder;
use ShopEM\Repositories\TradeAfterRefundRepository;
use ShopEM\Models\TradeRefunds;
use ShopEM\Models\UserAccount;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\TradeRefundLog;

class TradeAfterRefundController extends BaseController
{
	 /**
     * 退款列表
     *
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */
    public function refundsLists(Request $request, TradeAfterRefundRepository $TradeAfterRefundRepository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['total_data_status'] = true;
        $lists = $TradeAfterRefundRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        //$lists = $lists->toArray();
        foreach ($lists['data'] as $key => &$value) {
            $tradeOrderModel = TradeOrder::where('tid', $value['tid']);
            if ($value['oid']) {
                $tradeOrderModel = $tradeOrderModel->where('oid', $value['oid']);
            }
            $value['trade_order'] = $tradeOrderModel->get();
            $trade_order = [
                'data'  => $value['trade_order'],
                'field' => [
                    ['key'         => 'goods_image',
                     'dataIndex'   => 'goods_image',
                     'title'       => '商品主图',
                     'scopedSlots' => ['customRender' => 'goods_image']
                    ],
                    ['key' => 'goods_name', 'dataIndex' => 'goods_name', 'title' => '商品名称'],
                    ['key' => 'goods_price', 'dataIndex' => 'goods_price', 'title' => '商品价格'],
                    ['key' => 'quantity', 'dataIndex' => 'quantity', 'title' => '购买数量'],
                ],
            ];
            $value['trade_order'] = $trade_order;
        }

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $TradeAfterRefundRepository->listShowFields('group'),
            'total_fee_data' => $total_fee_data,
        ]);
    }

      /**
     * 售后订单单列表导出
     *
     * @Author djw
     * @param Request $request
     * @param TradeAfterRefundRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeRefundsDown(Request $request, TradeAfterRefundRepository $Repository)
    {
        $input_data = $request->all();

        $lists = $Repository->search($input_data, 1);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        $title = $Repository->listShowFields();

        $return['order']['tHeader']= array_column($title,'title'); //表头
        $return['order']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['order']['list']= $lists; //表头

        return $this->resSuccess($return);
    }


}
