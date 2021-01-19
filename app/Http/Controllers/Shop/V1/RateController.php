<?php
/**
 * @Filename        RateController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\AddRateRequest;
use ShopEM\Http\Requests\Shop\AddRateAppendRequest;
use ShopEM\Services\RateTraderateService;
use ShopEM\Services\RateAppendService;
use ShopEM\Models\RateTraderate;

class RateController extends BaseController
{
    /**
     * 创建评价
     *
     * @Author djw
     * @param AddRateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createRate(AddRateRequest $request)
    {
        $input_data = $request->only('tid', 'tally_score', 'attitude_score', 'delivery_speed_score', 'logistics_service_score', 'rate_data');
        $input_data['logistics_service_score'] = empty($input_data['logistics_service_score']) ? 5 : $input_data['logistics_service_score'];
        $input_data['user_id'] = $this->user->id;

        $rateData = [];
        foreach ($input_data['rate_data'] as $key => $row) {
            $row['content'] = isset($row['content']) ? $row['content'] : '';
            $row['result'] = isset($row['result']) ? $row['result'] : '';
            $row['anony'] = isset($row['anony']) && ($row['anony'] == 'true') ? 1 : 0;
            if ($row['result'] != "good" && !trim($row['content'])) {
                return $this->resFailed(702, '中评和差评时，评价必填');
            }

            if (mb_strlen($row['content'], 'UTF-8') > 300) {
                return $this->resFailed(702, '评价内容字数超过300上限');
            }

            $rateData[$key] = $row;
            $rateData[$key]['rate_pic'] = '';
            if (isset($row['rate_pic']) && $row['rate_pic']) {
                $rateData[$key]['rate_pic'] = json_encode($row['rate_pic']);
            }
        }
        $input_data['rate_data'] = $rateData;

        try
        {
            RateTraderateService::createRate($input_data);
        }
        catch (\Exception $e)
        {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }

    public function lists(){
        $per_page = config('app.per_page');
        $lists = RateTraderate::where('user_id', $this->user['id'])->where( 'disabled', 0)->orderBy('id', 'desc')->paginate($per_page);
        return $this->resSuccess($lists);
    }

    /**
     * 更新评论
     *
     * @Author djw
     * @param AddRateAppendRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateRate(AddRateAppendRequest $request)
    {
        $pic = $request->rate_pic;
        $rateId = $request->rate_id;
        $input_data = $request->only('result', 'content');
        $input_data['rate_pic'] = null;
        $input_data['user_id'] = $this->user->id;
        if( $pic )
        {
            $input_data['rate_pic'] = implode(',',$pic);
        }
        try
        {
            RateTraderateService::updateRate($rateId, $input_data);
        }
        catch(\LogicException $e)
        {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess('修改成功');
    }

    /**
     * 追加评论
     *
     * @Author djw
     * @param AddRateAppendRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function appendRate(AddRateAppendRequest $request)
    {
        $pic = $request->pic;
        $input_data = $request->only('rate_id', 'content');
        $input_data['user_id'] = $this->user->id;
        $input_data['pic'] = null;
        if( $pic )
        {
            $input_data['pic'] = implode(',',$pic);
        }

        try
        {
            $result = RateAppendService::createAppend($input_data);
        }
        catch(\LogicException $e)
        {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess($result);
    }
}