<?php
/**
 * @Filename        GoodsImportService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */
namespace ShopEM\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use ShopEM\Models\Cart;
use ShopEM\Models\Goods;
use ShopEM\Models\Shop;
use ShopEM\Models\UserAddress;
use ShopEM\Models\PayWalletConfig;
use ShopEM\Services\Marketing\Coupon;
use ShopEM\Services\Marketing\Activity;
use Illuminate\Support\Facades\Redis;

class GoodsImportService
{
    /**
     * Execute the job.
     *
     * @return bool
     */
    public function act_insert()
    {
        $sql = "SELECT  *  from  em_goods_import_details  where id =0";

        $data = DB::select($sql);

        if ($data) {

            $result = $this->storage($data);
        }
//        else {
//            GoodsImportModel::where('id', $this->params['id'])->update(['status' => 2]);
//        }
        return true;
    }


    /**
     * 创建商品
     * @param $data
     * @return array
     */
    public function storage($data)
    {
        // 提取导出数据
        $succ_number = 0;
        $shop_id = 10;

        $service = new GoodsService;
        foreach ($data as $k => $v) {

            try {

                $make_data['goods_name']=$v->goods_name;
                $make_data['class_name']=$v->gc_3;
                $make_data['brand_name']=$v->brand;
                $make_data['goods_marketprice'] = $v->market_price;
                $make_data['goods_cost'] = $v->cb_pirce;
                $make_data['goods_price'] = $v->price;
                $make_data['goods_stock'] = 9999;
                $make_data['goods_barcode'] = $v->tx;
                $make_data['goods_serial'] = $v->goods_serial;
                $make_data['goods_stock_alarm'] = 0;
                $make_data['rewards'] = 0;
                $make_data['gm_id'] = 2;

                $goods_data = $this->getGoodsData($make_data, $shop_id); //获取商品信息

                $this->__check($goods_data); //校验

                $goods_data['is_rebate'] = $goods_data['rewards'] ? 1 : 0;
                $goods_data['profit_sharing']=0;
                $goods_data['goods_state']=0;
                $goods_data['shop_id'] = $shop_id;

                $res = $service->storage($goods_data); //创建商品
                if ($res['code'] > 0) {
                    throw new \Exception($res['msg']);
                }

                $succ_number++;
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage().'上传数据有误');
            }
            $update_data['id'] = 1;

        }

        return true;
    }

    /**
     * 获取商品信息
     *
     * @Author djw
     * @param $data
     * @param $shop_id
     * @return mixed
     * @throws \Exception
     */
    private function getGoodsData($data, $shop_id)
    {
        if (empty($data)) {
            throw new \Exception('商品信息为空!');
        }
        //获取分类id
        $class = $this->getClass($data['class_name']);

        unset($data['class_name']);
        $data['gc_id'] = $data['gc_id_3'] = $class['gc_id_3'];
        $data['gc_id_2'] = $class['gc_id_2'];
        $data['gc_id_1'] = $class['gc_id_1'];



        //获取店铺分类id
//        $shopClass = $this->getShopClass($data['goods_shop_class'], $shop_id);
//        unset($data['goods_shop_class']);
//        $data['goods_shop_c_lv1'] = $shopClass['goods_shop_c_lv1'];
//        $data['goods_shop_c_lv2'] = $shopClass['goods_shop_c_lv2'];

        //获取品牌id
        $data['brand_id'] = $this->getBrandId($data['brand_name']);
        unset($data['brand_name']);

        //获取多规格信息
        if (isset($data['sku'])) {
            $skuinfo = $this->getSku($data['sku'], $data['spec_name']);
            $data['spec_name'] = $skuinfo['spec_name'];
            $data['goods_spec'] = $skuinfo['goods_spec'];
            $data['spec'] = $skuinfo['spec'];
            unset($data['sku']);
        }

        /*$status = [
            '上架' => 1,
            '下架' => 0
        ];
        $data['goods_state'] = $status[$data['status']] ?? 0;
        unset($data['status']);*/

        $pick_type = [
            '全部' => '0,1',
            '快递' => '0',
            '自提' => '1'
        ];
        $data['pick_type'] = '0,1';


        $data['goods_body'] = '';

        return $data;
    }

    /**
     * 获取sku
     * @param $skus
     * @param $spec_name
     * @return array
     */
    private function getSku($skus, $spec_name)
    {
        $data['spec'] = [];
        $data['spec'][0] = [
            'marketprice' => $skus['goods_marketprice'], //市场价
            'goods_cost' => $skus['goods_cost'], //成本价
            'price' => $skus['goods_price'], //售价
            'sp_value' => '', //多规格信息
            'stock' => $skus['goods_stock'], //库存
            'alarm' => $skus['goods_stock_alarm'], //预警库存
            'sku' => $skus['goods_serial'], //货号
            'barcode' => $skus['goods_barcode'], //条形码
        ];
        return $data;
    }

    /**
     * 获取分类id
     * @param $class_name
     * @return mixed
     */
    private function getClass($class_name)
    {
        $data['gc_id_1'] = $data['gc_id_2'] = $data['gc_id_3'] = 0;
        if ($class_name) {
            if (isset($this->class[$class_name])) {
                $data = $this->class[$class_name];
            } else {
                $goods_class = DB::table('goods_classes')->where('gc_name', $class_name)->select('id','parent_id')->first();
                if ($goods_class) {
                    $parent_id = DB::table('goods_classes')->where('id', $goods_class->parent_id)->value('parent_id');
                    $data = $this->class[$class_name] = [
                        'gc_id_1' => $parent_id ?: 0,
                        'gc_id_2' => $goods_class->parent_id,
                        'gc_id_3' => $goods_class->id,
                    ];
                }
            }
        }
        return $data;
    }

    /**
     * 获取店铺分类id
     * @param $class_name
     * @param $shop_id
     * @return mixed
     */
    private function getShopClass($class_name, $shop_id)
    {
        $data['goods_shop_c_lv1'] = $data['goods_shop_c_lv2'] = 0;
        if ($class_name) {
            if (isset($this->shopClass[$class_name])) {
                $data = $this->shopClass[$class_name];
            } else {
                $goods_class = DB::table('shop_cats')
                    ->where('cat_name', $class_name)
                    ->where('level', 2)
                    ->where('shop_id', $shop_id)
                    ->select('id','parent_id')->first();
                if($goods_class){
                    $data = $this->shopClass[$class_name] = [
                        'goods_shop_c_lv1' => $goods_class->parent_id,
                        'goods_shop_c_lv2' => $goods_class->id,
                    ];
                }
            }
        }
        return $data;
    }

    /**
     * 获取品牌id
     * @param $brand_name
     * @return mixed
     */
    private function getBrandId($brand_name)
    {
        $brand_id = 0;
        if ($brand_name) {
            if (isset($this->brand[$brand_name])) {
                $brand_id = $this->brand[$brand_name];
            } else {
                $brand_id = DB::table('brands')->where('brand_name', $brand_name)->value('id');
                $brand_id = $this->brand[$brand_name] = $brand_id ?: 0;
            }
        }
        return $brand_id;
    }


    /**
     * 校验
     *
     * @param $ruledata
     * @return bool
     * @throws \Exception
     */
    private function __check($ruledata)
    {
        $rules    = [
            'goods_name'        => 'required',
            'brand_id'          => 'required',
            'gc_id'             => 'required',
            'goods_price'       => 'required|numeric',
            'goods_cost'        => 'required|numeric',
            'goods_marketprice' => 'required|numeric',
            'goods_stock'       => 'required|numeric',
            'rewards'           => 'numeric',
            'faker_sales'       => 'numeric',
//            'image_list'        => 'required',
        ];
        $messages = [
            'goods_name.required'        => '请填写商品名称',
            'brand_id.required'          => '请填写正确的品牌',
            'gc_id.required'             => '请填写正确的分类',
            'goods_price.required'       => '请填写商品价格',
            'goods_cost.required'        => '请填写成本价',
            'goods_marketprice.required' => '请填写市场价',
            'goods_stock.required'       => '请填写商品库存',
            'goods_price.numeric'        => '商品价格参数错误',
            'goods_cost.numeric'         => '成本价参数错误',
            'goods_stock.numeric'        => '商品库存参数错误',
            'goods_marketprice.numeric'  => '市场价参数错误',
            'rewards.numeric'            => '商品佣金参数错误',
            'faker_sales.numeric'        => '销量参数错误',
//            'image_list.required'        => '请填写商品图册',
        ];
        $validator = Validator::make($ruledata, $rules, $messages);

        $errors = $validator->errors()->all();
        if ($errors) {
            $errors = implode('|', $errors);
            throw new \Exception($errors);
        }

        if($ruledata['goods_stock'] < $ruledata['goods_stock_alarm'])
        {
            throw new \Exception('设置报警值不能大于库存值!');
        }

        if($ruledata['goods_price'] <= $ruledata['rewards'])
        {
            throw new \Exception('分销金额请勿大于销售金额!');
        }

        return true;
    }

}