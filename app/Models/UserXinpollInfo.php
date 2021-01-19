<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class UserXinpollInfo extends Model
{
    protected $guarded = [];

    public function getRealnameAttribute($value)
    {
    	$len = mb_strlen($value,'utf-8');
        $str1 = mb_substr($value,0,1,'utf-8');
        if ($len > 2) {
            $str2 = str_repeat("*",$len-2); //替换字符数量
            $str3 = mb_substr($value,$len-1,1,'utf-8');
            $string = $str1.$str2.$str3;
        }else{
            $string = $str1.'*';
        }
        return $string;
    }

    public function getCardAttribute($value)
    {
        return substr_replace($value,"********",6,8);
    }
}
