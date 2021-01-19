<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsTemplate extends Model
{
    //
    protected $guarded = [];


    //状态转换
    public static $valuation = [
        1 => '按重量',
        2 => '按件数',
        3 => '按金额',
        4 => '按体积',
    ];
    protected $appends = ['valuation_text', 'is_free_text', 'status_text'];

    /**
     * 物流类型说明
     * @Author hfh_wind
     * @return mixed
     */
    public function getValuationTextAttribute()
    {
        return self::$valuation[$this->valuation];
    }


    /**
     * 免邮说明
     * @Author hfh_wind
     * @return mixed
     */
    public function getIsFreeTextAttribute()
    {
        if ($this->is_free == '1') {
            $text = '包邮';
        } else {
            $text = '自定义运费';
        }
        return $text;
    }


    /**
     * 物流类型说明
     * @Author hfh_wind
     * @return mixed
     */
    public function getStatusTextAttribute()
    {
        if ($this->status == '1') {
            $text = '启用';
        } else {
            $text = '关闭';
        }
        return $text;
    }
}
