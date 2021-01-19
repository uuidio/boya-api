<?php
/**
 * @Filename LogisticsDlycorpsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          swl 2020-3-14
 */

namespace ShopEM\Http\Controllers\Group\V1;


use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\LogisticsDlycorp;
use ShopEM\Http\Requests\Platform\LogisticsRequest;
use ShopEM\Repositories\LogisticsRepository;

class LogisticsController extends BaseController
{
    /**
     * 物流公司列表
     *
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */
    public function logisticsLists(Request $request, LogisticsRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');

        $lists = $repository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 添加物流公司
     *
     * @Author hfh_wind
     * @param LogisticsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function LogisticsAdd(LogisticsRequest $request)
    {
        $data = $request->only('corp_code', 'full_name', 'corp_name', 'website', 'request_url', 'order_sort', 'is_show');

        $msg_text="创建物流" . $data['corp_name'];
        try {

            LogisticsDlycorp::create($data);
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess([], '添加成功!');
    }


    /**
     * 物流公司详情
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function LogisticsDetail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = Shop::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }


    /**
     * 编辑物流公司
     *
     * @Author hfh_wind
     * @param LogisticsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function LogisticsEdit(LogisticsRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414, '编辑的物流公司id必填!');
        }

        $data = $request->only('corp_code', 'full_name', 'corp_name', 'website', 'request_url', 'order_sort', 'is_show');
        $Logistics = LogisticsDlycorp::find($id);
        if (empty($Logistics)) {
            return $this->resFailed(701);
        }

        $msg_text="修改物流" . $Logistics['corp_name'];
        try {

            LogisticsDlycorp::where(['id' => $id])->update($data);
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }

        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess([], '编辑成功!');
    }

    /**
     * 删除
     *
     * @Author hfh_wind
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function LogisticsDel($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $info = LogisticsDlycorp::find($id);
        if (empty($info)) {
            return $this->resFailed(700);
        }
        $msg_text = "删除物流" . $info['corp_name'];

        try {
            $info->destroy($id);
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess();
    }


}