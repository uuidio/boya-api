<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class SetPartnersLog extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['before_role_text', 'now_role_text'];


    /**
     * 追加角色
     * @Author hfh_wind
     * @return int
     */
    public function getBeforeRoleTextAttribute()
    {
        $text = '';
        switch ($this->old_role) {
            case 0;
                $text = "普通会员";
                break;
            case 1;
                $text = "推广员";
                break;
            case 2;
                $text = "小店";
                break;
            case 3;
                $text = "分销商";
                break;
            case 4;
                $text = "经销商";
                break;
        }

        return $text;
    }


    /**
     * 追加角色
     * @Author hfh_wind
     * @return int
     */
    public function getNowRoleTextAttribute()
    {
        $text = '';
        switch ($this->change_role) {
            case 0;
                $text = "普通会员";
                break;
            case 1;
                $text = "推广员";
                break;
            case 2;
                $text = "小店";
                break;
            case 3;
                $text = "分销商";
                break;
            case 4;
                $text = "经销商";
                break;
        }

        return $text;
    }
}
