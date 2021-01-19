<?php
/**
 * @Filename        RateController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Models\RateTraderate;
use ShopEM\Repositories\RateRepository;
use ShopEM\Http\Requests\Seller\replyRequest;
use ShopEM\Services\RateTraderateService;
use ShopEM\Services\RateAppendService;

class RateController extends BaseController
{
    /**
     * 评价列表
     *
     * @Author djw
     * @param Request         $request
     * @param RateRepository $rateRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, RateRepository $rateRepository)
    {
        $input_data = $request->all();
        $input_data['shop_id'] = $this->shop->id;
        $input_data['per_page'] = config('app.per_page');

        if (isset($input_data['is_content']) && $input_data['is_content']) {
            $input_data['content_not_null'] = '';
            $input_data['content_not_default'] = '系统默认好评';
        }
        if (isset($input_data['is_pic']) && $input_data['is_pic']) {
            $input_data['is_pic'] = '';
        }
        if (isset($input_data['is_reply']) && $input_data['is_reply']) {
            $input_data['is_reply'] = 1;
        }

        $lists = $rateRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $rateRepository->listShowFields(),
        ]);
    }

    /**
     * 评价详情
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $rate = RateTraderate::find($request->id);

        if (empty($rate))
            return $this->resFailed(700);

        return $this->resSuccess($rate);
    }

    /**
     * 回复评价
     *
     * @Author djw
     * @param replyRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reply(replyRequest $request)
    {
        $replyType = $request->replyType == 'append' ? 'append' : 'add';

        $rateId = $request->rate_id;
        $replyContent = trim($request->reply_content);
        $shopId = $this->shop->id;

        try {
            if( $replyType == 'add')
            {
                //回复订单评价
                $result = RateTraderateService::reply($rateId, $replyContent, $shopId);
            }
            else if($replyType == 'append')
            {
                //回复订单追加评价
                $result = RateAppendService::reply($rateId, $replyContent, $shopId);
            }
            if (!$result) {
                return $this->resFailed(702, '回复失败');
            }

//            $this->sellerlog('回复评价。回复评价id是'.$rateId);
            return $this->resSuccess('回复成功');
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }
    }
}