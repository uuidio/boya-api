<?php
/**
 * @Filename        RateAppealController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Models\RateTraderate;
use ShopEM\Repositories\RateRepository;
use ShopEM\Http\Requests\Seller\AddRateAppealRequest;
use ShopEM\Services\RateAppealService;

class RateAppealController extends BaseController
{
    /**
     * 申诉列表
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

        if (isset($input_data['appeal_again']) && !in_array($input_data['appeal_again'], array(0,1))) {
            unset($input_data['appeal_again']);
        }

        $input_data['is_appeal'] = 'NO_APPEAL';
        if (isset($input_data['appeal_status']) && !in_array($input_data['appeal_status'], array('REJECT', 'WAIT', 'SUCCESS', 'CLOSE'))) {
            unset($input_data['appeal_again']);
            unset($input_data['is_appeal']);
        }

        $lists = $rateRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $rateRepository->appealListShowFields(),
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
        $rate = RateTraderate::where('id', $request->id)->where('appeal_status', '!=', 'NO_APPEAL')->first();

        if (empty($rate))
            return $this->resFailed(700);

        return $this->resSuccess($rate);
    }

    //评价申诉
    public function appeal(AddRateAppealRequest $request)
    {
        $input_data = $request->only('rate_id', 'appeal_type');
        $input_data['evidence_pic'] = $evidencePic = $request->evidence_pic ?: '';
        $input_data['is_again'] = $request->is_again ?: false;
        $input_data['content'] = $request->appeal_content;
        $input_data['shop_id'] = $this->shop->id;

        if( $evidencePic )
        {
            $input_data['evidence_pic'] = implode(',', $evidencePic);
        }

        if( $input_data['is_again'] && !$evidencePic )
        {
            return $this->resFailed(702, '再次申诉时，必须上传图片凭证');
        }

        DB::beginTransaction();
        try {
            $flag = RateAppealService::createAppeal($input_data);
            if (!$flag) {
                return $this->resFailed(702, '申诉提交失败，请重新再试');
            }
            DB::commit();
//            $this->sellerlog('提交评价申诉。申诉的评价ID是 '.$input_data['rate_id']);
            return $this->resSuccess('申诉已提交，请耐心等待，我们将会在10个工作日内给予审核回复，谢谢！ ');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }
    }
}