<?php
/**
 * @Filename        ShopCatsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Seller\V1;


use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Models\ShopCats;
use ShopEM\Http\Requests\Seller\ShopCatsRequest;
use ShopEM\Repositories\ShopCatsRepository;
use ShopEM\Traits\ListsTree;
use Illuminate\Support\Facades\Cache;

class ShopCatsController extends BaseController
{
    //引用分类树
    use ListsTree;


    /**
     * 店铺分类列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param ShopCatsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(ShopCatsRepository $repository)
    {
        //获取商家店铺id
        $shopId = $this->shop->id;
        if (empty($shopId)) {
            return $this->resFailed(701);
        }

        //获取店铺分类列表
        $lists = $repository->listItems($shopId);

        $lists = $this->toFormatTree($lists->toArray(), 'cat_name');
        if(empty($lists)){
            $lists=[];
        }
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);

    }

    /**
     * 店铺分类树
     *
     * @Author moocde <mo@mocode.cn>
     * @param shopCatsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function allClassTree(shopCatsRepository $repository)
    {
        $shopCats = $repository->listItems($this->shop->id);
        if (empty($shopCats)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($this->shopCatsToTree($shopCats->toArray()));
    }

    /**
     * 创建店铺分类
     *
     * @Author moocde <mo@mocode.cn>
     * @param ShopCatsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeCat(ShopCatsRequest $request)
    {
        $data = $request->only('cat_name','class_icon','parent_id','disabled','order_sort');
        $data['shop_id'] = $this->shop->id;

        if ($data['parent_id'] == 0) {
            $data['level'] = 1;
        }else{
            $data['level'] = 2;
        }
        //同级不能同名
        $check=ShopCats::where(['cat_name'=>$data['cat_name'],'shop_id'=>$data['shop_id'],'level'=>$data['level']])->count();
        if($check){
            return $this->resFailed(701,'同级分类名称不能重复！');
        }

        ShopCats::create($data);

        // 清空分类缓存
        Cache::forget('all_shop_cats_tree');

        return $this->resSuccess('保存成功');
    }

    /**
     * 更新店铺分类
     *
     * @Author moocde <mo@mocode.cn>
     * @param ShopCatsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCat(ShopCatsRequest $request)
    {
        //获取商家店铺id
        $shopId = $this->shop->id;

        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $data = $request->only('cat_name','class_icon','parent_id',' disabled','order_sort');

        try {
            if ($data['parent_id'] == 0) {
                $data['level'] = 1;
            } else {
                $data['level'] = 2;
            }

            //同级不能同名
            $check=ShopCats::where(['cat_name'=>$data['cat_name'],'shop_id'=>$shopId,'level'=>$data['level']])->where('id','<>',$id)->count();
            if($check){
                return $this->resFailed(701,'同级分类名称不能重复！');
            }

            $shopcats = ShopCats::where('id', $id)->where('shop_id', $this->shop->id)->first();

//            $shopcats = ShopCats::find($id);

            if (empty($shopcats)) {
                return $this->resFailed(701);
            }

            $check=ShopCats::where('parent_id',$id)->count();

            //如果该分类下有子分类不允许移动
            if($shopcats['level'] ==1 && $data['level'] ==2  && $check){
                return $this->resFailed(701, '该一级分类下有子分类，无法移动！');
            }

            if ($data['parent_id'] == $shopcats->id) {
                return $this->resFailed(701, '不能选择自己的作为父类');
            }
            $shopcats->update($data);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        // 清空分类缓存
        Cache::forget('all_shop_cats_tree');

        return $this->resSuccess('更新成功');
    }



    /**
     * 删除店铺分类
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCat($id = 0)
    {

        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $shopcats = ShopCats::where('id', $id)->where('shop_id', $this->shop->id)->first();

        if (empty($shopcats)) {
            return $this->resFailed(701);
        }

        $hasChild = ShopCats::where('parent_id', $id)->count();

        if ($hasChild > 0) {
            return $this->resFailed(701, '该分类下存在子类，无法直接删除');
        }

        try {
            ShopCats::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess('删除成功');
    }


    /**
     * 店铺分类详情
     * @Author hfh
     * @param Request $request
     * @return mixed
     */
    public function ShopCatsDetail(Request $request)
    {
        $id = $request['id']??intval($request['id']);

        if($id <=0){
            return $this->resFailed(414,'参数错误！');
        }

        $detail['ShopCats'] = ShopCats::where('id',$id)->first();

        return $this->resSuccess($detail);
    }



}