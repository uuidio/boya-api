<?php
/**
 * @Filename RateController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use Illuminate\Http\Request;
use ShopEM\Models\RateTraderate;
use ShopEM\Repositories\RateRepository;

class RateController extends BaseController
{

    protected $rateRepository;

    public function __construct(RateRepository $rateRepository)
    {
        parent::__construct();
        $this->rateRepository = $rateRepository;
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
        $input_data['gm_id'] = $this->GMID;

        $lists = $this->rateRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $this->rateRepository->platformListShowFields(),
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

        $detail = RateTraderate::find($request->id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }
}