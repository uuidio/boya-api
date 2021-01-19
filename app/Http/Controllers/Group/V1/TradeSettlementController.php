<?php
/**
 * @Filename TradeController.php
 * @Author   swl 2020-3-12
 */

namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
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
     * 日结列表
     * 传递gm_id可查询对应项目的日结列表，不传则代表查询所有日结报表
     * @Author swl 2020-3-12
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeDayLists(Request $request, TradeDayRepository $Repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page') ? config('app.per_page') : 10;
        $input_data['total_data_status'] = true;
        $lists = $Repository->search($input_data);
        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $Repository->groupListFields(),
            'total_fee_data' => $total_fee_data,
        ]);
    }


    /**
     * 月结列表
     * 传递gm_id可查询对应项目的月结列表，不传则代表查询所有月结报表
     * @Author swl 2020-3-12
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeMonthLists(Request $request, TradeMonthRepository $Repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page') ? config('app.per_page') : 10;
        $input_data['total_data_status'] = true;

        $lists = $Repository->search($input_data);

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $Repository->groupListFields(),
            'total_fee_data' => $total_fee_data,
        ]);
    }

    /**
     * 日结下载
     * 传递gm_id可下载对应的项目日结
     * @Author swl 2020-3-12
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeDayListsDown(Request $request, TradeDayRepository $Repository)
    {
        $input_data = $request->all();
        $lists = $Repository->search($input_data,1);
        //获取下载表头
        $title=$Repository->groupListFields();
        $return['trade']['tHeader']= array_column($title,'title'); //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段
        $return['trade']['list']= $lists; //表头

        return $this->resSuccess($return);
    }

     /**
     * 月结下载
     * 传递gm_id可下载对应的项目月结
     * @Author swl 2020-3-12
     * @param Request $request
     * @param TradeRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function TradeMonthListsDown(Request $request, TradeMonthRepository $Repository)
    {
        $input_data = $request->all();
        $lists = $Repository->search($input_data,1);
        //获取下载表头
        $title=$Repository->groupListFields();
        $return['trade']['tHeader']= array_column($title,'title'); //表头
        $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段
        $return['trade']['list']= $lists; //表头

        return $this->resSuccess($return);
    }

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
        // $input_data['gm_id'] = $this->GMID;
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
        // $input_data['gm_id'] = $this->GMID;
        
        $input_data['per_page'] = config('app.per_page') ? config('app.per_page') : 10;
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



}