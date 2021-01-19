<?php

/**
 * @Author: nlx
 * @Date:   2020-04-20 16:44:41
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-05-13 11:49:46
 */
namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

use ShopEM\Models\CrmMasterStore;
use ShopEM\Repositories\CrmStoreRepository;

class CrmDataController extends BaseController
{

	//crm 店铺列表
	public function storeList(Request $request,CrmStoreRepository $repository)
	{
		$input = $request->all();
		$input['gm_id'] = $this->GMID;
		$size = 10000;
		$cache_key = md5(json_encode($input)).'_'.$size.'_crmstorelist';
		// $oldLists = Cache::get($cache_key);
		$oldLists = [];
		if ($oldLists) {
			$lists = $oldLists;
		}else {
			$lists = $repository->listItems($input,$size);

			if (empty($lists)) {
	            return $this->resFailed(700);
	        }
	        $lists = $lists->toArray();
		}

        foreach ($lists['data'] as $key => &$value)
        {
            $value['storeName'] = $value['storeName']." ( {$value['storeCode']} )";
        }

        $cache_day = Carbon::now()->addDay(1);
        if (!empty($lists['data'])) {
        	Cache::put($cache_key,$lists,$cache_day);
        }
		return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
	}

}