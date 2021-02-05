<?php
/**
 * @Filename TradeAfterSalesController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Payment;
use ShopEM\Models\ShopRelAddr;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeAftersales;
use ShopEM\Models\TradeOrder;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\AfterSaleApplyRequest;
use ShopEM\Http\Requests\Shop\TradeSendBackRequest;
use ShopEM\Http\Requests\Shop\CancelAftersalesApplyRequest;
use ShopEM\Http\Requests\Shop\AfterSaleCommitApplyRequest;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\TradeRefunds;


class TradeAfterSalesController extends BaseController
{


    public $aftersalesReason = [
        '实物不符',
        '质量原因',
        '现在不想购买',
        '商品价格较贵',
        '价格波动',
        '商品缺货',
        '重复下单',
        '订单商品选择有误',
        '支付方式选择有误',
        '收货信息填写有误',
        '支付方式选择有误',
        '发票信息填写有误',
        '其他原因',
    ];


    /**
     * 会员申请售后展示数据
     *
     *
     * @Author hfh_wind
     * @return mixed
     */

    public function aftersalesApply(AfterSaleApplyRequest $request)
    {
        $pagedata = [];
        $input_data = $request->only('tid', 'oid');
        $tid = $input_data['tid'];
        $oid = $input_data['oid'];
        // 获取商品信息
        $filter['oid'] = $oid;
        $filter['fields'] = '';
        //获取子订单号信息
        $orderInfo = TradeOrder::where(['oid' => $oid])->select('goods_id', 'gc_id', 'end_time', 'goods_serial',
            'goods_name', 'goods_price', 'quantity', 'complaint_status', 'after_sales_status', 'sku_info',
            'goods_image', 'amount', 'total_fee','allow_after')->first();

        if (!empty($orderInfo)) {
            $orderInfo = $orderInfo->toArray();
            if ($orderInfo['allow_after'] == 0) {
                return $this->resFailed(702, '该商品不支持售后');
            }
            //目前没有赠品 ,看后期需不需要添加
            if (!empty($orderInfo['gift_data'])) {
                foreach ($orderInfo['gift_data'] as $gift) {
                    if (!$gift['withoutReturn']) {
                        $pagedata['giftReturnFlag'] = true;
                    }
                    if ($pagedata['giftReturnFlag']) {
                        break;
                    }
                }
            }

            //判断是否可售后状态
            $aftersales = new \ShopEM\Services\TradeAfterSalesService();

            $aftersalesEnabled = $aftersales->isAftersalesEnabled($orderInfo);

            if (!empty($aftersalesEnabled)) {
                $orderInfo['refund_enabled'] = $aftersalesEnabled['refund_enabled'];
                $orderInfo['changing_enabled'] = $aftersalesEnabled['changing_enabled'];

                $pagedata['title'] = "申请退货";
                if ($orderInfo['refund_enabled'] && !$orderInfo['changing_enabled']) {
                    $pagedata['title'] = "申请退货";
                }
                if (!$orderInfo['refund_enabled'] && $orderInfo['changing_enabled']) {
                    $pagedata['title'] = "申请换货";
                }
            }

        }

        $pagedata['tid'] = $tid;
        $pagedata['oid'] = $oid;
        $pagedata['orderInfo'] = $orderInfo;
        $pagedata['reason'] = $this->aftersalesReason;

        return $this->resSuccess($pagedata);
    }


    /**
     *  (商家没有同意之前可以撤销)撤销售后申请
     * @Author hfh_wind
     * @return array
     */
    public function cancelAftersalesApply(CancelAftersalesApplyRequest $request)
    {
        //ONLY_REFUND:只退款，REFUND_GOODS:退货退款，EXCHANGING_GOODS:换货
        $getdata = $request->all();
        $user_id = $this->user->id;
        $data = [
            'tid'             => $getdata['tid'],
            'oid'             => $getdata['oid'],
            'user_id'         => $user_id,
            'aftersales_type' => $getdata['aftersales_type']
        ];

        $aftersales = new \ShopEM\Services\TradeAfterSalesService();

        try {
            $aftersales->cancelftersales($data);
        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            return $this->resFailed(702, $msg);
        }

        $msg = '售后申请撤销成功';
        return $this->resSuccess([], $msg);
    }


    /**
     * 售后申请提交
     *
     * @Author hfh_wind
     * @param AfterSaleCommitApplyRequest $request
     * @return mixed
     * @throws \Exception
     */
    public function commitAftersalesApply(AfterSaleCommitApplyRequest $request)
    {
        $getdata = $request->all();
        $user_id = $this->user->id;
        $data = [
            'tid'                   => $getdata['tid'],
            'oid'                   => $getdata['oid'],
            'user_id'               => $user_id,
            'reason'                => $getdata['reason'],
            'description'           => isset($getdata['description']) ? $getdata['description'] : '',
            'evidence_pic'          => !empty($getdata['evidence_pic']) ? $getdata['evidence_pic'] : '',
            'aftersales_type'       => $getdata['aftersales_type'],
            'apply_refund_price'    => $getdata['apply_refund_price'] ?? 0,
        ];
        //ONLY_REFUND:只退款，REFUND_GOODS:退货退款，EXCHANGING_GOODS:换货

        $aftersales = new \ShopEM\Services\TradeAfterSalesService();

        try {
            if (empty($data['tid'])) {
                return $this->resFailed(702, '网络异常，稍后重试');
            }

            $bill = TradePaybill::where('tid',$data['tid'])->first();
            $payment = Payment::where('payment_id',$bill->payment_id)->select('pay_app','payment_id')->first();
            if (in_array($payment->pay_app,['WalletPhysical','WalletVirtual'])) {
                return $this->resFailed(500, '钱包支付订单不支持取消申请');
            }

            $check = TradeAftersales::where('oid' , $getdata['oid'])->count();
            if($check)
            {
                $data['apply_type'] = 'apply_again';
            }
            $result = $aftersales->aftersalesCreate($data);
        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            return $this->resFailed(702, $msg);
        }

        $msg = '售后申请提交成功';
        return $this->resSuccess([], $msg);
    }


    /**
     * 填写售后回寄物流信息
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function sendback(TradeSendBackRequest $request)
    {
        $postdata = $request->only('aftersales_bn', 'corp_code', 'logi_name', 'logi_no', 'receiver_address', 'mobile');

        $postdata['user_id'] = $this->user->id;
        $afterSales = new \ShopEM\Services\TradeAfterSalesService();
        try {
            $afterSales->sendBack($postdata);
        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            return $this->resFailed(700, $msg);
        }

        return $this->resSuccess([], '回寄物流信息提交成功!!');
    }


    /**
     * 售后订单详情
     *
     * @Author djw
     * @return mixed
     */
    public function detail(Request $request)
    {
        if (!$request->has('aftersales_bn')) {
            return $this->resFailed(406);
        }

        $params['aftersales_bn'] = $request['aftersales_bn'];

        $params['user_id'] = $this->user->id;


        $afterSales = new \ShopEM\Services\TradeAfterSalesService();

        try {
            $result = $afterSales->AfterSalesGetData($params);
        } catch (\LogicException $e) {
            return $this->resFailed(700,$e->getMessage());
        }

        $shop = DB::table('shops')->select('shop_phone')->find($result['shop_id']);
        $result['shop_phone'] = $shop->shop_phone ?? 0;

        //快递公司代码
        // xxxx
        $pagedata['info'] = $result;
        //商家退款信息
        if (in_array($result['progress'], ['7', '8'])) {

            $refunds = TradeRefunds::where(['oid' => $result['oid']])->select('status',
                'total_price')->first()->toArray();
            $pagedata['refunds'] = $refunds;
        }

        if ($result['progress'] == 1) {
            $send_start_at = DB::table('trade_aftersale_logs')
                ->where('oid', $result['oid'])
                ->where('progress', 1)
                ->orderBy('created_at', 'desc')
                ->value('created_at');
            $repository = new \ShopEM\Repositories\ConfigRepository;
            $config = $repository->configItem('shop', 'trade', $result['gm_id']);
            $pagedata['info']['send_timeout'] = 0;
            if (isset($config['send_timeout']) && $config['send_timeout']['value']) {
                $second = $config['send_timeout']['value'] * 86400;
                $send_timeout = $second + strtotime($send_start_at);
                $pagedata['info']['send_timeout'] = date('Y-m-d H:i:s', $send_timeout);
            }
        }

        return $this->resSuccess([
            'info' => $pagedata,
        ]);
    }




    /**
     * 模拟售后申请提交
     *
     * @Author hfh_wind
     * @param AfterSaleCommitApplyRequest $request
     * @return mixed
     * @throws \Exception
     */
    public function commitAftersalesApplyTest(AfterSaleCommitApplyRequest $request)
    {
        $getdata = $request->all();
        $user_id = $request->user_id;
        if ($request->passwork !== 'yitian888') {
            return $this->resFailed(702, '禁止操作');
        }
        $data = [
            'tid'             => $getdata['tid'],
            'oid'             => $getdata['oid'],
            'user_id'         => $user_id,
            'reason'          => '其他原因',
            'description'     => isset($getdata['description']) ? $getdata['description'] : '',
            'evidence_pic'    => !empty($getdata['evidence_pic']) ? $getdata['evidence_pic'] : '',
            'aftersales_type' => $getdata['aftersales_type']
        ];
        //ONLY_REFUND:只退款，REFUND_GOODS:退货退款，EXCHANGING_GOODS:换货

        $aftersales = new \ShopEM\Services\TradeAfterSalesService();

        try {
            $result = $aftersales->aftersalesCreate($data);
        } catch (\LogicException $e) {
            $msg = $e->getMessage();
            return $this->resFailed(702, $msg);
        }

        $msg = '售后申请提交成功';
        return $this->resSuccess([], $msg);
    }

    /**
     * 获取回寄地址
     *
     * @Author huiho
     * @return mixed
     */
    public function sendBackAddr(Request $request)
    {
        $shop_id = $request->shop_id;

        if ($shop_id <= 0)
        {
            return $this->resFailed(414);
        }
        else
        {
            $search_data['shop_id'] = $shop_id;
        }
        $search_data['is_default'] = 1;

        $lists = ShopRelAddr::where($search_data)->get();

        return $this->resSuccess($lists);

    }

    /**
     * 换货确认收货按钮
     *
     * @Author huiho
     * @return mixed
     */
    public function confrimAfter(Request $request)
    {

        $input_data = $request->only('aftersales_bn' );
        $input_data['user_id'] = $this->user->id;

        try
        {
            $update_data['after_state'] = 2 ;
            TradeAftersales::where('aftersales_bn' , $input_data['aftersales_bn'])->where('user_id' , $input_data['user_id'])->update($update_data);
        }
        catch (\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->resFailed(700, $msg);
        }

        return $this->resSuccess([], '确认收货成功!!');
    }
}
