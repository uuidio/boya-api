<?php
/**
 * @Filename TradeAfterRefundController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradeAftersales;
use ShopEM\Models\TradeRefunds;
use ShopEM\Services\TradeAfterRefundService;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\AfterRefundRequest;


class TradeAfterRefundController extends BaseController
{

    /**
     * 消费者提交售后申请,商家审核,转售后流程或生成相关退款单
     *
     * @Author hfh_wind
     * @param AfterRefundRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function apply(AfterRefundRequest $request , TradeAfterRefundService $TradeAfterRefund)
    {
        $data = $request->all();
        $data['user_id'] = $this->user['id'];

        $TradeAfterRefund->apply($data);

        return $this->resSuccess([], '售后单创建成功!!');
    }




    /**
     *  变更订单状态
     *
     * @Author hfh_wind
     * @param $aftersalesBn
     * @param $status
     * @return mixed
     */
    public function updateStatus($aftersalesBn,$status)
    {
        return TradeRefunds::where(['aftersales_bn' => $aftersalesBn])->update(['status'=>$status]);
    }

}