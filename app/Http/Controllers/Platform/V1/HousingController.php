<?php
/**
 * @Filename HousingController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\HousingRequest;
use ShopEM\Models\Housing;
use ShopEM\Repositories\HousingRepository;

class HousingController extends BaseController
{
    protected $housingRepository;

    public function __construct(HousingRepository $housingRepository)
    {
        parent::__construct();
        $this->housingRepository = $housingRepository;
    }

    /**
     * 小区列表
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists()
    {
        $lists = $this->housingRepository->housingItems();

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $this->housingRepository->housingListField(),
        ]);
    }

    /**
     * 小区详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = Housing::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 添加小区
     *
     * @Author moocde <mo@mocode.cn>
     * @param HousingRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(HousingRequest $request)
    {
        $data = $request->only('housing_name', 'province_id', 'city_id', 'area_id', 'street_id', 'province_name', 'city_name', 'area_name', 'street_name', 'address', 'zip_code', 'lng', 'lat');

        Housing::create($data);
        return $this->resSuccess();
    }

    /**
     * 更新小区
     *
     * @Author moocde <mo@mocode.cn>
     * @param HousingRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(HousingRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $data = $request->only('housing_name', 'province_id', 'city_id', 'area_id', 'street_id', 'province_name', 'city_name', 'area_name', 'street_name', 'address', 'zip_code', 'lng', 'lat');
        try {
            $housing = Housing::find($id);
            if (empty($housing)) {
                return $this->resFailed(701);
            }
            $housing->update($data);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }


    /**
     * 删除小区
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        try {
            Housing::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    public function formatArea($data, &$area)
    {
        $tmp = [];
        foreach ($data as $key => $value) {
            if (!empty($value['children'])) {
                echo 11;
                $this->formatArea($value['children'], $area);
            }
            $tmp[] = ['code' => $value['code'], 'name' => $value['name']];
        }
        $area = $tmp;
    }
}