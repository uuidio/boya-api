<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ShopSiteConfig extends Model
{
//    protected $table = 'site_configs';

    protected $guarded = [];

    protected $casts = [
        'value' => 'array'
    ];
}
