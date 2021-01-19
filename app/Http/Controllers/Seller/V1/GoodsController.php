<?php
/**
 * @Filename        GoodsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Http\Requests\Seller\GoodsRequest;
use ShopEM\Jobs\DownloadLogAct;
use ShopEM\Models\Brand;
use ShopEM\Models\DownloadLog;
use ShopEM\Models\Goods;
use ShopEM\Models\GoodsAttribute;
use ShopEM\Models\GoodsAttributeValue;
use ShopEM\Models\GoodsClass;
use ShopEM\Models\GoodsSku;
use ShopEM\Models\AlbumPic;
use ShopEM\Models\GoodsImage;
use ShopEM\Models\GoodsSpec;
use ShopEM\Models\GoodsSpecValue;
use ShopEM\Models\GoodsType;
use ShopEM\Models\GoodsTypeBrand;
use ShopEM\Models\GoodsTypeSpec;
use ShopEM\Repositories\BrandRepository;
use ShopEM\Repositories\GoodsClassRepository;
use ShopEM\Repositories\GoodsRepository;
use ShopEM\Traits\ListsTree;
use ShopEM\Services\GoodsService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class GoodsController extends BaseController
{
    use ListsTree;

    /**
     * 商品列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param GoodsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, GoodsRepository $repository)
    {
        $data = $request->all();
        if (!$request->filled('shop_id')) {
            $data['shop_id'] = $this->shop->id;
        }
        $data['use_state'] = 20;
        $lists = $repository->listItems($data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 商品详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @param GoodsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0, GoodsRepository $repository)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = $repository->detail($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }
        
        if ($detail->shop_id != $this->shop->id) {
            return $this->resFailed(700);
        }
        $detail->gc_id_1;
        $cate_nav=GoodsClass::whereIn('id',[$detail->gc_id_1,$detail->gc_id_2,$detail->gc_id_3])->get()->toArray();
        //返回类型id
        if(isset($cate_nav[2]['type_id']) && !empty($cate_nav[2]['type_id'])){
            $detail['type_id']= $cate_nav[2]['type_id'] ;
        }

        $cate_nav=array_column($cate_nav,'gc_name');
        $detail['cate_nav']=implode('>',$cate_nav);

        $detail['goods_spec_list']= GoodsSpec::where('gm_id',$this->GMID)->orderBy('sp_sort','desc')->get();
//        print_r((array) $detail);
//dd($detail);
        return $this->resSuccess($detail);
    }


    /**
     * 发布商品
     *
     * @Author moocde <mo@mocode.cn>
     * @param GoodsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(GoodsRequest $request,GoodsService $service)
    {

        $data = $request->only('goods_name', 'goods_info', 'shop_id', 'gc_id_1', 'gc_id_2', 'gc_id_3', 'brand_id', 'supply_link', 'image_list', 'trade_type', 'is_need_qq',
            'goods_price', 'goods_marketprice', 'goods_cost', 'promotion_price', 'promotion_type', 'goods_serial',
            'goods_stock', 'goods_stock_alarm', 'goods_barcode', 'spec', 'spec_name', 'goods_spec', 'goods_image',
            'transport_id', 'goods_freight', 'goods_shop_cid', 'is_virtual', 'virtual_indate', 'is_own_shop',
            'invite_rate', 'goods_body', 'goods_attr',
            'rewards','profit_sharing','img_material','promo_article','vir_aftersale_at','vir_aftersale_state','is_rebate', 'goods_shop_c_lv1','goods_shop_c_lv2');
        foreach ($data as $key => $value) {
            if ($value === null) {
                unset($data[$key]);
            }
        }

        $data['goods_shop_c_lv1'] = $data['goods_shop_c_lv1']??0;
        $data['goods_shop_c_lv2'] = $data['goods_shop_c_lv2']??0;

        /*if (isset($data['transport_id']) && $data['transport_id'] === null) {
            unset($data['transport_id']);
        }*/

        if ($request->filled('pick_type')) {
            $data['pick_type'] = implode(',', $request->pick_type);
        }

        $data['gc_id'] = $data['gc_id_3'];
        $data['shop_id'] = $this->shop->id;
        $data['is_own_shop'] = $this->shop->is_own_shop;
        $res = $service->storage($data);
        if ($res['code'] > 0) {
            return $this->resFailed($res['code'], $res['msg']);
        }

        return $this->resSuccess();
    }

    /**
     * [update 更新商品]
     * @Author mssjxzw
     * @param  GoodsRequest $request [description]
     * @param  GoodsService $service [description]
     * @return [type]                [description]
     */
    public function update(GoodsRequest $request,GoodsService $service)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414, "请填写id");
        }

        $data = $request->only('goods_name', 'goods_info', 'shop_id', 'gc_id_1', 'gc_id_2', 'gc_id_3', 'brand_id', 'supply_link', 'image_list', 'trade_type', 'is_need_qq',
            'goods_price', 'goods_marketprice', 'goods_cost', 'promotion_price', 'promotion_type', 'goods_serial',
            'goods_stock', 'goods_stock_alarm', 'goods_barcode', 'spec', 'spec_name', 'goods_spec', 'goods_image',
            'transport_id', 'goods_freight', 'goods_shop_cid', 'is_virtual', 'virtual_indate', 'is_own_shop',
            'invite_rate','goods_body', 'goods_attr', 'third_attr_update',
            'rewards','profit_sharing','img_material','promo_article','vir_aftersale_at','vir_aftersale_state','is_rebate','goods_shop_c_lv1','goods_shop_c_lv2');
        foreach ($data as $key => $value) {
            if ($value === null) {
                unset($data[$key]);
            }
        }
        /*if (isset($data['transport_id']) && $data['transport_id'] === null) {
            unset($data['transport_id']);
        }*/

        $data['goods_shop_c_lv1'] = $data['goods_shop_c_lv1']??0;
        $data['goods_shop_c_lv2'] = $data['goods_shop_c_lv2']??0;

        if ($request->filled('pick_type')) {
            $data['pick_type'] = implode(',', $request->pick_type);
        }

        $data['gc_id'] = $data['gc_id_3'];
        $data['shop_id'] = $this->shop->id;

        $res = $service->storage($data,$id);
        if ($res['code'] > 0) {
            return $this->resFailed($res['code'], $res['msg']);
        }

        return $this->resSuccess();
    }


    public function getGoodsSpec()
    {
        // $this->GMID
        $gm_id = $this->GMID;;
        $detail['goods_spec']= GoodsSpec::where('gm_id',$gm_id)->orderBy('sp_sort','desc')->get();

        return $this->resSuccess($detail);
    }



    /**(废弃,不用了)
     * 根据类型获得规格、类型、属性信息
     * @Author hfh_wind
     * @param int $type_id 类型id
     * @return array 二位数组
     */
    public function getAttr($id)
    {
        $type_id = intval($id);
        if ($id <= 0 ) {
            return $this->resFailed(414, "请填写类型id");
        }
        //规格(废弃)
//        $goodsTypeSpec_model = new GoodsTypeSpec();
//        $goodsTypeSpecInfo = $goodsTypeSpec_model->where(['goods_type_specs.type_id' => $type_id])->select('goods_specs.*')
//            ->leftJoin('goods_specs', 'goods_type_specs.sp_id', '=', 'goods_specs.id')->get();
//        $returnData['type_specs'] = $goodsTypeSpecInfo;
        //属性
//        $goodsAttributeValue_model = new GoodsAttributeValue();
//        $goodsAttributeValueInfo = $goodsAttributeValue_model->where(['goods_attribute_values.type_id' => $type_id])->select('goods_attribute_values.*')
//            ->leftJoin('goods_attributes', 'goods_attributes.id', '=', 'goods_attribute_values.attr_id')->get();
//        $returnData['attribute_values'] = $goodsAttributeValueInfo;

        $returnData['attribute']=GoodsAttribute::where('type_id','=',$type_id)->where('attr_show','=','1')->select('id','attr_name')->get();


        //品牌,如果是关联的分类的品牌就以分类为准,如果没有就调取全部分类
        $goodsTypeBrand_model = new GoodsTypeBrand();
        $goodsTypeBrandInfo = $goodsTypeBrand_model->where(['goods_type_brands.type_id' => $type_id])->select('brands.*')
            ->leftJoin('brands', 'brands.id', '=', 'goods_type_brands.brand_id')->get();


        if (count($goodsTypeBrandInfo) <= 0) {
            $returnData['type_brands'] = Brand::orderBy('listorder', 'desc')->get();
        } else {
            $returnData['type_brands'] = $goodsTypeBrandInfo;
        }

        return $returnData;
    }


    /**
     * 更新商品SUK数据
     * @Author hfh_wind
     * @param $update
     * @param $goodsid_array
     * @param bool $updateXS
     * @return bool
     */
    public function editGoodsById($update, $goodsid_array, $updateXS = false)
    {
        if (empty($goodsid_array)) {
            return true;
        }

        $result = GoodsSku::where(['id' => $update['id']])->update($update);

        return $result;
    }


    /**
     * 删除商品(软删除)
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0, Request $request)
    {
        if ($id <= 0) {
            if ($request->filled('id')) {
                $id = $request->id;
            }else{
                return $this->resFailed(414);
            }
        }
        if (!is_array($id) && !is_numeric($id)) {
            return $this->resFailed(414);
        }

        if (is_numeric($id)) {
            $id = [$id];
        }

        try {
            $goods = Goods::whereIn('id',$id)->where('shop_id',$this->shop->id)->get();
            if (count($goods) == 0) {
                return $this->resSuccess();
            }
            $act_service = new \ShopEM\Services\Marketing\Activity();
            $coupon_service = new \ShopEM\Services\Marketing\Coupon();
            foreach ($goods as $key => $value) {
                $check = $act_service->checkGoods($value->id);
                if ($check['code']) {
                    $msg = $value->goods_name.':'.$check['msg'];
                    return $this->resFailed(701, $msg);
                }
                $check = $coupon_service->checkGoods($value->id);
                if ($check['code']) {
                    $msg = $value->goods_name.':'.$check['msg'];
                    return $this->resFailed(701, $msg);
                }
            }
            Goods::whereIn('id', $id)->update(['goods_state'=>20]);
            // Goods::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * [recycleList 商品回收站列表]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @param  GoodsRepository $repository [description]
     * @return [type]                      [description]
     */
    public function recycleList(Request $request, GoodsRepository $repository)
    {
        $data = $request->all();
        $data['shop_id'] = $this->shop->id;
        $data['goods_state'] = 20;
        $lists = $repository->listItems($data);
        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * [restore 恢复回收站商品]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function restore(Request $request)
    {
        if ($request->filled('ids')) {
            $data = $request->only('ids');
            try {
                if (!is_array($data['ids'])) {
                    return $this->resFailed(414, '参数错误');
                }
                $data['ids'] = explode(',', implode(',', $data['ids']));
                Goods::whereIn('id', $data['ids'])->where('shop_id',$this->shop->id)->update(['goods_state' => 0]);
                return $this->resSuccess();
            } catch (Exception $e) {
                return $this->resFailed(701, $e->getMessage());
            }
        } else {
            return $this->resFailed(414, '缺少参数');
        }
    }

    /**
     * [recycleDel 永久删除回收站商品]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function recycleDel(Request $request)
    {
        if ($request->filled('ids')) {
            $data = $request->only('ids');
            try {
                if (!is_array($data['ids'])) {
                    return $this->resFailed(414, '参数错误');
                }
                $data['ids'] = explode(',', implode(',', $data['ids']));
                Goods::whereIn('id', $data['ids'])->where('shop_id',$this->shop->id)->delete();
                return $this->resSuccess();
            } catch (Exception $e) {
                return $this->resFailed(701, $e->getMessage());
            }
        } else {
            return $this->resFailed(414, '缺少参数');
        }
    }

    /**
     * 商品分类树
     *
     * @Author moocde <mo@mocode.cn>
     * @param GoodsClassRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function allClassTree(GoodsClassRepository $repository)
    {
        $data = [];
         $data['gm_id'] = $this->GMID;
        $goodsClass = $repository->listItems($data);
        if (empty($goodsClass)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess($this->goodsClassToTree($goodsClass->toArray()));
    }

    /**
     * 全部品牌
     *
     * @Author moocde <mo@mocode.cn>
     * @param BrandRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function allBrands(BrandRepository $repository)
    {
        $allBrands = $repository->allItems($this->GMID);
        if (empty($allBrands)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess($allBrands);
    }

    /**
     * 上下架商品
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateState(Request $request)
    {
        DB::beginTransaction();
        try {
            //删除首页挂件缓存
            $page = 'index_fit';
            $cache_key = 'CONFIGITEMS_INDEX_PAGE_'.$page.'_GM_'.$this->GMID;
            Cache::forget($cache_key);

            $data = [
                'goods_state' => $request->state
            ];
            if ($request->state == 1) {
                $data['on_sale_time'] = date('Y-m-d H:i:s');
            }
            Goods::where('shop_id', $this->shop->id)
                ->where('goods_state', '<>', 10)
                ->whereIn('id', $request->goods_ids)
                ->where('shop_id',$this->shop->id)
                ->update($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 新增规格值
     * @Author hfh_wind
     * @param GoodsSpecValueRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodsSpecValueStore(GoodsSpecValueRequest $request)
    {
        $data = $request->only('sp_value_name', 'sp_id', 'shop_id', 'cat_id', 'sp_value_data', 'sp_value_sort');
        try {
            GoodsSpecValue::create($data);
        } catch (\Exception $e) {

            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess([],'添加成功!');
    }

    /**
     * 编辑规格值
     * @Author hfh_wind
     * @param GoodsSpecValueRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodsSpecValueEdit(GoodsSpecValueRequest $request)
    {
        $data = $request->only('id', 'sp_value_name', 'sp_id', 'shop_id', 'cat_id', 'sp_value_data', 'sp_value_sort');
        $id=isset($data['id'])?$data['id']:0;
        if ($id <= 0) {
            return $this->resFailed(414,'缺少规格id');
        }

        try {
            GoodsSpecValue::where(['id'=>$id])->update($data);
        } catch (\Exception $e) {

            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess([],'添加成功!');
    }

    /**
     * 获取规格值数据
     * @Author hfh_wind
     * @param $id 所属规格id
     * @return array
     */
    public function getGoodsSpecValue(Request $request)
    {
        $data= $request->only('cat_id','sp_id');
        //该店铺此类型下某个规格的的规格值
        $shop_id = $this->shop->id;
        $GoodsSpecValue=GoodsSpecValue::where(['cat_id'=>$data['cat_id'],'sp_id'=>$data['sp_id'],'shop_id'=>$shop_id])->get();
        $returnDate['GoodsSpecValue']=$GoodsSpecValue;

        return $this->resSuccess($returnDate);
    }

    /**
     * [getSourceList 获取来源列表]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    public function getSourceList()
    {
        $list = \ShopEM\Models\SourceConfig::select('source','name','shop_id','type')->get();
        return $this->resSuccess($list);
    }


     /**
     * 显示/隐藏市场价
     *
     * @Author swl
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function showPrice(Request $request)
    {
        DB::beginTransaction();
        try {

            $data = [
                // 1:显示 0：隐藏
                'show_promotion_price' => $request->status
            ];
            Goods::where('shop_id', $this->shop->id)
                ->whereIn('id', $request->goods_ids)
                ->update($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

     /**
     * 获取快捷修改内容(sku)
     *
     * @Author swl
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
     public function getGoodSku($id = 0, GoodsRepository $repository){
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        // 只获取sku这个追加属性
        $detail = Goods::select('id','goods_spec','spec_name','shop_id')->find($id)->setAppends(['sku']);

        if (empty($detail)) {
            return $this->resFailed(700);
        }
        if ($detail->shop_id != $this->shop->id) {
            return $this->resFailed(700);
        }
        $detail = $detail->toArray();
        // 如果为空，说明是单规格商品,也要返回sku属性
        if(empty($detail['sku'])){
            $goodssku = GoodsSku::where(['goods_id' =>$id])->get();
            $param =[];
            foreach ($goodssku as $key => $value) {
                $param[$key]['id'] = $value['id'];//sku_id
                $param[$key]['price'] = $value['goods_price'];//商品售价
                $param[$key]['goods_cost'] = $value['goods_cost'];//成本价
                $param[$key]['marketprice'] = $value['goods_marketprice']; //市场价
                $param[$key]['sku'] = $value['goods_serial'];//货号
                $param[$key]['barcode'] = $value['goods_barcode'];//条形码
                $param[$key]['alarm'] = $value['goods_stock_alarm'];//预警库存
                $param[$key]['sp_value'] = $value['goods_spec']; //规格值
                $param[$key]['stock'] = $value['goods_stock'];
            }
            $detail['sku'] = $param;
           
        }

        return $this->resSuccess($detail);
     }

     /**
     * 保存快捷编辑
     *
     * @Author swl
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
     public function quickUpdate(Request $request,GoodsService $service){
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414, "请填写id");
        }
        $data = $request->only('spec','shop_id','type');
       
        // $data['spec_name'] = is_array($data['spec']) ? serialize($data['spec_name']) : serialize(null);
        // $data['goods_spec'] = is_array($data['spec']) ? serialize($data['goods_spec']) : serialize(null);
        $spec = $data['spec'];
        unset($data['spec']);
        // type:1单规格 2多规格
        $data['type'] = $data['type']??2;
        DB::beginTransaction();
        try{
            $res = $service->fastSave($data,$id,$spec);
            DB::commit();
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }
        
        return $this->resSuccess();
    }

    /**
     * 商品导出
     *
     * @Author Huiho
     * @param GoodsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function goodDown(Request $request,GoodsRepository $repository)
    {
        /*
   *
   * 之前的导出
   * */
//        $data = $request->all();
//        if (!$request->filled('shop_id')) {
//            $data['shop_id'] = $this->shop->id;
//        }
//        $data['use_state'] = 20;
//
//        $lists = $repository->listItems($data , false , 1);
//        $title = $repository->downLstFields();
//
//        $return['goods']['tHeader']= array_column($title,'title'); //表头
//        $return['goods']['filterVal']= array_column($title,'dataIndex'); //表头字段
//        $return['goods']['list']= $lists; //表头
//
//        return  $this->resSuccess($return);

        /*
      *
      * 之前的导出
      * */


        $data = $request->all();
        if (!$request->filled('shop_id')) {
            $data['shop_id'] = $this->shop->id;
        }

        $data['use_state'] = 20;


        if (isset($data['s'])) {
            unset($data['s']);
        }

        $insert['type'] = 'Goods';
        $insert['desc'] = json_encode($data);
        $insert['shop_id'] =$data['shop_id'] ;

        $res = DownloadLog::create($insert);

        $return['log_id'] = $res['id'];
        //$data['log_id'] = 6;

        DownloadLogAct::dispatch($return);

        return $this->resSuccess('导出中请等待!');
    }


}

