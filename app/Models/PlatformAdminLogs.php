<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformAdminLogs extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['status_text'];


    /**
     * 追加状态说明
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getStatusTextAttribute()
    {
        $text = $this->status ? "成功" : "失败";

        return $text;
    }
}
