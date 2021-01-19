<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LotteryRecord extends Model
{
    protected $guarded = [''];

    protected $appends = ['status_name', 'grant_status_name', 'user_account_name','activities_type_name','is_show_text'];

    public function getStatusNameAttribute()
    {
        $name = [
            0 => '未中奖',
            1 => '中奖',
        ];
        return isset($this->status) ? $name[$this->status] : '';
    }

    public function getGrantStatusNameAttribute()
    {
        $name = [
            0 => '未发放',
            1 => '已发放',
            2 => '已补发',
        ];

        return isset($this->grant_status) ? $name[$this->grant_status] : '';
    }

    public function getUserAccountNameAttribute()
    {
        $mobile = DB::table('user_accounts')->where('id', $this->user_account_id)->value('mobile');

        return $mobile ?: '匿名';
    }

    /**
     * 追加活动类型
     *
     * @Author RJie
     * @return mixed|string
     */
    public function getActivitiesTypeNameAttribute()
    {
        $txt = [
            0 => '线上转盘',
            1 => '线下多转盘',
        ];

        return isset($this->activities_type) ? $txt[$this->activities_type] : '';
    }

    /**
     * 展示状态
     *
     * @Author RJie
     * @return mixed|string
     */
    public function getIsShowTextAttribute()
    {
        $text = [
            0 => '隐藏',
            1 => '显示',
        ];

        return isset($this->is_show) ? $text[$this->is_show]:'';
    }
}
