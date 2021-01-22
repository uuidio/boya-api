<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AppVersions extends Model
{
    protected $table = 'app_versions';
    protected $guarded = [];


    /**
     * 输出图片完整url
     *
     * @Author linzhe
     * @param $value
     * @return string
     */
//    public function getUrlAttribute($value)
//    {
//        if ($this->filesystem === 'oss') {
//            return config('filesystems.disks.oss.domain') . $value;
//        }
//
//        return Storage::disk($this->filesystem)->url($value);
//    }
}


