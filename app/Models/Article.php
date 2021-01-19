<?php
/**
 * @Filename        Article
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $guarded = [];
    protected $appends = ['gm_name','activity_name','type_id'];

    public function getTypeIdAttribute()
    {
        return $this->attributes['type'];
    }


    // 文章类型返回文字
    public function getTypeAttribute(){
    	
    	if($this->attributes['type']==0){
    		return '文本';
    	}else{
    		return '活动';
    	}
    }

    public function getSubtitleAttribute($value)
    {
        return empty($value) ? '' : $value;
    }

    // 追加所属项目名称
    public function getGmNameAttribute(){
        $gm = GmPlatform::find($this->gm_id);
        return $gm['platform_name']??'';
    }

    // 审核状态返回文字
    public function getVerifyStatusAttribute(){

        switch ($this->attributes['verify_status']) {
            case '0':
                $status = '待审核';
                break;
            case '1':
                $status = '审核通过';
                break;
            default:
                $status = '审核不通过';
                break;
        }
        return $status;
    }

    // 获取自定义活动名称
    public function getActivityNameAttribute(){
        $activity =  CustomActivityConfig::find($this->activity_id);
        return $activity['title']??'';
    }

     // public function getCatNameAttribute()
    // {
    //     return ArticleClass::find($this->cat_id)['name'] ?: '已被删除';
    // }
}
