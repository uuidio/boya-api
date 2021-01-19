<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class WxMiniSubscribeMessages extends Model
{
    //
    protected $guarded = [];


    protected $casts = [
        'contents' => 'array',
        'data' => 'array'
    ];
}
