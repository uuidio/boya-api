<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ActivityBargains extends Model
{
    //
    protected $guarded = [];

    protected $appends = ['ap_name', 'type_text', 'is_sold_text', 'activity_stock'];


    /**
     * 追加活动名称
     * @Author hfh_wind
     * @return string
     */
    public function getApNameAttribute()
    {
        if ($this->type == '2') {
            $ap_name = BargainirgApplies::select('activity_name')->find($this->ap_id);
        }

        return $ap_name['activity_name']??'';
    }


    /**
     * 追加类型
     * @Author hfh_wind
     * @return string
     */
    public function getTypeTextAttribute()
    {
        $ap_name = [
            0 => '平台票劵',
            1 => '店铺商品',
            2 => '平台活动采购',
        ];
        return $ap_name[$this->type]??'无';
    }


    /**
     * 追加库存
     * @Author hfh_wind
     * @return string
     */
    public function getActivityStockAttribute()
    {
        $res = ActivityBargainsDetails::where('bargain_id', $this->id)->select(DB::raw('SUM(bargain_stock) as bargain_stock,SUM(stock_log) as stock_log'))->first();

        $sold_count_arr = ActivityBargainsDetails::where('bargain_id', $this->id)->select('id')->get();

        $sold_count=0;
        foreach($sold_count_arr  as  $value){
            $sold_count +=$value['sold_count'];
        }

        $return['bargain_stock'] = $res['bargain_stock']??0;
        $return['stock_log'] = $res['stock_log']??0;
        $return['sold_count'] = $sold_count;
        return $return;
    }


    /**
     * 追加显示说明
     * @Author hfh_wind
     * @return string
     */
    public function getIsSoldTextAttribute()
    {
        $text = '';
        switch ($this->is_sold) {
            case '0':
                $text = '待审核';
                break;
            case '1':
                $text = '上架';
                break;
            case '2':
                $text = '下架';
                break;
            case '3':
                $text = '强制关闭';
                break;
            case '4':
                $text = '驳回';
                break;
        }

        return $text;
    }


}
