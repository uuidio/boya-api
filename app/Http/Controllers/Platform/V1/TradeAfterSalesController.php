<?php
/**
 * @Filename TradeAfterSalesController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\TradeAftersales;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradeRefunds;
use ShopEM\Repositories\TradeAfterSalesRepository;

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
        $input_data['gm_id'] = $this->GMID;
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
     * 售后单基本信息
     *
     * @Author hfh_wind
     * @param $id
     * @return mixed
     */
    public function detailBasic($id)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $aftersales = TradeAftersales::where(['id' => $id])->first();

        $pagedata['trade'] = TradeOrder::where(['tid' => $aftersales->tid, 'oid' => $aftersales->oid])->select('tid', 'goods_name', 'goods_price', 'amount', 'quantity', 'sendnum', 'goods_serial', 'status', 'goods_id')->first();

        //商家退款信息
        if (in_array($aftersales->progress, ['7', '8'])) {
            $refunds = TradeRefunds::where(['oid' => $aftersales->oid])->select('status', 'total_price')->first()->toArray();
            $pagedata['refunds'] = $refunds;
        }
        $aftersales['sendback_data'] = unserialize($aftersales->sendback_data);
        $pagedata['data'] = $aftersales;
        return $pagedata;
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
        $input_data['gm_id'] = $this->GMID;
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
     * 售后退货退款列表
     *
     * @Author Huiho
     * @return \Illuminate\Http\JsonResponse
     */
    public function refundGoodsLists(Request $request, TradeAfterSalesRepository $TradeAfterSalesRepository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = 15;
        $input_data['gm_id'] = $this->GMID;
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
        $input_data['gm_id'] = $this->GMID;
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


}