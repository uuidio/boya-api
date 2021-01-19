<?php


/**
 * @Author: swl
 * @Date:   2020-03-10 
 */
namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Http\Request;
use ShopEM\Models\RateTraderate;
use ShopEM\Repositories\RateRepository;

class RateController extends BaseController
{
	/**
     * 评价列表
     *
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
	public function lists(Request $request,RateRepository $repository)
	{
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $lists = $repository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->platformListShowFields('group'),
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
