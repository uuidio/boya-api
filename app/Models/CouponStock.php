<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class CouponStock extends Model
{
    protected $guarded = [];
    // protected $appends = ['qrcode_link'];

    // //二维码链接
    // public function getQrcodeLinkAttribute()
    // {
    //     return 'https://shop.hyplmm.com/activity?bn='.$this->bn;
    // }
}
