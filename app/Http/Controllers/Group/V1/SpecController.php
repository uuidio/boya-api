<?php

/**
 * @Author: swl
 * @Date:   2020-03-09 
 */
namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Http\Request;
use ShopEM\Http\Requests\Platform\GoodsSpecRequest;
use ShopEM\Models\GoodsSpec;
use ShopEM\Models\GoodsTypeSpec;
use ShopEM\Repositories\GoodsSpecRepository;

class SpecController extends BaseController
{
	/**
     * 规格列表
     *
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request,GoodsSpecRepository $GoodsSpecRepository)
    {
        $data = $request->all();
        $lists = $GoodsSpecRepository->listSpec($data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $GoodsSpecRepository->listShowFields(),
        ]);
    }
    /**
     * 规格详情
     *
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = GoodsSpec::find($id);

        if(empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 添加规格
     * @Author hfh_wind
     * @param BrandRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(GoodsSpecRequest $request)
    {
        $data = $request->only('sp_name', 'sp_sort', 'class_id', 'class_name');
        if(!isset($data['sp_sort'])  || empty($data['sp_sort'])) {
            $data['sp_sort'] = 0;
        }
        $data['gm_id'] = 0;
        GoodsSpec::create($data);

        return $this->resSuccess();
    }

    /**
     * 跟新规格
     * @Author hfh_wind
     * @param GoodsSpecRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(GoodsSpecRequest $request)
    {
        $data = $request->only('id','sp_name', 'sp_sort', 'class_id', 'class_name');

        if(!isset($data['id'])  || empty($data['id'])) {
            return $this->resFailed(414,"请输入规格id");
        }
        $id=$data['id'];
        try {
            $goodsSpec = GoodsSpec::find($id);
            if(empty($goodsSpec)) {
                return $this->resFailed(701,"找不到数据!");
            }
            $goodsSpec->update($data);
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
        if($id <= 0) {
            return $this->resFailed(414);
        }
        $goodsSpec = GoodsSpec::find($id);
        if(empty($goodsSpec)) {
            return $this->resFailed(701,"找不到数据!");
        }

        try {
            GoodsSpec::destroy($id);
            //对应商家添加出来商品的值也一并删掉(废弃)
            //GoodsSpecValue::destroy($id);
            GoodsTypeSpec::where('type_id', $id)->delete();
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }
}
