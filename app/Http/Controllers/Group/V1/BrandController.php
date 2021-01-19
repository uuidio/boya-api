<?php

/**
 * @Author: swl
 * @Date:   2020-03-09 
 */
namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Http\Request;
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
    public function lists(Request $request , BrandRepository $brandRepository)
    {
        $input_data = $request->all();

        $input_data['per_page'] = $input_data['per_page'] ?? config('app.per_page');

        $lists = $brandRepository->allListItems($input_data);

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
        try {
            $data['brand_initial'] = getFirstChar($data['brand_name']);
        } catch (\Exception $e) {
            return $this->resFailed(701, '品牌名称含有特殊字符');
        }
        
        $msg_text="创建品牌".$data['brand_name'];
        try {
            $exists = Brand::where('brand_name',$data['brand_name'])->exists();
            if ($exists) {
                return $this->resFailed(701, '品牌名已存在');
            }
            $data['gm_id'] = 0;
            Brand::create($data);

        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        // 清空缓存
        Cache::forget('all_brands_group');
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
        if (empty($brand)) {
            return $this->resFailed(701);
        }
        $exists = Brand::where('brand_name',$data['brand_name'])->where('id','!=',$id)->exists();
        if ($exists) {
            return $this->resFailed(701, '品牌名已存在');
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
        Cache::forget('all_brands_group');
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
        if (empty($brand)) {
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
        Cache::forget('all_brands_group');
        return $this->resSuccess();
    }


    /**
     * 品牌名称列表
     *
     * @Author Huiho
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBrandlists(BrandRepository $brandRepository)
    {
        $lists = $brandRepository->allItems();

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' =>
                [
                    ['dataIndex' => 'id', 'title' => '品牌ID'],
                    ['dataIndex' => 'brand_name', 'title' => '品牌名称'],
                    //['dataIndex' => 'brand_initial', 'title' => '品牌首字母'],
                ]
        ]);
    }

    
}
