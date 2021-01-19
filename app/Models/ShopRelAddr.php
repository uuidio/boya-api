<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ShopRelAddr extends Model
{
    protected $guarded = [];
    protected $hidden = ['province','city', 'county', 'area_code','postal_code','shop_id'];
}
