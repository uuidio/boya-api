<?php
/**
 * @Filename TradeAfterSalesController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Http\Requests\Shop\TradeSendBackRequest;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeAftersaleLog;
use ShopEM\Models\TradeAftersales;
use ShopEM\Models\UserAccount;
use ShopEM\Models\TradeRefunds;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\WxUserinfo;
use ShopEM\Repositories\TradeAfterSalesRepository;
use ShopEM\Http\Requests\Seller\AfterSalesDetailRequest;
use ShopEM\Http\Requests\Seller\AfterSalesVerificationRequest;


class TradeAfterSalesController extends BaseController
{

    /**
     * 售后列表
     *
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */
    public function Lists(Request $request, TradeAfterSalesRepository $TradeAfterSalesRepository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['shop_id'] = $this->shop->id;

        $lists = $TradeAfterSalesRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $TradeAfterSalesRepository->listShowFields(),
        ]);
    }

    /**
     * 退换货详情
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function detail(AfterSalesDetailRequest $request)
    {

//        $params['oid'] = $request->oid;
        $params['aftersales_bn'] = $request['aftersales_bn'];

//        $params['shop_id'] = $this->shop->id =2;
        $params['shop_id'] = $this->shop->id ;

        $afterSales = new \ShopEM\Services\TradeAfterSalesService();

        $result = $afterSales->AfterSalesGetData($params);

        if ($result['user_id']) {
            $user = UserAccount::where(['id' => $result['user_id']])->first();
            $nickname = '';
            if (isset($user->openid) && $user->openid) {
                $info = WxUserinfo::select('nickname')->where('openid',$user->openid)->first();
                $nickname = isset($info->nickname) ? $info->nickname : ($user->mobile ? substr_replace($user->mobile, '****',3, 4) : '');
            }
            $pagedata['userName'] = $nickname;
        }

        if ($result['tid']) {
            $trade = Trade::where(['tid' =>$result['tid']])->first();
            if ($trade) {
                $pagedata['receiver_name'] = $trade->receiver_name;
                $pagedata['receiver_tel'] = $trade->receiver_tel;
                $pagedata['receiver_province'] = $trade->receiver_province;
                $pagedata['receiver_city'] = $trade->receiver_city;
                $pagedata['receiver_county'] = $trade->receiver_county;
                $pagedata['receiver_address'] = $trade->receiver_address;
                $pagedata['aftersale_trace'] = $trade->aftersale_trace;
            }
        }

        //快递公司代码
           // xxxx
        $pagedata['info'] = $result;
        //商家退款信息
        if (in_array($result['progress'], ['7', '8'])) {

            $refunds = TradeRefunds::where(['oid' => $result['oid']])->select('status', 'total_price')->first();
            if ($refunds) {
                $refunds = $refunds->toArray();
            }
            $pagedata['refunds'] = $refunds;
        }

        return $this->resSuccess([
            'info' => $pagedata,
        ]);

    }


    /**
     * 审核售后申请
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function verification(AfterSalesVerificationRequest $request)
    {
        //total_price
        $postdata = $request->only('aftersales_bn','check_result','aftersales_type','shop_explanation','total_price','refunds_reason');
        $postdata['shop_id'] = $this->shop->id;

        $oid = TradeRefunds::where(['aftersales_bn'=>$postdata['aftersales_bn']])->value('oid');
        $amount = TradeOrder::where(['oid'=>$oid])->value('amount');
        $total_price = false;
        if ($amount>0) {
            if ( empty($postdata['total_price'])) {
                $total_price = true;
            }
        }

        if ($postdata['aftersales_type'] != "EXCHANGING_GOODS"  &&  $total_price &&  $postdata['check_result'] == 'true') {
            return $this->resFailed(702,'请输入退款金额!');
        }

        $afterSales = new \ShopEM\Services\TradeAfterSalesService();
        try {
            $result = $afterSales->afterSalesVerification($postdata);
        } catch (\LogicException $e) {
            $msg = $e->getMessage();
//            throw new \Exception($e->getMessage());
            return $this->resFailed(700,$msg);
        }
        $aType = array(
            'ONLY_REFUND' => '仅退款',
            'REFUND_GOODS' => '退货退款',
            'EXCHANGING_GOODS' => '换货',
        );

        return $this->resSuccess('处理售后申请。售后类型：' . $aType[$postdata['aftersales_type']]);
    }


    /**
     * 填写换货重新发货物流信息
     *
     * @Author djw
     * @return mixed
     */
    public function sendConfirm(TradeSendBackRequest $request)
    {
        $postdata = $request->only('aftersales_bn','corp_code','logi_name','logi_no');

        $postdata['shop_id'] = $this->shop->id;
        $afterSales=new \ShopEM\Services\TradeAfterSalesService();
        try
        {
            $afterSales->sendConfirm($postdata);
            //新增更改售后单状态 2020-03-27 zhh
            $update_data['after_state'] = 1 ;
            TradeAftersales::where('aftersales_bn' , $postdata['aftersales_bn'])->update($update_data);
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->resFailed(700,$msg);
        }

        return $this->resSuccess('售后操作。换货重新发货。申请售后的订单编号是：' . $postdata['aftersales_bn']);
    }

    /**
     * 售后订单单列表导出
     *
     * @Author djw
     * @param Request $request
     * @param TradeAfterSalesRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeAfterSalesDown(Request $request, TradeAfterSalesRepository $Repository)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;

        $lists = $Repository->search($input_data, 1);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        $title = $Repository->listShowFields();

        $return['trade']['tHeader']= array_column($title,'title'); //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['trade']['list']= $lists; //表头

        return $this->resSuccess($return);
    }

    /**
     * 审核重新申请
     * @Author zhh
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function againExamine(Request $request)
    {
        $input = $request->only('tid' , 'apply_again_status');

        $input['apply_again_status'] = $input['apply_again_status'] ?? 0;


        if(!TradeOrder::where('tid', $input['tid'])->where('shop_id', $this->shop->id)->exists())
        {
            return $this->resFailed(700, "订单不存在!");
        }

        try
        {

            TradeOrder::where('tid',$input['tid'])->update($input);
            return $this->resSuccess();
        }
        catch (\Exception $e)
        {
            return $this->resFailed(600);
        }

    }


    /**
     * 售后退货退款列表
     *
     * @Author Huiho
     * @return \Illuminate\Http\JsonResponse
     */
    public function refundGoodsLists(Request $request, TradeAfterSalesRepository $TradeAfterSalesRepository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['shop_id'] = $this->shop->id;
        $input_data['aftersales_type'] = 'REFUND_GOODS';

        $lists = $TradeAfterSalesRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $TradeAfterSalesRepository->refundGoodsFields(),
        ]);
    }


    /**
     * 售后退货退款单导出
     *
     * @Author Huiho
     * @param Request $request
     * @param TradeAfterSalesRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function refundGoodsDown(Request $request, TradeAfterSalesRepository $Repository)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;
        $input_data['aftersales_type'] = 'REFUND_GOODS';
        $lists = $Repository->search($input_data, 1);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        $title = $Repository->refundGoodsFields();

        $return['order']['tHeader']= array_column($title,'title'); //表头
        $return['order']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['order']['list']= $lists; //表头

        return $this->resSuccess($return);
    }

    /**[获取售后日志]
     * @param Request $request
     * @return mixed
     */
    public function getAftersaleTrace(Request $request)
    {
        $input = $request->only('oid');

        if(!TradeOrder::where('oid', $input['oid'])->where('shop_id', $this->shop->id)->exists())
        {
            return $this->resFailed(700, "订单不存在!");
        }

        if(!TradeAftersaleLog::where('oid', $input['oid'])->exists())
        {
            return $this->resFailed(700, "订单不存在!");
        }

        try
        {

            $list = TradeAftersaleLog::where('oid',$input['oid'])->get();
            return $this->resSuccess($list);
        }
        catch (\Exception $e)
        {
            return $this->resFailed(600);
        }
    }

    public function updateAftersaleTrace(Request $request)
    {
        $input = $request->only('tid' , 'aftersale_trace');

        $input['aftersale_trace'] = $input['aftersale_trace'] ?? 0;


        if(!Trade::where('tid', $input['tid'])->where('shop_id', $this->shop->id)->exists())
        {
            return $this->resFailed(700, "订单不存在!");
        }

        try
        {

            Trade::where('tid',$input['tid'])->update($input);
            return $this->resSuccess();
        }
        catch (\Exception $e)
        {
            return $this->resFailed(600);
        }

    }

}
