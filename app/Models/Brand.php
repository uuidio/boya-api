<?php
/**
 * @Filename        Brand.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $guarded = [];

    protected $appends = ['initial_name'];

    /**
     * 返回带英文首字母的品牌名称
     *
     * @Author moocde <mo@mocode.cn>
     * @return string
     */
    public function getInitialNameAttribute()
    {
        return $this->brand_initial . ' | ' . $this->brand_name;
    }
}
