<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LiveTagImage extends Model
{
    protected $table = 'live_tags_image';
    protected $guarded = [];


    /**
     * 输出图片完整url
     *
     * @Author moocde <mo@mocode.cn>
     * @param $value
     * @return string
     */
    public function getImgAttribute($value)
    {
        if ($this->filesystem === 'oss') {
            return config('filesystems.disks.oss.domain') . $value;
        }

        return Storage::disk($this->filesystem)->url($value);
    }
}
