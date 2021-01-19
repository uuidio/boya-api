<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ActivitiesTransmit extends Model
{
    //
    protected $guarded = [];

    protected $appends = ['is_show_text','article_cat_text'];

    /**
     * 追加文字说明
     * @Author hfh_wind
     * @return string
     */
    public function getIsShowTextAttribute()
    {
        return $this->is_show == 1 ? '是' : '否';
    }


    /**
     * 追加文章分类
     * @Author hfh_wind
     * @return string
     */
    public function getArticleCatTextAttribute()
    {
       $info= ArticleClass::where('id',$this->article_cat_id)->first();
        return !empty($info) ? $info['name'] : '';
    }
}
