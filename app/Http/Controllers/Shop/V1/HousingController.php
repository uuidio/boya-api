<?php
/**
 * @Filename        HousingController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Repositories\HousingRepository;

class HousingController extends BaseController
{
    /**
     * 小区列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param HousingRepository $repository
     * @param Request           $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(HousingRepository $repository, Request $request)
    {
        testLog($request->all());
        return $this->resSuccess($repository->seachAll($request));
    }
}