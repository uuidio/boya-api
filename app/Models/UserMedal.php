<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class UserMedal extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['lv','title'];

    public function getLvAttribute()
    {
        $explode = explode('_', $this->medal_name);
        $lv = 1;
        if (isset($explode[1]) && $explode[1]) {
            $lv = str_replace('V', '', $explode[1]);
            $lv = $lv ? (int)$lv : 1;
        }
        return $lv;
    }

    public function getTitleAttribute()
    {
        $explode = explode('_', $this->medal_name);
        return $explode[0];
    }
}
