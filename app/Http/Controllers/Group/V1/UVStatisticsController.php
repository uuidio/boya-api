<?php

/**
 * @Author: Huiho
 * @Date:   2020-03-10 
 */
namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Http\Request;
use ShopEM\Repositories\UVStatisticsRepository;
use ShopEM\Models\UVTrade;

class UVStatisticsController extends BaseController
{
	 /**
     *
     * 列表
     * @Author Huiho <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, UVStatisticsRepository $repository)
    {
        $input_data = $request->all();

        $input_data['per_page'] = $input_data['per_page'] ?? config('app.per_page');
        $input_data['total_data_status'] = true;

        $lists = $repository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        $total_fee_data = $lists['total_fee_data'];
        unset($lists['total_fee_data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
            'total_fee_data' => $total_fee_data,
        ]);
    }



    /**
     * [filterExport 筛选导出会员]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function filterExport(Request $request , UVStatisticsRepository $repository)
    {
        $input_data = $request->all();
        if (isset($input_data['s']))
        {
            unset($input_data['s']);
        }
        $lists = $repository->search($input_data ,1);

        $return['tHeader'] = ['项目名称','店铺名称','交易人数'];
        $return['filterVal'] = ['gm_name','shop_name','trading_volume'];
        $return['list'] = $lists;

        return $this->resSuccess($return);
    }
}
