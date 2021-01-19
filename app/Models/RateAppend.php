<?php
/**
 * @Filename        RateAppend.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class RateAppend extends Model
{
    protected $table = 'rate_append';
    protected $guarded = [];

    public function getAppendRatePicAttribute($appendRatePic)
    {
        return $appendRatePic ? explode(',',$appendRatePic) : $appendRatePic;
    }
}
