<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class TradeSplit extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['goods_name', 'sku_info','user_mobile'];

    public function getGoodsNameAttribute()
    {
        $goods =  Goods::select('goods_name')->where('id', $this->goods_id)->first();
        return isset($goods['goods_name']) ? $goods['goods_name'] : null;
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
        return $sku_info ?: '无规格';
    }


    public function getUserMobileAttribute()
    {
        return UserAccount::where('id',$this->user_id)->value('mobile');
    }
}
