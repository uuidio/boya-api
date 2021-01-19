<?php
/**
 * @Filename TradeController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use Illuminate\Http\Request;
use ShopEM\Models\TradeMonthSettleAccount;
use ShopEM\Repositories\TradeRepository;
use ShopEM\Repositories\TradeDayDetailRepository;
use ShopEM\Repositories\TradeDayRepository;
use ShopEM\Repositories\TradeMonthRepository;
use ShopEM\Repositories\TradeDayDetailGoodsRepository;


class TradeSettlementController extends BaseController
{


    /**
     * 日结算明细列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeDayDetailLists(Request $request, TradeDayDetailRepository $Repository)
    {
        $input_data = $request->all();

        $input_data['per_page'] = config('app.per_page') ? config('app.per_page') : 10;
        $input_data['gm_id'] = $this->GMID;
        $input_data['total_data_status'] = true;

        $lists = $Repository->search($input_data);

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $Repository->listFields(),
            'total_fee_data' => $total_fee_data,
        ]);
    }



    /**
     * 日结算明细下载
     *
     * @Author hfh_wind
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeDayDetailListsDown(Request $request, TradeDayDetailRepository $Repository)
    {
        $input_data = $request->all();
        $input_data['gm_id'] = $this->GMID;

        $lists = $Repository->search($input_data,1);
        //获取下载表头
        $title=$Repository->listFields();
        $return['trade']['tHeader']= array_column($title,'title'); //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['trade']['list']= $lists; //表头

        return $this->resSuccess($return);
    }



    /**
     * 日结算商品明细
     *
     * @Author hfh_wind
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeDayDetailGoodsLists(Request $request, TradeDayDetailGoodsRepository $Repository)
    {
        $input_data = $request->all();
        $input_data['gm_id'] = $this->GMID;
        
        $input_data['per_page'] = config('app.per_page') ? config('app.per_page') : 10;

        $lists = $Repository->search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $Repository->listFields()

        ]);
    }


    /**
     * 日结列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeDayLists(Request $request, TradeDayRepository $Repository)
    {
        $input_data = $request->all();
        $input_data['gm_id'] = $this->GMID;
        $input_data['total_data_status'] = true;

        $input_data['per_page'] = config('app.per_page') ? config('app.per_page') : 10;

        $lists = $Repository->search($input_data);

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $Repository->listFields(),
            'total_fee_data' => $total_fee_data,
        ]);
    }




    /**
     * 日结下载
     *
     * @Author hfh_wind
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeDayListsDown(Request $request, TradeDayRepository $Repository)
    {
        $input_data = $request->all();
        $input_data['gm_id'] = $this->GMID;
        $lists = $Repository->search($input_data,1);
        //获取下载表头
        $title=$Repository->listFields();
        $return['trade']['tHeader']= array_column($title,'title'); //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['trade']['list']= $lists; //表头

        return $this->resSuccess($return);
    }



    /**
     * 月结列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeMonthLists(Request $request, TradeMonthRepository $Repository)
    {
        $input_data = $request->all();

        $input_data['per_page'] = config('app.per_page') ? config('app.per_page') : 10;
        $input_data['gm_id'] = $this->GMID;
        $input_data['total_data_status'] = true;
        $lists = $Repository->search($input_data);

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $Repository->listFields(),
            'total_fee_data' => $total_fee_data,
        ]);
    }


    /**
     * 月结下载
     *
     * @Author hfh_windT
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeMonthListsDown(Request $request, TradeMonthRepository $Repository)
    {
        $input_data = $request->all();
        $input_data['gm_id'] = $this->GMID;

        $lists = $Repository->search($input_data,1);
        //获取下载表头
        $title=$Repository->listFields();
        $return['trade']['tHeader']= array_column($title,'title'); //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段

        $return['trade']['list']= $lists; //表头

        return $this->resSuccess($return);
    }



    /**
     *  确认结算
     * @Author hfh_wind
     * @return int
     */
    public function SettleMentReview(Request $request)
    {
        $id = $request->id;
        $status = $request->status ? $request->status : 0;
        if (empty($id)) {
            return $this->resFailed(414, 'id参数错误!');
        }
        $monthInfo = TradeMonthSettleAccount::find($id);

        if (empty($monthInfo)) {
            return $this->resFailed(700, '查询数据为空!');
        }

        if ($monthInfo['status'] =='1') {
            return $this->resFailed(700, '已经审核过了,请勿再审核!');
        }

        try {

            $monthInfo->update(['status' => $status]);

        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }


}