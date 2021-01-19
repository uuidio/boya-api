<?php
/**
 * @Filename RateAppealController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use Illuminate\Http\Request;
use ShopEM\Models\RateAppeal;
use ShopEM\Models\RateTraderate;
use ShopEM\Models\Trade;
use ShopEM\Repositories\RateAppealRepository;
use ShopEM\Services\RateAppealService;

class RateAppealController extends BaseController
{

    protected $rateAppealRepository;

    public function __construct(RateAppealRepository $rateAppealRepository)
    {
        parent::__construct();
        $this->rateAppealRepository = $rateAppealRepository;
    }

    /**
     * 评价列表
     *
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');

        //status = 1为未处理，= 2为已处理
        if (isset($input_data['status'])) {
            if ($input_data['status'] == 1) {
                $input_data['is_wait'] = 'WAIT';
            } else if ($input_data['status'] == 2) {
                $input_data['is_not_wait'] = 'WAIT';
            }
        }
        $input_data['gm_id'] = $this->GMID;
        $lists = $this->rateAppealRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $this->rateAppealRepository->listShowFields(),
        ]);
    }

    /**
     * 评价详情
     *
     * @Author djw
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = RateAppeal::find($request->id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        $detail['rate_info'] = RateTraderate::find($detail['rate_id']);

        $detail['trade_info'] = $detail['rate_info'] ? Trade::find($detail['rate_info']['tid']) : null;
        if ($detail['trade_info']) {
            $detail['trade_info']['consign_date'] = date('Y-m-d H:i:s',$detail['trade_info']['consign_time']);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 审核评价申诉
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        $input_data = $request->all();
        if (!isset($input_data['result']) || !isset($input_data['appeal_id'])) {
            return $this->resFailed(414);
        }
        $appealId = $input_data['appeal_id'];
        $params['result'] = $input_data['result'];
        $params['reject_reason'] = isset($input_data['reject_reason']) ? $input_data['reject_reason'] : null;

        try
        {
            RateAppealService::check($appealId, $params);
//            $this->adminlog("评价申诉审核[{$params['id']}]", 1);
            return $this->resSuccess();
        }
        catch(\Exception $e)
        {
//            $this->adminlog("评价申诉审核[{$params['appeal_id']}]", 0);
            return $this->resFailed(702, $e->getMessage());
        }
    }
}