<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class MemberBenefitConfig extends Model
{
	protected $guarded = [];
    //
    //
    public function getValueAttribute()
    {
    	return empty($this->attributes['value'])?[]:json_decode($this->attributes['value'],1);
    }
}
