<?php
/**
 * @Filename RegionsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Repositories\RegionsClassRepository;
use ShopEM\Traits\RegionsTree;
use ShopEM\Http\Controllers\Seller\BaseController;

class RegionsController extends BaseController
{

    use RegionsTree;

    protected $regionsClassRepository;

    public function __construct(RegionsClassRepository $regionsClassRepository)
    {
        $this->regionsClassRepository = $regionsClassRepository;
    }

    /**
     * 地区分类树
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */

    public function allClassTree()
    {
        $goodsClass = $this->regionsClassRepository->listItems();
        if (empty($goodsClass)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess($this->RegionsClassToTree($goodsClass->toArray()));
    }


}