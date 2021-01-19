<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsHotkeywords extends Model
{
    protected $guarded = [];
    protected $appends = ['disabled_text'];

    public static $disabledMap = [
        0 => '启用',
        1 => '不启用',
    ];

    public function getDisabledTextAttribute()
    {
        return self::$disabledMap[$this->disabled];
    }

}
