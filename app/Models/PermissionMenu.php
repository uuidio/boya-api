<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionMenu extends Model
{
    /**
     * 不可批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * 该模型是否被自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;
}
