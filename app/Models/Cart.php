<?php
/**
 * @Filename        cart.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Shop;

class Cart extends Model
{
    protected $guarded = [];

    protected $appends = ['goods_price', 'goods_info', 'sku_info'];

    /**
     * 商品价格
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getGoodsPriceAttribute()
    {
        if($this->params=='seckill'){
            $goods= SecKillGood::where(['seckill_ap_id'=>$this->activity_id,'sku_id'=>$this->sku_id])->select('seckill_price')->first();
            $goods->goods_price=isset($goods['seckill_price'])?$goods['seckill_price']:0;
        }else{
            //取商品规格表价格
            $goods = GoodsSku::find($this->sku_id);
        }

        return empty($goods) ? 0 : $goods->goods_price;
    }

    /**
     * 商品信息
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getGoodsInfoAttribute()
    {
        $goods_info = DB::table('goods')
            ->select('id', 'goods_name', 'goods_info', 'shop_id', 'gc_id', 'goods_serial', 'goods_state', 'goods_marketprice')
            ->where('id', $this->goods_id)
            ->first();
        if ($goods_info) {
            $goods_info->goods_info = $goods_info->goods_info ?: '';
        }
        return $goods_info;
    }

    /**
     * 商品sku信息
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getSkuInfoAttribute()
    {
        $sku = GoodsSku::where(['id' => $this->sku_id])->first();
        $sku_info = [];
        if ($sku) {
            $spec_name_array = empty($sku['spec_name']) ? null : unserialize($sku['spec_name']);
            if ($spec_name_array && is_array($spec_name_array)) {
                $goods_spec_array = array_values($sku['goods_spec']);
                foreach ($spec_name_array as $k => $spec_name) {
                    $goods_spec = isset($goods_spec_array[$k]) ? ':' . $goods_spec_array[$k] : '';
                    $sku_info[] = $spec_name . $goods_spec;
                }
            }
            $sku_info = implode(' ', $sku_info);
        }
        return $sku_info ?: '';
    }


    /**
     *
     * 店铺信息
     *
     * @Author hfh_wind
     * @return int

    public function getShopsInfoAttribute()
    {
//        $shops = Shop::where('id',$this->shop_id) ->first();
        $shopInfo = DB::table('shops')
            ->select('id', 'shop_name', 'point_id', 'housing_id', 'shop_logo', 'is_own_shop', 'shop_type')
            ->where('id', $this->shop_id)
            ->first();
        return empty($shopInfo) ? 0 : $shopInfo;
    }
*/
}
