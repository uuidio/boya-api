<?php
/**
 * @Filename   AdminLogsController.php
 *
 * @Author swl 2020-3-12      
 */

namespace ShopEM\Http\Controllers\Group\V1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Models\GroupManageLog;
use ShopEM\Repositories\GroupManageLogsRepository;

class AdminLogsController extends BaseController
{
	/**
	* 日志列表
	* @Author swl
	* @param GroupManageLogsRepository $repository
	* @param Request $request
	* @return \Illuminate\Http\JsonResponse
	*/
	public function lists(GroupManageLogsRepository $repository,Request $request){
		$input_data = $request->all();
	    $lists = $repository->search($input_data);

	    return $this->resSuccess([
	        'lists' => $lists,
	        'field' => $repository->listFields,
	    ]);
	}

}