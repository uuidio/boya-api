<?php
/**
 * @Filename DownloadServiceController.php
 * 品牌
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Platform\V1;


use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\DownloadLog;
use ShopEM\Repositories\DownLoadListRepository;
use Symfony\Component\HttpFoundation\Request;

class DownloadServiceController extends BaseController
{


    /**
     * 导出下载列表
     * @Author hfh_wind
     * @param Request $request
     * @param DownLoadListRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function DownLoadList(Request $request, DownLoadListRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = 15;
        $input_data['shop_id'] = 0;
        $input_data['gm_id'] = $this->GMID;

        $lists = $repository->search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 删除导出记录
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function Delete(Request $request)
    {
        $id = $request['id']??0;

        if ($id <= 0)
        {
            return $this->resFailed(414, '参数错误!');
        }

        $info = DownloadLog::find($id)->where('gm_id',$this->GMID);

        if (empty($info) || $info['shop_id'] != 0)
        {
            return $this->resFailed(700, '数据找不到!');
        }

        $msg_text = "删除导出日志-" . $info['id'] . "-" . $info['desc'];

        try
        {
            $info::destroy($id);
        } catch (\Exception $e)
        {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess();
    }
}