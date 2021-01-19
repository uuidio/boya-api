<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ShopType extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['type_status'];


    public function getTypeStatusAttribute()
    {
        if ($this->status == 1) {
            return '启用';
        }else{
            return '关闭';
        }
    }
}
