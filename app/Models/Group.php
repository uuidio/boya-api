<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['shop_name','count_group', 'sku_info','is_show_text'];


    /**
     * 店铺名称
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getShopNameAttribute()
    {
        $shop = Shop::find($this->shop_id);
        return isset($shop->shop_name)?$shop->shop_name:'';
    }



    /**
     * 拼团成功数量
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getCountGroupAttribute()
    {
        $count = GroupsUserOrder::where(['groups_id'=>$this->id,'status'=>'2'])->count();
        return $count;
    }


    public function getIsShowTextAttribute()
    { 
        return $this->is_show>0?'是':'否';
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
        return $sku_info;
    }

    /**
     * [joinSecKill 参与秒杀活动的商品]
     * @param [type] $goods_id [商品id]
     * @param [type] $start [是否展示正在开始的]
     */
    public static function joinGroup($goods_id, $start = false)
    {
        $model = Group::where('goods_id',$goods_id)
                ->where('end_time','>=',nowTimeString())
                ->whereNotNull('end_time');
        if ($start) {
            $model = $model->where('start_time','<=',nowTimeString());
        }
        $group = $model->orderBy('group_price','asc')->first();
        return $group;
    }
}
