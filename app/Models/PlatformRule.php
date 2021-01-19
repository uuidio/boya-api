<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformRule extends Model
{
    public $guarded = [];
    protected $appends = ['gm_name','type_name'];

     // 追加所属项目名称
    public function getGmNameAttribute(){
        $gm = GmPlatform::find($this->gm_id);
        return $gm['platform_name']??'';
    }


    // 追加分类名称
    public function getTypeNameAttribute(){
        $type = [
        	0 =>'积分',
            1 =>'分销'
        ];
        return $type[$this->type]??'';
    }
}
