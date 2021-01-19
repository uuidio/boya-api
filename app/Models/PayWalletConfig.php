<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class PayWalletConfig extends Model
{
    //
    protected $guarded = [];

    protected $primaryKey = 'gm_id';

    //限制状态 是否开启
    public static function limitStatus()
    {
    	return self::where('mode','limit')->where('status',1)->exists();
    }

    //获取当前项目下限制的店铺
    public function getLimitShop($gm_id='')
    {
        if ( !self::limitStatus())  return 'all'; //全部店铺
        $filter = [
            'mode' => 'limit',
            'status' => '1',
        ];
        if (!empty($gm_id)) $filter['gm_id'] = $gm_id;
        $limit_shop = self::where($filter)->pluck('limit_shop');
        if (empty($limit_shop)) return 'all'; //项目全部店铺

        $limit_shop_arr[] = -1;
        foreach ($limit_shop as $value) 
        {
            $limit_shop_arr[] = $value;
        }
        $limit_shop_str = implode(',', (array)$limit_shop_arr);

        return array_unique(explode(',', $limit_shop_str));
    }


}
