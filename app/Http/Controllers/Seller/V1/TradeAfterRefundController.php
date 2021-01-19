<?php
/**
 * @Filename TradeAfterRefundController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use EasyWeChat\Payment\Notify\Refunded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Requests\Platform\refundsPayRequest;
use ShopEM\Http\Requests\Platform\refundsActRequest;
use ShopEM\Http\Controllers\Seller\BaseController;
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
        $input_data['shop_id'] = $this->shop->id;
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
            'field' => $TradeAfterRefundRepository->listShowFields(),
            'total_fee_data' => $total_fee_data,
        ]);
    }


    /**
     * 后台退款处理操作
     * (已支付,退换货流程才需要退款处理,其他取消订单未发货直接默认)
     * @Author hfh_wind
     * @param refundsPayRequest $refunds_id
     * @return mixed
     */
    public function refundsPay(refundsPayRequest $refunds_id)
    {
        $id = $refunds_id->refunds_id;
        $data = TradeRefunds::where(['id' => $id])->first();

        if (empty($data)) {
            return $this->resFailed([], '数据不存在!');
        }
        $data = $data->toArray();
//        dd($data->user_id);
        $pagedata['user']['id'] = $this->platform['id'];
        $pagedata['user']['name'] = $this->platform['username'];
        $user = UserAccount::where(['id' => $data['user_id']])->select('login_account')->first()->toArray();
        $data['user_name'] = $user['login_account'];
        $pagedata['data'] = $data;
        $pagedata['refundFee'] = $data['refund_fee'];

        // 获取退款申请单对应原订单支付信息
        $payment = TradePaybill::where(['tid' => $data['tid'], 'status' => 'succ'])->first();
        $pagedata['payment']['payment_id'] = $payment->payment_id;
        $pagedata['payment']['tid'] = $payment->tid;
        $pagedata['payment']['amount'] = $payment->amount;

        return $this->resSuccess($pagedata);
    }

    /**
     * (已支付,退换货流程才需要退款处理,其他取消订单未发货直接默认)
     * 执行退款操作,线上原路返还退款或者线下转款
     *
     * @Author hfh_wind
     * @param refundsActRequest $refundsAct
     * @return bool|string
     * @throws \ShopEM\Services\Exception
     */
    public function dorefund(refundsActRequest $refundsAct)
    {
//        $postdata = input::get('data');
        $refundsData = $refundsAct->all();

        try {

            $refunds = TradeRefunds::where(['id' => $refundsAct['refunds_id']])->first()->toArray();

            if (!in_array($refunds['status'], ['3', '5', '6'])) {
                throw new \LogicException('当前申请还未审核');
            }

            $refundsData['refunds_type'] = $refunds['refunds_type'];

            if ($refunds['refunds_type'] != '1')//退款类型，售后退款
            {
                $refundsData['aftersales_bn'] = $refunds['aftersales_bn'];
            }
//            $refundsData['op_id'] = $this->user->get_id();

            $refundsData['return_fee'] = $refundsData['money']; //退款总金额，包含红包，方便退款
            $refundsData['refunds_id'] = $refundsData['refunds_id']; //sysaftersales/refunds.php主键，方便退款
            $refundsData['payment_id'] = $refundsData['payment_id']; //退款对应原支付单号
            //创建退款单记录
            $refund = new \ShopEM\Services\TradeAfterRefundService();
            $refundId = $refund->createTradeRefundLog($refundsData);
            if (!$refundId) {
                throw new \LogicException('退款单创建失败');
            }

            // 在线原路退款(用什么支付则退到什么地方)
            if ($refundsData['refund_type'] == 'online' && $refundsData['money'] > 0) {
                $apiParams = [
                    'refund_id'  => $refundId,
                    'payment_id' => $refundsData['payment_id'],
                    'money'      => number_format($refundsData['money'], 2, '.', ''),
                ];
                //订单退款支付请求支付网关
                //$res = 'progress';
                $res = $refund->refundPay($apiParams);
//                $res = 'payment.trade.refundpay' $apiParams;
                if ($res['status'] == 'progress') {
                    //退款原路返还
                    return $res;
                }
                if ($res['status'] != 'succ') {
                    throw new \logicexception('支付失败或者信息未返回');
                }
            }

            //更改退款申请单
            $apiParams = ['refund_fee' => $refundsData['return_fee'], 'id' => $refundsData['refunds_id']];

            //平台对退款申请进行退款处理,比如回库存那些流程
            $refund->refundApplyRestore($apiParams);

        } catch (\Exception $e) {

            $msg = $e->getMessage();

            $this->adminlog("退款单号" . $refunds['refund_bn'] . "退款失败", 0);
            throw new \logicexception($msg);
        }
        //日志
        $this->adminlog("退款单号" . $refunds['refund_bn'] . "退款成功", 1);
        return $this->resSuccess([], '退款成功!');
    }


    /**
     * 驳回商家审核同意退款项
     * @Author hfh_wind
     * @return int
     */
    public function ResetSellerRefund(Request $request)
    {
        $refund_id = $request->id;
        if (empty($refund_id)) {
            return $this->resFailed(406, '参数错误!');
        }

        $data = TradeRefunds::where(['id' => $refund_id])->first();

        if (empty($data) || $data['status'] != 3) {
            return $this->resFailed(700, '数据有误!');
        }


        $tid = $data['tid'];
        $oid = $data['oid'];
        DB::beginTransaction();
        try {

            //售后订单处理
            if ($data['refunds_type'] == '0') {

                TradeAftersales::where('oid', $oid)->update(['progress' => '0']);

                TradeRefunds::where('id', $refund_id)->delete();

            } elseif ($data['refunds_type'] == '1') { //取消订单

                TradeCancel::where(['tid' => $tid])->update(['process' => '0', 'refunds_status' => 'WAIT_CHECK']);

                TradeRefunds::where(['id' => $refund_id])->update(['status' => '0']);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $msg = $e->getMessage();
            $this->adminlog("驳回商家审核同意,订单号-" . $tid . "失败", 0);
            throw new \logicexception($msg);
        }
        DB::commit();
        //日志
        $this->adminlog("驳回商家审核同意,订单号-" . $tid . "成功", 1);

        return $this->resSuccess([], '驳回商家审核同意成功!');
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
        $input_data['shop_id'] = $this->shop->id;;
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

    /**
     * 已退款详情
     *
     * @Author huiho
     * @param Request $request
     * @param TradeAfterRefundRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function onceRefundDetail(Request $request)
    {
        $tid = $request->tid;
        $data = TradeRefundLog::where(['tid' => $tid])->first();

        if (empty($data)) {
            return $this->resFailed([], '数据不存在!');
        }

        return $this->resSuccess($data);
    }



}