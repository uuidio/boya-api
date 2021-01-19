<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class TradeActivityDetail extends Model
{
    protected $guarded = [];

    /**
     * *****  rule   ****
     */

    public function getRuleAttribute($value)
    {
        $data = explode(';', $value);
        foreach ($data as $k => $v) {
            $x = explode('-', $v);
            $i['condition'] = $x[0];
            $i['num'] = $x[1];
            $out[] = $i;
        }
        return $out;
    }

    public function setRuleAttribute($value)
    {
        foreach ($value as $k => $v) {
            if (is_object($v)) {
                $v = get_object_vars($v);
            }
            $rules[] = $v['condition'].'-'.$v['num'];
        }
        $this->attributes['rule'] = implode(';', $rules);
    }
}
