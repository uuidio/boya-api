<?php
/**
 * @Filename        ArticleClass
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AlbumPic extends Model
{
    //

    protected $guarded = [];

    /**
     * 输出图片完整url
     *
     * @Author moocde <mo@mocode.cn>
     * @param $value
     * @return string
     */
    public function getPicUrlAttribute($value)
    {
        if ($this->filesystem === 'oss') {
            return config('filesystems.disks.oss.domain') . $value;
        }

        return Storage::disk($this->filesystem)->url($value);
    }

}
