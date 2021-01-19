<?php
/**
 * @Filename        GoodsService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Services;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\Cart;
use ShopEM\Models\Goods;
use ShopEM\Models\GoodsCount;
use ShopEM\Models\GoodsImage;
use ShopEM\Models\GoodsSku;
use ShopEM\Models\AlbumPic;
use ShopEM\Models\GoodsStockLog;
use ShopEM\Models\PointActivityGoods;
use ShopEM\Models\TradeOrder;
use Illuminate\Support\Facades\Storage;

class GoodsService
{
    /**
     * 商品是否可以销售
     *
     * @Author moocde <mo@mocode.cn>
     * @param $goods_id
     * @return array
     */
    public static function saleAble($sku_id, $user_id = 0, $quantity = 0)
    {
        $return = [
            'code'    => 0,
            'message' => '商品不存在',
            'data'    => [],
        ];

        $sku = GoodsSku::find($sku_id);

        $goods = '';
        if ($sku) {
            $goods = Goods::find($sku->goods_id);
        }

        if (empty($goods)) {
            return $return;
        }

        if ($goods->goods_state != 1) {
            $return['message'] = '商品已下架';
            return $return;
        }

        if ($sku->goods_stock < $quantity) {
            $return['message'] = '库存不足';
            return $return;
        }

        if ($sku->goods_stock == 0) {
            $return['message'] = '库存为零!';
            return $return;
        }

        //判断用户是否达到了购买上限
        if ($user_id) {
            $buy_limit = self::getUserBuyLimit($sku->goods_id, $user_id, $quantity);
            if ($buy_limit['code'] === 0 ) {
                $return['message'] = $buy_limit['message'];
                return $return;
            }
        }

        $return['code'] = 1;
        $return['data_sku'] = $sku;
        $return['data_goods'] = $goods;

        return $return;
    }


    /**
     * 恢复库存
     * @Author hfh_wind
     * @param $params
     * @return bool
     */
    public function storeRecover($params)
    {
        $count = DB::table('goods_skus')->where(['id' => $params['sku_id'], 'goods_id' => $params['goods_id']])->count();
        if ($count) {
            $subStock = $params['sub_stock'] = '';
            $tradePay = $params['tradePay'];
            //        unset($params['sub_stock']);
            unset($params['tradePay']);
            if ($tradePay || $subStock) {
                $isRecover = $this->recoverItemStore($params);
                if (!$isRecover) {
                    return false;
                }
            } else {
                $isRecover = $this->unfreezeItemStore($params);
                if (!$isRecover) {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * 取消订单恢复库存
     *
     * @Author hfh_wind
     * @param array $arrParams
     * @return bool
     */
    public function recoverItemStore($arrParams = array())
    {
        $this->incrbyStore($arrParams['goods_id'], $arrParams['sku_id'], $arrParams['quantity']);

        //记录库存日志
        $sku = DB::table('goods_skus')->where(['id' => $arrParams['sku_id'], 'goods_id' => $arrParams['goods_id']])->select('goods_stock')->first();
        $goods_stock = $sku->goods_stock ?? 0;
        $arrParams['goods_stock'] = $goods_stock;
        $arrParams['change'] = $arrParams['quantity'];
        $arrParams['type'] = 'inc';
        $arrParams['note'] = "取消订单增加库存";
        $this->GoodsStockLogs($arrParams);

        return true;
    }


    /**
     * 付款减库存时冻结库存
     *
     * @Author hfh_wind
     * @param array $arrParams
     * @return bool
     */
    public function freezeItemStore($arrParams = array())
    {
        $this->decrbyStore($arrParams['goods_id'], $arrParams['sku_id'], $arrParams['quantity'], true);

        //记录库存日志
        $sku = DB::table('goods_skus')->where(['id' => $arrParams['sku_id'], 'goods_id' => $arrParams['goods_id']])->select('goods_stock')->first();
        $goods_stock = $sku->goods_stock ?? 0;
        $arrParams['goods_stock'] = $goods_stock;
        $arrParams['change'] = $arrParams['quantity'];
        $arrParams['type'] = 'dec';
        $arrParams['note'] = "订单减库存";
        $this->GoodsStockLogs($arrParams);


        return true;
    }


    /**
     * 付款减库存情况下取消订单释放库存
     *
     * @Author hfh_wind
     * @param array $arrParams
     * @return bool
     */
    public function unfreezeItemStore($arrParams = array())
    {
        $this->incrbyStore($arrParams['goods_id'], $arrParams['sku_id'], $arrParams['quantity'], true);

        //记录库存日志
        $sku = DB::table('goods_skus')->where(['id' => $arrParams['sku_id'], 'goods_id' => $arrParams['goods_id']])->select('goods_stock')->first();
        $goods_stock = $sku->goods_stock ?? 0;
        $arrParams['goods_stock'] = $goods_stock;
        $arrParams['change'] = $arrParams['quantity'];
        $arrParams['type'] = 'inc';
        $arrParams['note'] = "退款增加库存";
        $this->GoodsStockLogs($arrParams);

        return true;
    }

    /**
     * 增加库存数据 恢复库存
     *
     * @Author hfh_wind
     * @param $itemId
     * @param $skuId
     * @param $num
     * @param bool $isFreez
     * @return bool
     */
    public function incrbyStore($goods_id, $skuId, $num, $isFreez = false)
    {
        try {
            // Goods::where(['id' => $goods_id])->increment('goods_stock', $num);
            $res = GoodsSku::where(['id' => $skuId, 'goods_id' => $goods_id])->increment('goods_stock', $num);

            if (!$res) {
                throw new \Exception('商品库存恢复失败!');
            }

        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new \Exception($message . '商品库存恢复失败!');
        }

        return true;
    }


    /**
     * 用于下单减库存和下单冻结库存
     *
     * @Author hfh_wind
     * @param $itemId
     * @param $skuId
     * @param $num
     * @param bool $isFreez
     * @return mixed
     */
    public function decrbyStore($goods_id, $skuId, $num, $isFreez = false)
    {

        try {

            // $res = Goods::where(['id' => $goods_id])->where('goods_stock', '>=', $num)->decrement('goods_stock', $num);
            $res = GoodsSku::where(['id' => $skuId, 'goods_id' => $goods_id])->where('goods_stock', '>=',
                $num)->decrement('goods_stock', $num);

            if (!$res) {
                throw new \Exception('库存不足,商品库存扣减失败!');
            }

        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new \Exception($message . '商品库存扣减失败!');
        }

        return true;
    }


    /**
     * 获取商品的可筛选条件
     *
     * @Author djw
     * @return bool
     * @throws \Exception
     */
    public function getGoodsFilter($gc_id = false)
    {
        //暂时写死
        $filter = [
            'brand'
        ];

        $result = [];
        //获取可筛选的品牌
        if (in_array('brand', $filter)) {
            $brandModel = new \ShopEM\Models\Brand();
            if ($gc_id) {
                $brandModel = $brandModel->where('class_id', $gc_id);
            }
            $result[] = [
                'filter' => 'brand',
                'text'   => '品牌',
                'data'   => $brandModel->get()->keyBy('id')->toArray()
            ];
        }

        return $result;
    }

    /**
     * 热门搜索关键字的检验
     *
     * @Author djw
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function hotKeywordCheck($keyword, $id = false , $gm_id = 0)
    {
        $model = new \ShopEM\Models\GoodsHotkeywords();
        $model = $model->where('keyword', $keyword);
        if ($id) {
            $model = $model->where('id', '!=', $id);
        }
        if ($gm_id > 0)
        {
            $model = $model->where('gm_id', '=', $gm_id);
        }
        return $model->first() ? false : true;
    }

    public function storage($data, $id = 0)
    {
        /* date内容
        'goods_name', 'goods_info', 'shop_id', 'gc_id_1', 'gc_id_2', 'gc_id_3', 'brand_id','goods_price', 'goods_marketprice', 'goods_cost',goods_marketprice,'goods_serial','goods_stock','goods_image',goods_state,'goods_body'
         */
        $spec = "";
        if (isset($data['spec'])) {
           // $data['spec_name'] = json_decode($data['spec_name'], true);
           // $data['goods_spec'] = json_decode($data['goods_spec'], true);
            $spec = is_array($data['spec']) ? $data['spec'] : json_decode($data['spec'], true);
            if (!isset($data['spec_name']) || !isset($data['goods_spec'])) {
                return ['code' => 701, 'msg' => '多规格参数不完整'];
            }
        }
        $data['spec_name'] = is_array($spec) ? serialize($data['spec_name']) : serialize(null);
        $data['goods_spec'] = is_array($spec) ? serialize($data['goods_spec']) : serialize(null);
        $data['goods_serial'] = $data['goods_serial'] ?? strtoupper(uniqid('s')); //不存在货号时自动生成一个
        $data['goods_cost'] = $data['goods_cost'] ?? 0; //成本价的默认值
        if (isset($data['spec'])) {
            unset($data['spec']);
        }
        if (isset($data['goods_shop_cid'])) {
            $data['goods_shop_cid'] = serialize($data['goods_shop_cid']);
        }
        //属性
        if (isset($data['goods_attr'])) {
            $data['goods_attr'] = serialize($data['goods_attr']);
        }

        // 销售价不能小于0.01
        if($data['goods_price']<0.01){
            return ['code' => 701, 'msg' => '商品价格最低为0.01元'];
        }
        DB::beginTransaction();
        try {
            $local = Storage::disk('local')->url('');
            if ($id) {
                $goods = Goods::where('id', $id)->where('shop_id', $data['shop_id'])->first();
                if (empty($goods)) {
                    return ['code' => 701, 'msg' => '无此商品'];
                }

                //  检查商品是否参加秒杀、团购、积分专区活动，如果参加，不让编辑     2020/06/23
                $now = now()->toDateTimeString();
                $pointGoods = PointActivityGoods::where('goods_id', $goods->id)->where('active_start','<=',$now)->where('active_end','>=', $now)->first();
                $secKillService = new SecKillService();
                $groupService = new GroupService();
                if ($secKillService->actingSecKill($goods->id) || $groupService->actingGroup($goods->id) || $pointGoods) {
                    return ['code' => 701, 'msg' => '无法编辑活动中的商品'];
                }
                $imageData = [];
                $pic_arr = [];
                if (isset($data['image_list']) && is_array($data['image_list'])) {
                    foreach ($data['image_list'] as $image) {
                        $imageData[] = $image['url'];
                        $pic_arr[] = str_replace($local, '', $image['url']);
                    }
                }
                unset($data['image_list']);
                $old_name = $goods->goods_name;
                $goods_data = $data;
                unset($goods_data['goods_stock']);

                // 转换图片素材类型
                if (isset($data['img_material']) ) {
                    $goods_data['img_material'] = json_encode($data['img_material']);
                }


                $goods->update($goods_data);
            } else {
                $image_list = $data['image_list']??[];
                unset($data['image_list']);
                $goods_data = $data;
                unset($goods_data['goods_stock']);

                // 转换图片素材类型
                if (isset($data['img_material']) ) {
                    $goods_data['img_material'] = json_encode($data['img_material']);
                }

                $goods = Goods::create($goods_data);
                $imageData = [];
                $pic_arr = [];
                if ($image_list && is_array($image_list)) {
                    foreach ($image_list as $image) {
                        $tmp = [];
                        $tmp['goods_id'] = $goods->id;
                        $tmp['shop_id'] = $data['shop_id'];
                        $tmp['image_url'] = $image['url'];
                        $pic_arr[] = str_replace($local, '', $image['url']);
                        $imageData[] = $tmp;
                    }
                }
            }
            //sku不存商品属性
            if (array_key_exists('goods_attr', $data)) {
                unset($data['goods_attr']);
            }
            if (array_key_exists('supply_link', $data)) {
                unset($data['supply_link']);
            }
            if (array_key_exists('source', $data)) {
                unset($data['source']);
            }
            if (array_key_exists('pick_type', $data)) {
                unset($data['pick_type']);
            }
            if (array_key_exists('trade_type', $data)) {
                unset($data['trade_type']);
            }
            if (array_key_exists('package', $data)) {
                unset($data['package']);
            }
            if (array_key_exists('lhy_id', $data)) {
                unset($data['lhy_id']);
            }
            if (array_key_exists('third_attr_update', $data)) {
                unset($data['third_attr_update']);
            }
            if (array_key_exists('is_need_qq', $data)) {
                unset($data['is_need_qq']);
            }


            //删除素材图片
            unset($data['img_material']);
            //删除推广素材
            unset($data['promo_article']);
            //删除虚拟商品的配置信息
            unset($data['vir_aftersale_at']);
            unset($data['vir_aftersale_state']);
            //删除店铺分类id
            unset($data['goods_shop_c_lv1']);
            unset($data['goods_shop_c_lv2']);

            if ($id) {
                $res = $this->editSku($data, $goods->id, $spec);
                if (!empty($res)) {
                    GoodsSku::where(['goods_id' => $goods->id, 'shop_id' => $data['shop_id']])->whereNotIn('id',
                        $res)->delete();
                }
                $this->upImages($id, $data['shop_id'], $imageData);
                AlbumPic::where('pic_name', 'like', $old_name . '%')->update(['is_use' => 0]);
                $url_pic = str_replace($local, '', $data['goods_image']);
                $new = AlbumPic::where('pic_url', $url_pic)->first();
                if ($new) {
                    $new->pic_name = $data['goods_name'] . '(主图)';
                    $new->is_use = 1;
                    $new->save();
                }
                $pic_list = AlbumPic::whereIn('pic_url', $pic_arr)->get();
                foreach ($pic_list as $key => $value) {
                    $update = [
                        'pic_name' => $goods->goods_name . '(图册)',
                        'is_use'   => 1,
                    ];
                    AlbumPic::where('id', $value->id)->update($update);
                }
                DB::commit();
            } else {
                //保存sku 信息
                $sku_info=$this->addSku($data, $goods->id, $spec);
                $url_pic = str_replace($local, '', $goods->goods_image);
                $pic = AlbumPic::where('pic_url', $url_pic)->first();
                if ($pic) {
                    $pic->pic_name = $goods->goods_name . '(主图)';
                    $pic->is_use = 1;
                    $pic->save();
                }

                /*$insert['shop_id'] = $sku_info['shop_id'];
                $insert['goods_id'] = $sku_info['goods_id'];
                $insert['sku_id'] = $sku_info['id'];
                $insert['goods_stock'] = $sku_info['goods_stock'];
                $insert['type'] = 'add';
                $insert['note'] = "商品添加";

                $this->GoodsStockLogs($insert);*/

                DB::table('goods_images')->insert($imageData);
                $pic_list = AlbumPic::whereIn('pic_url', $pic_arr)->get();
                foreach ($pic_list as $key => $value) {
                    $update = [
                        'pic_name' => $goods->goods_name . '(图册)',
                        'is_use'   => 1,
                    ];
                    AlbumPic::where('id', $value->id)->update($update);
                }

                GoodsCount::create(['goods_id' => $goods->id]);
                DB::commit();
            }
            //以规格的最低价作为商品价格
            $sku_min_price = GoodsSku::where('goods_id',$goods->id)->orderBy('goods_price','asc')->value('goods_price');
            if ($sku_min_price) {
                Goods::where('id',$goods->id)->update(['goods_price'=>$sku_min_price]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return ['code' => 702, 'msg' => $e->getMessage()];
        }
        return ['code' => 0];
    }

    public function upImages($goods_id, $shop_id, $data)
    {
        $list = GoodsImage::where('goods_id', $goods_id)->get();
        if (count($list) > 0) {
            foreach ($list as $key => $value) {
                if (in_array($value->image_url, $data)) {
                    $k = array_search($value->image_url, $data);
                    unset($data[$k]);
                } else {
                    GoodsImage::where('id', $value->id)->delete();
                }
            }
        }
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $tmp = [];
                $tmp['goods_id'] = $goods_id;
                $tmp['shop_id'] = $shop_id;
                $tmp['image_url'] = $value;
                $insert[] = $tmp;
            }
            DB::table('goods_images')->insert($insert);
        }
    }

    /**
     * 用户是否达到购买上限
     * @Author djw
     * @param $goods_id
     * @param $user_id
     * @return array
     */
    public static function getUserBuyLimit($goods_id, $user_id, $quantity = 1)
    {
        $return = [
            'code'    => 1,
            'message' => '可以购买',
            'data'    => [],
        ];
        $goods = Goods::find($goods_id);
        if ($goods->buy_limit > 0) {
            $status = [
                'TRADE_CLOSED',
                'TRADE_CLOSED_BY_SYSTEM',
                'TRADE_CLOSED_AFTER_PAY',
            ];
            $quantity += TradeOrder::where('user_id', $user_id)->where('goods_id', $goods_id)->whereNotIn('status',
                $status)->sum('quantity');
            if ($goods->buy_limit <= $quantity) {
                $return = [
                    'code'    => 0,
                    'message' => '超过购买上限，每人限购' . $goods->buy_limit . '件',
                    'data'    => [],
                ];
            }
        }
        return $return;
    }

    /**
     * 生成商品sku返回商品ID(SKU)数组
     *
     * @Author hfh_wind
     * @param $param
     * @param $goods_id
     * @param $common_array
     * @return array
     */
    private function addSku($param, $goods_id, $spec)
    {
        unset($param['source'], $param['lhy_id']);
        // 商品规格
        if (is_array($spec)) {
//            $spec_name = serialize($param['spec_name']);
            $spec_name = $param['spec_name'];
            foreach ($spec as $value) {
                if($value['price'] < 0.01){
                    throw new \Exception('销售价最低为0.01元');
                }
                if (empty($value['price']) || empty($value['marketprice'])) {
                    throw new \Exception('销售价,市场价必填!');
                }
                if ($value['stock'] == null) {
                    throw new \Exception('请填写商品库存');
                }

                $param['goods_cost'] = $value['goods_cost'] ?? 0; //成本价的默认值
                $param['goods_price'] = $value['price'];//商品售价
                $param['promotion_price'] = $value['price'];//活动价
                $param['goods_marketprice'] = $value['marketprice']; //市场价
//                $param['goods_serial'] = $value['sku'];//货号
                $param['goods_serial'] = $value['sku'] ?? strtoupper(uniqid('s')); //不存在货号时自动生成一个
                $param['goods_barcode'] = $value['barcode'];//条形码
                $alarm = $value['alarm'] ?? 0;
                $param['goods_stock_alarm'] = intval($alarm);//预警库存
                $param['spec_name'] = $spec_name; //规格名称
                $param['goods_spec'] = serialize($value['sp_value']); //规格值
                $param['goods_stock'] = $value['stock'];
                $param['goods_id'] = $goods_id;
                $param['spec_sign'] = implode('_', $value['sp_value']);
                $sku = GoodsSku::create($param);

                //库存日志
                $insert['shop_id'] = $param['shop_id'];
                $insert['goods_id'] = $goods_id;
                $insert['sku_id'] = $sku->id;;
                $insert['goods_stock'] = $param['goods_stock'];
                $insert['change'] = $param['goods_stock'];
                $insert['type'] = 'add';
                $insert['note'] = "商品添加";

                $this->GoodsStockLogs($insert);
            }
        } else {
//            $goods = $this->_initGoodsByCommonGoods($goods_id, $param);
            $param['spec_name'] = serialize(null);
            $param['goods_spec'] = serialize(null);
            $param['goods_stock'] = intval($param['goods_stock']);
            $param['goods_id'] = $goods_id;
            $sku = GoodsSku::create($param);

            //库存日志
            $insert['shop_id'] = $param['shop_id'];
            $insert['goods_id'] = $goods_id;
            $insert['sku_id'] = $sku->id;
            $insert['goods_stock'] = $param['goods_stock'];
            $insert['change'] = $param['goods_stock'];
            $insert['type'] = 'add';
            $insert['note'] = "商品添加";

            $this->GoodsStockLogs($insert);
        }

        return $sku;
    }

    /**
     * 更新sku数据
     *
     * @Author hfh_wind
     * @param $param
     * @param $goods_id
     * @param $spec
     * @return array
     */
    private function editSku($param, $goods_id, $spec)
    {
        unset($param['source'], $param['lhy_id']);
        $skuid_array = [];
        if (!empty($spec)) {
           // $spec_name = serialize($param['spec_name']);
            $spec_name = $param['spec_name'];
            foreach ($spec as $value) {
                if($value['price'] < 0.01){
                    throw new \Exception('销售价最低为0.01元');
                }
                if (empty($value['price']) || empty($value['marketprice'])) {
                    throw new \Exception('销售价,市场价必填!');
                }
                if ($value['stock'] == null) {
                    throw new \Exception('请填写商品库存');
                }


                if (isset($value['id']) && !empty($value['id'])) {
                    $goods_info = GoodsSku::where([
                        'id'       => $value['id'],
                        'goods_id' => $goods_id,
                    ])->first();
                    if ($goods_info->id) {
                        $old_goods_stock = $goods_info->goods_stock;
                        $param['goods_price'] = $value['price'];
                        $param['promotion_price'] = $value['price'];
                        $param['goods_marketprice'] = $value['marketprice'] == 0 ? $param['goods_marketprice'] : $value['marketprice'];
                       // $param['goods_serial'] = $value['sku'];//货号
                        $param['goods_serial'] = $value['sku'] ?? strtoupper(uniqid('s')); //不存在货号时自动生成一个
                        $param['goods_barcode'] = $value['barcode'];//条形码
                        $alarm = $value['alarm'] ?? 0;
                        $param['goods_stock_alarm'] = intval($alarm);//预警库存
                        $param['spec_name'] = $spec_name; //规格名称
                        $param['goods_spec'] = serialize($value['sp_value']); //规格值
                        $param['goods_stock'] = $value['stock'];
                        $param['goods_id'] = $goods_id;
                        $param['spec_sign'] = implode('_', $value['sp_value']);
                        $sku = GoodsSku::where([
                            'id'       => $value['id'],
                            'goods_id' => $goods_id,
                        ])->update($param);
                   // dd($sku);
                        $skuid_array[] = $value['id'];
                        //库存日志
                        $insert['shop_id'] = $param['shop_id'];
                        $insert['goods_id'] = $param['goods_id'];
                        $insert['sku_id'] = $value['id'];
                        $insert['goods_stock'] = $param['goods_stock'];
                        $goods_stock = $param['goods_stock'] >=0 ? $param['goods_stock'] : 0;
                        $insert['change'] = $goods_stock - $old_goods_stock;
                        $insert['type'] = 'edit';
                        $insert['note'] = "商品编辑";
                        $this->GoodsStockLogs($insert);
                    }

                } else {
                    $param['goods_price'] = $value['price'];
                    $param['promotion_price'] = $value['price'];
                    $param['goods_marketprice'] = $value['marketprice'];
                   // $param['goods_serial'] = $value['sku'];//货号
                    $param['goods_serial'] = $value['sku'] ?? strtoupper(uniqid('s')); //不存在货号时自动生成一个
                    $param['goods_barcode'] = $value['barcode'];//条形码
                    $alarm = $value['alarm'] ?? 0;
                    $param['goods_stock_alarm'] = intval($alarm);//预警库存
                    $param['spec_name'] = $spec_name; //规格名称
                    $param['goods_spec'] = serialize($value['sp_value']); //规格值
                    $param['goods_stock'] = $value['stock'];
                    $param['goods_id'] = $goods_id;
                    $param['spec_sign'] = implode('_', $value['sp_value']);
                    $sku = GoodsSku::create($param);
                    $skuid_array[] = $sku->id;
                    //库存日志
                    $insert['shop_id'] = $param['shop_id'];
                    $insert['goods_id'] = $param['goods_id'];
                    $insert['sku_id'] = $sku['id'];
                    $insert['goods_stock'] = $param['goods_stock'];
                    $insert['change'] = $param['goods_stock'];
                    $insert['type'] = 'edit';
                    $insert['note'] = "商品编辑";
                    $this->GoodsStockLogs($insert);
                }
            }
        } else {

            $goods_info = GoodsSku::where([
                'goods_id'   => $goods_id,
                'shop_id'    => $param['shop_id'],
                'goods_spec' => serialize(null),
            ])->first();

            if (!empty($goods_info)) {
                $old_goods_stock = $goods_info->goods_stock;

                $param['spec_name'] = serialize(null);
                $param['goods_spec'] = serialize(null);
                $param['goods_stock'] = intval($param['goods_stock']);
                $param['goods_id'] = $goods_id;
                $sku = GoodsSku::where([
                    'goods_id'   => $goods_id,
                    'shop_id'    => $param['shop_id'],
                    'goods_spec' => serialize(null),
                ])->update($param);
                //库存日志
                $insert['shop_id'] = $param['shop_id'];
                $insert['goods_id'] = $param['goods_id'];
                $insert['sku_id'] = $goods_info->id;
                $insert['goods_stock'] = $param['goods_stock'];
                $goods_stock = $param['goods_stock'] >=0 ? $param['goods_stock'] : 0;
                $insert['change'] = $goods_stock - $old_goods_stock;
                $insert['type'] = 'edit';
                $insert['note'] = "商品编辑";
                $this->GoodsStockLogs($insert);
            } else {
                $param['spec_name'] = serialize(null);
                $param['goods_spec'] = serialize(null);
                $param['goods_stock'] = intval($param['goods_stock']);
                $param['goods_id'] = $goods_id;
                $sku = GoodsSku::create($param);
                $skuid_array[] = $sku->id;
                //库存日志
                $insert['shop_id'] = $param['shop_id'];
                $insert['goods_id'] = $param['goods_id'];
                $insert['sku_id'] = $sku->id;
                $insert['goods_stock'] = $param['goods_stock'];
                $insert['change'] = $param['goods_stock'];
                $insert['type'] = 'edit';
                $insert['note'] = "商品编辑";
                $this->GoodsStockLogs($insert);
            }
        }
        return $skuid_array;
    }

    /**
     * 记录库存日志
     * @Author hfh_wind
     */
    public function GoodsStockLogs($data)
    {
        $insert['shop_id'] = $data['shop_id'];
        $insert['goods_id'] = $data['goods_id'];
        $insert['sku_id'] = $data['sku_id'];
        $insert['goods_stock'] = $data['goods_stock'];
        $insert['type'] = $data['type'];
        $insert['note'] = $data['note'];
        $insert['oid'] = $data['oid'] ?? null;
        $insert['change'] = $data['change'] ?? 0;

        GoodsStockLog::create($insert);
    }

    // 快捷编辑保存
    public function fastSave($param, $goods_id, $spec){

        // $spec_name = $param['spec_name'];
        foreach ($spec as $value) {

            if($value['price'] < 0.01){
                throw new \Exception('销售价最低为0.01元');
            }
            if (empty($value['price']) || empty($value['marketprice'])) {
                throw new \Exception('销售价,市场价必填!');
            }
            if ($value['stock'] == null) {
                throw new \Exception('请填写商品库存');
            }

            $goods_info = GoodsSku::where([
                'id'       => $value['id'],
                'goods_id' => $goods_id,
            ])->first();
            if ($goods_info->id) {
                $old_goods_stock = $goods_info->goods_stock;
                $up['goods_price'] = $value['price'];
                $up['promotion_price'] = $value['price'];
                $up['goods_marketprice'] = $value['marketprice'] == 0 ? $param['goods_marketprice'] : $value['marketprice'];
                $up['goods_cost'] = $value['goods_cost'] ?? 0; //成本价的默认值
                $up['goods_stock'] = $value['stock'];
                $up['goods_id'] = $goods_id;
                $sku = GoodsSku::where([
                    'id'       => $value['id'],
                    'goods_id' => $goods_id,
                ])->update($up);

                $skuid_array[] = $value['id'];
                //库存日志
                $insert['shop_id'] = $param['shop_id'];
                $insert['goods_id'] = $goods_id;
                $insert['sku_id'] = $value['id'];
                $insert['goods_stock'] = $up['goods_stock'];
                $goods_stock = $up['goods_stock'] >=0 ? $up['goods_stock'] : 0;
                $insert['change'] = $goods_stock - $old_goods_stock;
                $insert['type'] = 'edit';
                $insert['note'] = "商品编辑";
                $this->GoodsStockLogs($insert);
            }

        }

        // 单规格商品还需要修改商品主表
        if($param['type'] != 2){
            $good['goods_price'] = $spec[0]['price'];
            $good['promotion_price'] = $spec[0]['price'];
            $good['goods_marketprice'] = $spec[0]['marketprice'];
            $good['goods_cost'] = $spec[0]['goods_cost']??0;

            Goods::where('id',$goods_id)->update($good);
        }else{
             //以规格的最低价作为商品价格
            $sku_min_price = GoodsSku::where('goods_id',$goods_id)->orderBy('goods_price','asc')->value('goods_price');
            if ($sku_min_price) {
                Goods::where('id',$goods_id)->update(['goods_price'=>$sku_min_price]);
            }
        }

    }

}
