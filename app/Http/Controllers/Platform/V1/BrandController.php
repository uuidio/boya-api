<?php
/**
 * @Filename BrandController.php
 * 品牌
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\BrandRequest;
use ShopEM\Models\Brand;
use ShopEM\Repositories\BrandRepository;

class BrandController extends BaseController
{



    /**
     * 品牌列表
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(BrandRepository $brandRepository)
    {
        $lists = $brandRepository->listItems($this->GMID);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $brandRepository->listShowFields(),
        ]);
    }

    /**
     * 品牌详情
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

        $detail = Brand::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 添加品牌
     *
     * @Author moocde <mo@mocode.cn>
     * @param BrandRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(BrandRequest $request)
    {

        $data = $request->only('brand_name', 'class_id', 'brand_logo', 'description', 'is_recommend', 'show_type',
            'listorder');
        $data['brand_initial'] = getFirstChar($data['brand_name']);
        $msg_text="创建品牌".$data['brand_name'];
        try {
            $data['gm_id'] = $this->GMID;
            Brand::create($data);

        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        // 清空缓存
        Cache::forget('all_brands_gmid_'.$data['gm_id']);
        return $this->resSuccess();
    }

    /**
     * 更新品牌
     *
     * @Author moocde <mo@mocode.cn>
     * @param BrandRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(BrandRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $data = $request->only('brand_name', 'class_id', 'brand_logo', 'description', 'is_recommend', 'show_type',
            'listorder');
        $data['brand_initial'] = getFirstChar($data['brand_name']);
        $brand = Brand::find($id);
        if (empty($brand) ||  $brand->gm_id != $this->GMID) {
            return $this->resFailed(701);
        }

        $msg_text="修改品牌-".$brand['id']."-".$brand['brand_name'];
        try {

            $brand->update($data);
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        // 清空缓存
        Cache::forget('all_brands_gmid_'.$this->GMID);
        return $this->resSuccess();
    }

    /**
     * 删除品牌
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
        $brand = Brand::find($id);
        if (empty($brand) || $brand->gm_id != $this->GMID) {
            return $this->resFailed(701);
        }
        $msg_text="删除品牌-".$brand['id']."-".$brand['brand_name'];
        try {
            Brand::destroy($id);
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        // 清空缓存
        Cache::forget('all_brands_gmid_'.$this->GMID);
        return $this->resSuccess();
    }
}