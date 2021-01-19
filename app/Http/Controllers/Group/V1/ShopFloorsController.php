<?php


/**
 * @Author: swl
 * @Date:   2020-03-10 
 */
namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Repositories\ShopFloorsRepository;
use ShopEM\Http\Requests\Platform\ShopFloorsRequest;
use ShopEM\Models\ShopFloor;

class ShopFloorsController extends BaseController
{
	 /**
     *  楼层列表
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, ShopFloorsRepository $shopFloor)
    {
        $input_data = $request->all();
        $input_data['per_page'] = $input_data['per_page']  ?? config('app.per_page');
        $floor = $shopFloor->search($input_data);

        if (empty($floor)) {
            $floor = [];
        }

        return $this->resSuccess([
            'lists' => $floor,
            'field' => $shopFloor->listShowFields()
        ]);
    }

}
