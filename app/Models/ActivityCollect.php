<?php

namespace ShopEM\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ActivityCollect extends Model
{
	protected $guarded = [];
    //
    
    const CLICK = 'CLICK';
    const VISIT = 'VISIT';

    //动作形式
    public static $actionClassMap = [
        self::CLICK     => '点击',    
        self::VISIT   	=> '访问',   
    ];

    /**
     * [newSave 新保存数据]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public static function newSave($data)
    {
    	$ip = request()->getClientIp();
    	$encryption = isset($data['url']) ? md5($ip.$data['url']) : md5($ip);
        $cache_key = 'activity_collect_ip_'.$encryption;
        $cache_value = Cache::get($cache_key,'default');
        if ($cache_value == $encryption) {
        	return true;
        }
        Cache::put($cache_key, $encryption, Carbon::now()->addSeconds(5));

        $saveData = array(
        	'action'	=> strtoupper($data['action']),
        	'url'		=> $data['url'],
        	'goods_id'	=> $data['goods_id']??0,
        	'ip'		=> $ip,
        	'time'		=> time(),
        );
        DB::transaction(function () use ($saveData) {
        	self::create($saveData);
		});
    }
}
