<?php
/**
 * @Filename BrandRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\Cache;
use ShopEM\Models\Brand;

class BrandRepository
{

    /*
     * 定义搜索过滤字段
    */
    protected $filterables = [
        'id' => ['field' => 'id', 'operator' => '='],
        'brand_name' => ['field' => 'brand_name', 'operator' => 'like'],
        'brand_initial' => ['field' => 'brand_initial', 'operator' => '='],

    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields()
    {
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'brand_name', 'dataIndex' => 'brand_name', 'title' => '品牌名称'],
            ['key' => 'class_id', 'dataIndex' => 'class_id', 'title' => '分类ID'],
            ['key' => 'brand_initial', 'dataIndex' => 'brand_initial', 'title' => '品牌首字母'],
            ['key' => 'brand_logo', 'dataIndex' => 'brand_logo', 'title' => '品牌LOGO'],
            ['key' => 'description', 'dataIndex' => 'description', 'title' => '品牌描述'],
            ['key' => 'is_recommend', 'dataIndex' => 'is_recommend', 'title' => '是否推荐'],
            ['key' => 'listorder', 'dataIndex' => 'listorder', 'title' => '排序'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 获取列表数据
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function listItems($gm_id=1)
    {
        return Brand::where('gm_id',$gm_id)->select(listFieldToSelect($this->listShowFields()))->paginate(config('app.per_page'));
    }

     /**
     * 获取全部列表数据
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
//    public function allListItems()
//    {
//        return Brand::select(listFieldToSelect($this->listShowFields()))->paginate(config('app.per_page'));
//    }
    public function allListItems($request)
    {
        $model = new Brand();
        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('brand_initial')->paginate($request['per_page']);

        return $lists;
    }

//    /**
//     * 获取所有的品牌数据
//     *
//     * @Author moocde <mo@mocode.cn>
//     * @return mixed
//     */
//    public function allItems($gm_id=1)
//    {
//        // 升级成集团统一管理  2020-4-1 18:19:04
//        $gm_id = 0;
//        $key = 'all_brands_group';
//        return Cache::remember($key, cacheExpires(), function () use ($gm_id) {
//            return Brand::where('gm_id',$gm_id)->orderBy('brand_initial')->get();
//        });
//    }
    /**
     * 获取所有的品牌数据
     *
     * @Author Huiho
     * @return mixed
     */
    public function allItems($gm_id=0)
    {
        return Brand::where('gm_id',$gm_id)->select('id','brand_name')->orderBy('brand_initial')->get();
    }


}