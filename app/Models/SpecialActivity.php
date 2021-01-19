<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SpecialActivity extends Model
{
	protected $table = 'special_activities';
    protected $shop_type_arr = ['flag'=>'品牌旗舰店','brand'=>'品牌专卖店','cat'=>'类目专营店','store'=>'多品类通用型'];
	protected $guarded = [];
    protected $appends = ['type_text','apply_time','effective_time','shop_type_text','goods_class_text'];

    public function getTypeTextAttribute()
    {
        $out='';
        switch ($this->type) {
        	case 1:
        		$out = '立减';
        		break;
        	case 2:
        		$out = '折扣';
        		break;
        }
        return $out;
    }
    public function getApplyTimeAttribute()
    {
        return $this->star_apply.'至'.$this->end_apply;
    }

    public function getEffectiveTimeAttribute()
    {
        return $this->star_time.'至'.$this->end_time;
    }

    public function getShopTypeTextAttribute()
    {
        if ($this->shop_type) {
            $out = [];
//            $type = explode(',', $this->shop_type);
//            if(!empty($type)){
//                foreach ($type as $k => $v) {
//                    $out[] = $this->shop_type_arr[$v];
//                }
//                return implode(',', $out);
//            }
        }
    }

    public function getGoodsClassTextAttribute()
    {
        if ($this->goods_class) {
            $out = [];
            $ids = explode(',',$this->goods_class);
            $class = GoodsClass::whereIn('id',$ids)->get();
            foreach ($class as $k => $v) {
                $out[] = $v->gc_name;
            }
            return implode(',', $out);
        }
    }

    public function setRangeAttribute($value)
    {
        if (isset($value['from']) && $value['from'] && isset($value['to']) && $value['to']) {
        	$this->attributes['range'] = implode('-', $value);
        }else{
        	$this->attributes['range'] = '100-100';
        }
    }

    public function setShopTypeAttribute($value)
    {
        if (is_array($value)) {
        	$this->attributes['shop_type'] = implode(',', $value);
        }elseif (is_string($value)) {
        	$this->attributes['shop_type'] = $value;
        }else{
        	$this->attributes['shop_type'] = '';
        }
    }

    public function setGoodsClassAttribute($value)
    {
        if (is_array($value)) {
        	foreach ($value as $k => $v) {
        		if (isset($v['id'])) {
        			$out[] = $v['id'];
        		}
        	}
        	$this->attributes['goods_class'] = implode(',', $out??[]);
        }elseif (is_string($value)) {
        	$this->attributes['goods_class'] = $value;
        }else{
        	$this->attributes['goods_class'] = '';
        }
    }

    public function setStarApplyAttribute($value)
    {
        $this->attributes['star_apply'] = Carbon::parse($value)->toDateTimeString();
    }

    public function setEndApplyAttribute($value)
    {
        $this->attributes['end_apply'] = Carbon::parse($value)->toDateTimeString();
    }

    public function setStarTimeAttribute($value)
    {
        $this->attributes['star_time'] = Carbon::parse($value)->toDateTimeString();
    }

    public function setEndTimeAttribute($value)
    {
        $this->attributes['end_time'] = Carbon::parse($value)->toDateTimeString();
    }
}
