<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsAttribute extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['attr_is_show','attr_values'];


    /**
     * 是否显示
     * @Author hfh_wind
     * @return bool
     */
    public function getAttrIsShowAttribute()
    {
        $attr_show = boolval($this->attr_show);
        return $attr_show;
    }


    /**
     * 是否显示
     * @Author hfh_wind
     * @return bool
     */
    public function getAttrValuesAttribute()
    {
        $attr_values=GoodsAttributeValue::where('attr_id','=',$this->id)->select('id','attr_value_name')->get();
        $attr_values_info=[];
        if(count($attr_values) >0){
            $attr_values_info=$attr_values->toArray();
        }
        return $attr_values_info;
    }




}
