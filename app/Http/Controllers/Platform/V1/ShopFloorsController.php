<?php
/**
 * @Filename FloorsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author         hfh
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Platform\BaseController;
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
        $input_data['gm_id'] = $this->GMID;

        $floor = $shopFloor->search($input_data);

        if (empty($floor)) {
            $floor = [];
        }

        return $this->resSuccess([
            'lists' => $floor,
            'field' => $shopFloor->listShowFields()
        ]);
    }

    /**
     *  楼层详情
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

        $detail = ShopFloor::find($id);

        if (empty($detail) || $detail->gm_id != $this->GMID) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 创建楼层
     * @Author hfh_wind
     * @param ArticleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ShopFloorsRequest $request)
    {
        $data = $request->only('name', 'order', 'is_show');
        $msg_text="创建楼层".$data['name'];
        try {
            $data['gm_id'] = $this->GMID;
            ShopFloor::create($data);

        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(702, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess([], "创建成功!");
    }

    /**
     *  更新楼层
     *
     * @Author hfh_wind
     * @param ShopFloorRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ShopFloorsRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $data = $request->only('name', 'order', 'is_show');
        $res = ShopFloor::find($id);

        $msg_text="更新楼层-".$id."-".$data['name'];
        try {

            $res->update($data);

        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess([], "更新成功!");
    }

    /**
     * 删除楼层
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $res = ShopFloor::find($id);
        if (empty($res) || $res->gm_id != $this->GMID) {
            return $this->resFailed(702,'数据不存在');
        }

        $msg_text="删除楼层-".$res['id']."-".$res['name'];

        try {
            ShopFloor::destroy($id);
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }

        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess([], "删除成功!");
    }

}