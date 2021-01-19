<?php
/**
 * @Filename TradePaymentController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use Illuminate\Http\Request;
use ShopEM\Models\Trade;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\DownloadLog;
use ShopEM\Repositories\TradePaymentRepository;
use ShopEM\Jobs\DownloadLogAct;


class TradePaymentController extends BaseController
{


    /**
     * 列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @param TradePaymentRepository $tradePaymentRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, TradePaymentRepository $tradePaymentRepository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = $request->per_page?$request->per_page : 15;
        $input_data['total_data_status'] = true;

        $lists = $tradePaymentRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $tradePaymentRepository->listShowFields(),
            'total_fee_data' => $total_fee_data,
        ]);
    }


    /**
     * 订单详情
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $tid = $request->tid;

        if (empty($tid)) {
            return $this->resFailed(414);
        }

        $trade = Trade::find($tid);

        if (empty($trade))
            return $this->resFailed(700);

        return $this->resSuccess($trade);
    }


    /**
     * 支付单号单列表导出
     *
     * @Author Huiho
     * @param Request $request
     * @param TradePaymentRepository $tradeRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function PaymentDown(Request $request, TradePaymentRepository $Repository)
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

    /**
     * 新支付单号单列表导出
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function newPaymentDown(Request $request)
    {
        $input_data = $request->all();

        if (isset($input_data['s'])) {
            unset($input_data['s']);
        }

        $insert['type'] = 'TradePayment';
        $insert['desc'] = json_encode($input_data);
        $insert['gm_id'] = 0;

        $res = DownloadLog::create($insert);

        $data['log_id'] = $res['id'];

        DownloadLogAct::dispatch($data);

        return $this->resSuccess('导出中请等待!');
    }


}