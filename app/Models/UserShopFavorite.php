<?php
/**
 * @Filename        UserShopFavorite.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class UserShopFavorite extends Model
{
    protected $table = 'user_shop_favorite';
    protected $guarded = [];
    protected $appends = ['shop_type'];

    public function getShopTypeAttribute()
    {
        $shop = Shop::find($this->shop_id);
        return isset($shop->shop_type) ? $shop->shop_type : '';
    }
}
