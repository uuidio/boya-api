<?php
/**
 * @Filename        StorePoliceController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Repositories\GoodsRepository;
use ShopEM\Repositories\StorePoliceRepository;
use ShopEM\Models\StorePolices;
use ShopEM\Http\Requests\Seller\StorePoliceRequest;
use ShopEM\Models\Goods;

class StorePoliceController extends BaseController
{
    /**
     * 获取库存报警值
     *
     * @Author moocde <mo@mocode.cn>
     * @param  StorePoliceRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */

    public function storePolice(StorePoliceRepository $repository){
        //获取店铺id
        $shopId = $this->shop->id;
        $lists = $repository->listItems($shopId);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 设置库存报警值
     *
     * @Author moocde <mo@mocode.cn>,
     * @param  StorePoliceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function saveStorePolice(StorePoliceRequest $request){
        //获取店铺id
        $shopId = $this->shop->id;
        $lists = storePolices::where('shop_id', $shopId)->get()->toArray();

        //判断店铺是否设置了库存报警值
        if(empty($lists)){
            $data = $request->only('policevalue');
            $data['shop_id'] = $shopId;
            storePolices::create($data);
            return $this->resSuccess('设置成功');
        }
        return $this->resFailed('该店铺已存在报警值');



    }

    /**
     * 编辑库存报警值
     *
     * @Author moocde <mo@mocode.cn>,
     * @param  StorePoliceRequest $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function updateStorePolice(StorePoliceRequest $request){
        //获取店铺id
        $shopId = $this->shop->id;
        $data = $request->only('policevalue');
        $data['shop_id'] = $shopId;

        //对输入的ID进行处理
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $storePolices = storePolices::find($id);

        if(!isset($storePolices)){
            return $this->resFailed(406);
        }

        $storePolices ->update($data);
        return $this->resSuccess('更新成功');


    }

    /**
     * 库存报警商品
     *
     * @Author moocde <mo@mocode.cn>
     * @param  GoodsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */

    public function goodsPolice(GoodsRepository $repository){

        //获取店铺id
        $shopId = $this->shop->id;

        //获取设定库存值
        $data = storePolices::where('shop_id', $shopId)->get()->toArray();

        //判断店铺是否已设置了库存值
        if(isset($data['0'])){
            $Police = $data['0']['policevalue'];
            $goods = Goods::where('shop_id',$shopId)
                                ->where('goods_stock','>=',$Police)
                                    ->get();

            return $this->resSuccess([
            'lists' => $goods,
            'field' => $repository->listShowFields(),
         ]);
        }

        //店铺未设置库存值,默认搜索库存在0～10之间(包含)库存的商品
        $goods = Goods::where('shop_id',$shopId)
                            ->where('goods_stock','>=',0)
                                ->where('goods_stock','<=',10)
                                    ->get();
        return $this->resSuccess([
            'lists' => $goods,
            'field' => $repository->listShowFields(),
        ]);
    }




}