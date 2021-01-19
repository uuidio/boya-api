<?php


/**
 * @Author: swl
 * @Date:   2020-03-09 
 */
namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Jobs\DownloadLogAct;
use ShopEM\Models\DownloadLog;
use ShopEM\Repositories\GoodsRepository;
use ShopEM\Models\Config;

class GoodsController extends BaseController
{
	 /**
     * 商品列表
     *
     * @Author swl
     * @param GoodsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
	public function lists(Request $request,GoodsRepository $repository)
	{

		$data = $request->all();
        
        $data['use_state'] = 20;
        $lists = $repository->listItems($data);
        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields('group'),
        ]);
	}

    /**
     * 商品导出
     *
     * @Author Huiho
     * @param GoodsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function goodDown(Request $request,GoodsRepository $repository)
    {
        $data = $request->all();
        $data['use_state'] = 20;


        if (isset($input_data['s'])) {
            unset($input_data['s']);
        }

        $insert['type'] = 'Goods';
        $insert['desc'] = json_encode($data);
        $insert['gm_id'] = 0;

        $res = DownloadLog::create($insert);

        $return['log_id'] = $res['id'];
        //$data['log_id'] = 6;

        DownloadLogAct::dispatch($return);

        return $this->resSuccess('导出中请等待!');
    }


}
