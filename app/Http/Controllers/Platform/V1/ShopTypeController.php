<?php
/**
 * @Filename ShopTypeController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\ShopTypeRequest;
use ShopEM\Models\ShopType;
use ShopEM\Repositories\ShopTypeRepository;

class ShopTypeController extends BaseController
{


    /**
     *  店铺类型列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @param ShopTypeRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, ShopTypeRepository $repository)
    {
        $data = $request->all();
        $data['per_page'] = config('app.per_page');
        $lists = $repository->search($data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     *  店铺类型详情
     *
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = ShopType::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 创建店铺类型
     * @Author hfh_wind
     * @param ShopRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(ShopTypeRequest $request)
    {

        $data = $request->only('name','shop_type', 'brief', 'suffix', 'max_item', 'status');
        try {

            ShopType::create($data);

        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }


    /**
     * 更新店铺类型
     * @Author hfh_wind
     * @param ShopRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ShopTypeRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $data = $request->only('name','shop_type', 'brief', 'suffix', 'max_item', 'status');
        try {
            $shop = ShopType::find($id);
            if (empty($shop)) {
                return $this->resFailed(701);
            }
            $shop->update($data);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 删除
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        try {
            ShopType::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }


}