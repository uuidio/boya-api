<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GmPlatform extends Model
{
    protected $primaryKey = 'gm_id';
    protected $guarded = [];

    //状态转换
    public static $typeName = [
        'normal' => '普通自营',
        'self' => '集团自营',
    ];
    protected $appends = ['type_name','default_type_code'];


    public static function gmSelf()
    {
        $key = 'GM_PLATFORM_GM_SELF';
        $gm_id = Cache::remember($key, cacheExpires(), function () {
            $gm_id = self::where('type','self')
                        ->value('gm_id');
            return $gm_id;
        });
        if (empty($gm_id)) Cache::forget($key);
        return $gm_id;
    }

    //项目编号
    public static function getCorpCode($gm_id)
    {
        $key = 'gmplatform_get_corp_code_' . $gm_id ;
        $corp_code = Cache::remember($key, cacheExpires(), function () use ($gm_id) {
            $corp_code = self::where('gm_id',$gm_id)
                        ->value('corp_code');
            return $corp_code;
        });
        if (empty($corp_code)) Cache::forget($key);
        return $corp_code;
    }

    //删除-项目相关-缓存
    public static function delGmCache($gm_id)
    {
        $key = 'gmplatform_get_corp_code_' . $gm_id ;
        Cache::forget($key);
        $key = 'yitan_gm_platform_id_' . $gm_id ;
        Cache::forget($key);
        return true;
    }



    /**
     * 保存积分配置
     *
     * @Author djw
     * @param $value
     */
    public function setUseObtainPointAttribute($value)
    {
        $this->attributes['use_obtain_point'] = empty($value) ? '5|1' : implode($value, '|');
    }

    /**
     * 获取积分配置
     *
     * @Author djw
     * @return mixed
     */
    public function getUseObtainPointAttribute($value)
    {
        $data = explode('|', $value);
        $config = [
            'use_point' => $data[0],
            'obtain_point' => $data[1],
        ];
        $config['scale'] = $config['obtain_point']/$config['use_point'];
        return $config;
    }


    public function getTypeNameAttribute()
    {
        return self::$typeName[$this->type]??'普通自营';
    }

    
    public function getDefaultTypeCodeAttribute()
    {
        $card_code = YiTianUserCard::where('level',1)->where('gm_id',$this->gm_id)->value('card_code');
    	return empty($card_code) ? null : $card_code ;
    }

}
