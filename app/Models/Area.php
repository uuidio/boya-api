<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    //

    protected $table = 'area';

    /**
     * 通过微信授权返回的省份名称获取省份ID
     *
     * @Author mocode <mo@mocode.cn>
     * @param $name
     * @return mixed
     */
    public static function checkArea($areaId)
    {
        $info = self::where('id', '=', $areaId)->first();
        if (empty($info)) {
            $city = UserAddress::where('area_code',$areaId)->value('city');
            if (!empty($city)) {
                $info = self::where('name', '=', $city)->first();
            }
        }
        $return = empty($info) ? ['node'=>110000,'id'=>110100] : $info;

        return $return;
    }
}
