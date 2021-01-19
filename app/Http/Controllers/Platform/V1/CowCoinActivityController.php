<?php
/**
 * @Filename        CowCoinActivityController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Jobs\DownloadLogAct;
use ShopEM\Models\DownloadLog;
use ShopEM\Models\UserAccount;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\YiTianUserCard;
use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Repositories\CowCoinActivityRepository;

class CowCoinActivityController extends BaseController
{
    /**
     * [lists 积分转牛币列表]
     * @Author djw
     * @param  Request $request [请求对象]
     * @return [type]           [description]
     */
    public function lists(Request $request, CowCoinActivityRepository $repository)
    {
        $data = $request->all();

        $data['per_page'] = isset($data['per_page']) ? $data['per_page'] : config('app.per_page');
        $data['gm_id'] = $this->GMID;
        $lists = $repository->lists($data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }



        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }
    /**
     * [lists 积分转牛币列表筛选   -弃用]
     * @Author djw
     * @param  Request $request [请求对象]
     * @return [type]           [description]
     */
    public function search(Request $request, CowCoinActivityRepository $repository)
    {
        $data = $request->all();
        $data['per_page'] = isset($data['per_page']) ? $data['per_page'] : config('app.per_page');
        $data['gm_id'] = $this->GMID;
        $lists = $repository->lists($data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }


        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }
    /**
     * 积分转牛币信息导出
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cowCoinLogDown(Request $request)
    {
        $input_data = $request->all();

        $input_data['gm_id'] = $this->GMID;
        if (isset($input_data['s'])) {
            unset($input_data['s']);
        }

        $insert['type'] = 'UserCowCoinLog';
        $insert['desc'] = json_encode($input_data);
        $insert['gm_id'] = $input_data['gm_id'];

        $res = DownloadLog::create($insert);

        $data['log_id'] = $res['id'];
        //$data['log_id'] = 6;

        DownloadLogAct::dispatch($data);

        return $this->resSuccess('导出中请等待!');
    }
}
