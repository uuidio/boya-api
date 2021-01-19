<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class UserPassword extends Model
{
    protected $fillable = ['user_id', 'pay_password'];


    const ERROR_NUM = 3; //允许错误次数
 
    const THAW_TIME = '1:00'; //解冻时间
    //是否设置支付密码
    public static function hasPayPass($user_id)
    {
    	$has = self::where('user_id',$user_id)->select('pay_password')->first();
    	if (!$has || empty($has->pay_password)) {
    		if(!$has) self::create(['user_id'=>$user_id]);
    		return false;
    	}
    	return true;
    }

    //是否可以使用
    public static function usability($user_id)
    {
    	$has = self::where('user_id',$user_id)->first();
        if (!$has) return false;
        
    	$error_num = self::ERROR_NUM;
    	if ($has->status == 1 && $has->error_num < $error_num) {
    		return true;
    	}
    	if ($has->error_num >= $error_num) 
    	{
    		$has->status = 2;
    		$has->save();
    	}
    	
    }
    
    //错误次数+
    public static function payPassError($user_id)
    {
    	self::where('user_id',$user_id)->increment('error_num');
        $error_num = self::where('user_id',$user_id)->value('error_num');
        $num = self::ERROR_NUM - $error_num;
        return ($num < 0) ? 0 : $num;
    }
}
