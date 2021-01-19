<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsDlycorp extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['is_show_text'];

    public function getIsShowTextAttribute()
    {
        return $this->is_show == 1 ? '是' : '否';
    }
}
