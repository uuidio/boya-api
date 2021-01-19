<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ActivitiesRewardGoods extends Model
{
    //

    protected $guarded = [];
    protected $appends = ['is_use_text'];



    /**
     * 是否启用
     * @Author hfh_wind
     * @return string
     */
    public function getIsUseTextAttribute()
    {
        return $this->is_use == 1 ? '启用' : '停用';
    }
}
