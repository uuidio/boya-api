<?php
/**
 * @Filename        ShopArticle
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ShopArticle extends Model
{
    protected $guarded = [];
    protected $appends = ['cat_name'];

    public function getCatNameAttribute()
    {
        return ArticleClass::find($this->cat_id)['name'] ?: '已被删除';
    }

}
