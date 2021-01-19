<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class TradeStockReturnLog extends Model
{
    protected $guarded = [];
    protected $appends = ['status_text', 'gm_name'];

    public function getStatusTextAttribute()
    {
        $map = [1=>'成功',2=>'失败'];
        if (isset($this->status)) {
            return $map[$this->status];
        } else {
            return '';
        }
    }

    public function getGmNameAttribute()
    {
        $name = '';
        if (isset($this->gm_id)) {
            $gm = GmPlatform::where('gm_id',$this->gm_id)->select('platform_name')->first();
            $name = $gm->platform_name;
        }
        return $name;
    }
}
