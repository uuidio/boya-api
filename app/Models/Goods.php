<?php
/**
 * @Filename        Goods.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use ShopEM\Models\GoodsClass;
use ShopEM\Models\Brand;
use ShopEM\Models\GoodsImage;
use ShopEM\Models\Shop;

class Goods extends Model
{
    protected $guarded = [];

    protected $appends = ['class_name', 'brand_name', 'image_list', 'shop', 'shop_name', 'shop_cate', 'source_info', 'goods_stock', 'sku', 'gm_name', 'point_unit', 'goods_cost', 'web_show_price', 'is_rebate_text', 'good_state_text', 'is_show_price', 'goods_shop_class_name1',
        'goods_shop_class_name2'];


    //修改库存显示 以sku库存为主 nlx 2019-9-17 10:43:18
    public function getGoodsStockAttribute($value)
    {
        $skuStock = GoodsSku::where('goods_id', $this->id)->sum('goods_stock');
//        if ($skuStock != $value) {
//            self::where('id',$this->id)->update(['goods_stock'=>$skuStock]);
//        }
        return $skuStock;
    }

    public function getGoodsStateAttribute($value)
    {
        if ($value > 0) {
            $gm_status = GmPlatform::where('gm_id', $this->gm_id)->value('status');
            if ($gm_status != 1) {
                return 0;
            }
            $shop_state = Shop::where('id', $this->shop_id)->value('shop_state');
            if ($shop_state != 1) {
                return 0;
            }
        }
        return $value;
    }

    /**
     * [getShowWebPriceAttribute 前端展示价格]
     * @param string $value [description]
     * @return [type]        [description]
     */
    public function getWebShowPriceAttribute()
    {
        //商品秒杀价格
        $seckill = SecKillGood::joinSecKill($this->id);
        if ($seckill) {
            return $seckill->seckill_price;
        }
        //商品秒杀价格
        $group = Group::joinGroup($this->id);
        if ($group) {
            return $group->group_price;
        }
        return 0;
    }

    /**
     * 销量
     *
     * @Author djw
     * @return string
     */
    public function getGoodsSalenumAttribute()
    {
        $goodsCount = GoodsCount::where(['goods_id' => $this->id])->first();
        return isset($goodsCount['sold_quantity']) ? $goodsCount['sold_quantity'] : 0;
    }

    /**
     * 追加商品名称
     *
     * @Author moocde <mo@mocode.cn>
     * @return string
     */
    public function getClassNameAttribute()
    {
        $class = GoodsClass::find($this->gc_id_3);
        if (empty($class)) {
            return '';
        }
        return $class->gc_name;
    }

    /**
     * 追加品牌名称
     *
     * @Author moocde <mo@mocode.cn>
     * @return string
     */
    public function getBrandNameAttribute()
    {
        $brand = Brand::find($this->brand_id);
        if (empty($brand)) {
            return '';
        }
        return $brand->brand_name;
    }

    /**
     * 追加库存
     *
     * @Author moocde <mo@mocode.cn>
     * @return string
     */
    /*public function getGoodsStockAttribute()
    {
        $goods_stock = 0;
        $goodssku= GoodsSku::where(['goods_id'=>$this->id])->get();
        foreach($goodssku as $key =>$value){
            $goods_stock += $value['goods_stock'];//总库存
        }
        return $goods_stock;
    }*/

    /**
     * 追加库sku
     *
     * @Author moocde <mo@mocode.cn>
     * @return string
     */
    public function getSkuAttribute()
    {
        $sku = [];
        if (!empty($this->goods_spec)) {
            $goodssku = GoodsSku::where(['goods_id' => $this->id])->select('id', 'goods_price', 'goods_cost',
                'goods_marketprice', 'goods_serial', 'goods_barcode', 'goods_stock_alarm', 'goods_spec', 'goods_stock',
                'spec_sign')->get();
            $param = [];
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
                $param[$key]['spec_sign'] = $value['spec_sign'];
            }
            $sku = $param;
        } else {
            $goodssku = GoodsSku::where(['goods_id' => $this->id])->select('id')->first();
            $sku['id'] = $goodssku['id'];
            $sku['price'] = $goodssku['goods_price'];//商品售价
            $sku['goods_cost'] = $goodssku['goods_cost'];//成本价
            $sku['marketprice'] = $goodssku['goods_marketprice']; //市场价
            $sku['sku'] = $goodssku['goods_serial'];//货号
            $sku['barcode'] = $goodssku['goods_barcode'];//条形码
            $sku['alarm'] = $goodssku['goods_stock_alarm'];//预警库存
            $sku['sp_value'] = $goodssku['goods_spec']; //规格值
            $sku['stock'] = $goodssku['goods_stock'];
            $sku['spec_sign'] = $goodssku['spec_sign'];
        }
        return $sku;
    }


    /**
     * 反序列化属性值
     * @Author hfh_wind
     * @return string
     */
    public function getGoodsAttrAttribute($value)
    {
        return empty($value) ? null : unserialize($value);
    }


    /**
     * 反序列化规格名称
     * @Author hfh_wind
     * @return mixed|null
     */
    public function getSpecNameAttribute($value)
    {
        return empty($value) ? null : unserialize($value);
    }


    /**
     * 反序列化规格值
     * @Author hfh_wind
     * @return mixed|null
     */
    public function getGoodsSpecAttribute($value)
    {
        return empty($value) ? null : unserialize($value);
    }


    /**
     * 反序列化店铺分类
     * @Author hfh_wind
     * @return mixed|null
     */
    public function getShopCateAttribute()
    {
        return empty($this->goods_shop_cid) ? null : unserialize($this->goods_shop_cid);
    }


    /**
     * 追加图片列表
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getImageListAttribute()
    {
        $images = GoodsImage::where('goods_id', $this->id)->get();
        $goods_image = [];
        if ($images) {
            foreach ($images as $image) {
                $goods_image[] = [
                    'image_url' => $image['image_url'],
                    'status'    => 'finished',
                ];
            }
        }
        return $goods_image;
    }

    /**
     * 店铺信息
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getShopAttribute()
    {
        return Shop::find($this->shop_id);
    }

    public function getShopNameAttribute()
    {
        return $this->shop['shop_name'];
    }


    /**
     * [getSourceInfoAttribute 获取第三方来源信息与第三方模板商品信息]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    public function getSourceInfoAttribute()
    {
        return [];
    }

    /**
     * [getGoodsSpecAttribute 提货方式]
     * @Author mssjxzw
     * @param  [type]  $value [description]
     * @return [type]         [description]
     */
    public function getPickTypeAttribute($value)
    {
        return explode(',', $value);
    }

    //info为空时显示空而非显示null
    public function getGoodsInfoAttribute($value)
    {
        return $value ?: '';
    }

    /**
     * 追加项目名称
     * @Author swl
     * @return string
     */
    public function getGmNameAttribute()
    {
        $shop_info = GmPlatform::where('gm_id', '=', $this->gm_id)->select('platform_name')->first();

        return isset($shop_info['platform_name']) ? $shop_info['platform_name'] : '';
    }


    public function getPointUnitAttribute()
    {
        if ($this->gm_id == GmPlatform::gmSelf()) {
            return '牛币';
        }
        return '积分';
    }

    /**
     * 追加成本价
     * @Author Huiho
     * @return string
     */

    public function getGoodsCostAttribute()
    {
        $goods_cost = GoodsSku::where('goods_id', $this->id)->value('goods_cost');
        return isset($goods_cost) ? $goods_cost : '0.00';
    }


    public function getImgMaterialAttribute($val)
    {
        return json_decode($val, true);
    }


    /**
     * 追加是否推广商品
     * @Author hfh_wind
     * @return array
     */
    public function getIsRebateTextAttribute()
    {
        return $this->is_rebate ? "是" : "否";
    }

    // 追加状态描述
    public function getGoodStateTextAttribute()
    {
        $statusText = [
            0  => '下架',
            1  => '上架',
            10 => '禁售',
            20 => '已回收',
        ];
        return isset($statusText[$this->goods_state]) ? $statusText[$this->goods_state] : '';
    }

    // 追加是否显示市场价
    public function getIsShowPriceAttribute()
    {
        return $this->show_promotion_price ? "是" : "否";
    }

    /**
     * 追加店铺一级分类名称
     * @Author hfh
     * @return mixed|string
     */
    public function getGoodsShopClassName1Attribute()
    {
        $gc_name = ShopCats::where('id', $this->goods_shop_c_lv1)->value('cat_name');
        return $gc_name;
    }

    /**
     * 追加店铺二级分类名称
     * @Author hfh
     * @return mixed|string
     */
    public function getGoodsShopClassName2Attribute()
    {
        $gc_name = ShopCats::where('id', $this->goods_shop_c_lv2)->value('cat_name');
        return $gc_name;
    }
}
