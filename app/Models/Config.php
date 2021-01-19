<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $guarded = [];


    public static function getPhysicalImg()
    {
    	$physical_img = self::where(['page' => 'pay_wallet','group' => 'physical_img'])->value('value');
    	return !empty($physical_img) ? $physical_img : config('paytype.physical_img');
    }
}
