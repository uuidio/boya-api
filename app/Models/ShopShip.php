<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ShopShip extends Model
{
	protected $table = 'shop_ships';
    protected $guarded = [];
    protected $appends = ['content', 'is_default'];

    /**
     * *****  运费规则   ****
     */
    public function setRulesAttribute($value)
    {
    	foreach ($value as $k => $v) {
    		$r = $v['limit'].'|'.$v['post'];
    		$arr[] = $r;
    	}
        $this->attributes['rules'] = implode(',', $arr);
    }

    public function getRulesAttribute($value)
    {
    	$list = explode(',', $value);
    	foreach ($list as $k => $v) {
    		$data = explode('|',$v);
    		$rule['limit'] = $data[0];
    		$rule['post'] = $data[1];
    		$res[] = $rule;
    	}
        return $res;
    }

    /**
     * [getContentAttribute 列表规则展示]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    public function getContentAttribute()
    {
    	switch ($this->add_type) {
    		case 1:
    			$head = '满';
    			break;
    		case 2:
    			$head = '每';
    			break;
    	}
    	switch ($this->type) {
    		case 1:
    			$unit = '元';
    			break;
    		case 2:
    			$unit = 'KG';
    			break;
    		case 3:
    			$unit = 'L';
    			break;
    		case 4:
    			$unit = '件';
    			break;
    	}
    	foreach ($this->rules as $k => $v) {
    		$content[] = $head.$v['limit'].$unit.'运费'.$v['post'].'元';
    	}
    	return implode(',', $content);
    }


    public function getIsDefaultAttribute()
    {
        if ($this->default) {
            return '默认';
        }else{
        	return '普通';
        }
    }

}
