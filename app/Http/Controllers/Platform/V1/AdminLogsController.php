<?php
/**
 * @Filename        AdminLogsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\PlatformAdminLogs;
use ShopEM\Repositories\PlatformAdminLogsRepository;

class AdminLogsController extends BaseController
{
    /**
     * 日志列表
     * @Author hfh_wind
     * @param PlatformAdminLogsRepository $repository
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(PlatformAdminLogsRepository $repository, Request $request)
    {
        $input_data = $request->all();
        $input_data['gm_id'] = $this->GMID;
        $lists = $repository->search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listFields,
        ]);
    }

    /**
     * 日志详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $admin = PlatformAdminLogs::find(intval($request->id));

        if (empty($admin)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($admin);
    }


    /**
     * 删除数据
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        try {
            $admin = PlatformAdminLogs::find(intval($request->id));
            if (empty($admin)) {
                return $this->resFailed(700, '删除的数据不存在');
            }
            $admin->delete();
            return $this->resSuccess();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->resFailed(600);
        }
    }
}