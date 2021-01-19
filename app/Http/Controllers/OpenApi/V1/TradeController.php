<?php


namespace ShopEM\Http\Controllers\OpenApi\V1;


use ShopEM\Http\Controllers\OpenApi\BaseController;
use ShopEM\Http\Requests\OpenApi\FetchRefundTradeRequest;
use ShopEM\Http\Requests\OpenApi\FetchTradeRequest;
use ShopEM\Http\Requests\OpenApi\SynStockLogRequest;
use ShopEM\Models\GoodsSku;
use ShopEM\Models\Payment;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeAftersales;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\TradeRefunds;
use ShopEM\Models\TradeStockReturnLog;

class TradeController extends BaseController
{
    public function fetchTrade(FetchTradeRequest $request)
    {
        $input = $request->only('gm_id', 'shop_id', 'status', 'create_start', 'create_end', 'pay_start', 'pay_end','trade_bn');
        $page_num = $request->get('page_num',20);
        if ($page_num > 500) $page_num = 500;
        $filter = [
            'gm_id'     => ['field' => 'gm_id', 'operator' => '='],
            'shop_id'   => ['field' => 'shop_id', 'operator' => '='],
            'status'    => ['field' => 'status', 'operator' => '='],
            'trade_bn'  => ['field' => 'tid', 'operator' => '='],
            'create_start'  => ['field' => 'created_at', 'operator' => '>='],
            'create_end'  => ['field' => 'created_at', 'operator' => '<='],
            'pay_start'  => ['field' => 'pay_time', 'operator' => '>='],
            'pay_end'  => ['field' => 'pay_time', 'operator' => '<='],
        ];
        $trades = filterModel((new Trade()),$filter,$input)
            ->select('tid','status','created_at','pay_time','gm_id','shop_id','amount','total_fee','discount_fee','activity_sign')
            ->orderBy('id','desc')
            ->paginate($page_num)
            ->toArray();
        $return = [
            'current_page'  => $trades['current_page'],
            'total_page'    => $trades['last_page'],
            'per_page'      => $trades['per_page'],
        ];
        foreach ($trades['data'] as $trade) {
            $bill = TradePaybill::where('tid', $trade['tid'])->select('payment_id')->first();
            $bill_count = TradePaybill::where('payment_id', $bill->payment_id)->count();
            $payment = Payment::where('payment_id',$bill->payment_id)->select('status','memo','points_fee','platform_coupon_fee')->first();
            $pack = [
                'gm_id'  =>  $trade['gm_id'],
                'gm_name'  =>  $trade['gm_name'],
                'shop_id'  =>  $trade['shop_id'],
                'shop_name'  =>  $trade['shop_name'],
                'status'  =>  $trade['status'],
                'status_name'  =>  $trade['status_text'],
                'create_time'  =>  $trade['created_at'],
                'pay_time'  =>  $trade['pay_time'],
                'trade_id'  =>  $trade['tid'],
                'amount'    =>  $trade['amount'],
                'total'     =>  $trade['total_fee'],
                'discount'  =>  $trade['discount_fee'],
                'activity_sign' =>  $trade['activity_sign'],
                'payment'   =>  [
                    'pay_status'    =>  $payment->status,
                    'pay_type'    =>  $payment->memo,
                    'point_reduce'  =>  $payment->points_fee / $bill_count,
                    'coupon_reduce' =>  $payment->platform_coupon_fee / $bill_count,
                ],
            ];
            foreach ($trade['trade_order'] as $k => $v) {
                $pack['order'][$k]['order_id'] = $v['oid'];
                $pack['order'][$k]['goods_name'] = $v['goods_name'];
                $pack['order'][$k]['sku_id'] = $v['sku_id'];
                $sku = GoodsSku::where('id',$v['sku_id'])->select('goods_barcode')->first();
                if ($sku) {
                    $pack['order'][$k]['barcode'] = $sku->goods_barcode;
                } else {
                    $pack['order'][$k]['barcode'] = null;
                }
                $pack['order'][$k]['quantity'] = $v['quantity'];
            }
            $return['data'][] = $pack;
        }
        return $this->resSuccess($return);
    }

    public function fetchRefundTrade(FetchRefundTradeRequest $request)
    {
        $input = $request->only('gm_id', 'shop_id', 'status', 'apply_start', 'apply_end','trade_bn','order_bn','after_sale_bn');
        $page_num = $request->get('page_num',20);
        if ($page_num > 500) $page_num = 500;
        $filter = [
            'gm_id'     => ['field' => 'gm_id', 'operator' => '='],
            'shop_id'   => ['field' => 'shop_id', 'operator' => '='],
            'status'    => ['field' => 'progress', 'operator' => '='],
            'trade_bn'  => ['field' => 'tid', 'operator' => '='],
            'order_bn'  => ['field' => 'oid', 'operator' => '='],
            'after_sale_bn'  => ['field' => 'aftersales_bn', 'operator' => '='],
            'apply_start'  => ['field' => 'created_at', 'operator' => '>='],
            'apply_end'  => ['field' => 'created_at', 'operator' => '<='],
        ];
        $after_sales = filterModel((new TradeAftersales()),$filter,$input)
            ->where('aftersales_type', 'REFUND_GOODS')
            ->select('tid','progress','created_at','gm_id','shop_id','oid','sku_id','title','aftersales_bn','num')
            ->orderBy('id','desc')
            ->paginate($page_num)
            ->toArray();
        $return = [
            'current_page'  => $after_sales['current_page'],
            'total_page'    => $after_sales['last_page'],
            'per_page'      => $after_sales['per_page'],
        ];
        foreach ($after_sales['data'] as $after_sale) {
            $trade = Trade::where('tid',$after_sale['tid'])->select('created_at','pay_time','amount','total_fee','discount_fee')->first();
            $refund = TradeRefunds::where('aftersales_bn',$after_sale['aftersales_bn'])->select('refund_fee','refund_point')->first();
            $pack = [
                'gm_id'  =>  $after_sale['gm_id'],
                'gm_name'  =>  $after_sale['gm_name'],
                'shop_id'  =>  $after_sale['shop_id'],
                'shop_name'  =>  $after_sale['shop_name'],
                'status'  =>  $after_sale['progress'],
                'apply_time'    =>  $after_sale['created_at'],
                'order_time'    =>  $trade->created_at,
                'pay_time'      =>  $trade->pay_time,
                'amount'        =>  $trade->amount,
                'total'         =>  $trade->total_fee,
                'discount'      =>  $trade->discount_fee,
                'trade_id'  =>  $after_sale['tid'],
                'order_id'  =>  $after_sale['oid'],
                'after_sale_bn' =>  $after_sale['aftersales_bn'],
                'goods_name' =>  $after_sale['title'],
                'sku_id' =>  $after_sale['sku_id'],
                'quantity' =>  $after_sale['num'],
            ];
            if ($refund) {
                $pack['refund'] = [
                    'type'      =>  '原路退款',
                    'amount'    =>  $refund->refund_fee,
                    'point'     =>  $refund->refund_point,
                ];
            } else {
                $pack['refund'] = [];
            }
            $return['data'][] = $pack;
        }
        return $this->resSuccess($return);
    }

    /**
     * 线下库存回传
     * @param SynStockLogRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function synStockLog(SynStockLogRequest $request)
    {
        try {
            $logData = $request->only('gm_id','tid','status','reason');
            $trade = Trade::where('tid',$logData['tid'])->select('id','status','syn_stock_status')->first();
            if (empty($trade)) return $this->resFailed(414,'无此订单');
            $trade->syn_stock_status = $logData['status'];
            $trade->save();
            TradeStockReturnLog::create($logData);
            return $this->resSuccess();
        } catch (\Exception $exception) {
            return $this->resFailed(500,'回传记录失败');
        }
    }
}
