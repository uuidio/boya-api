<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class SellerRole extends Model
{

    /**
     * 不可批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * 追加自定义字段
     *
     * @var array
     */
    protected $appends = ['menus' ,'status_text'];

    /**
     * 角色多对多关联权限
     *
     * @author moocde <mo@mocode.cn>
     * @return type
     */
    public function roleToMenus()
    {
        return $this->belongsToMany(SellerRole::class, 'seller_role_menus', 'role_id', 'menu_name');
    }

    /**
     * 角色对应的权限
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasMenus()
    {
        return $this->hasMany(SellerRoleMenu::class, 'role_id');
    }

    public function setFrontendExtendAttribute($value)
    {
        $this->attributes['frontend_extend'] = serialize($value);
    }

    public function getFrontendExtendAttribute($value)
    {
        return unserialize($value);
    }

    /**
     * 获取角色下所有的权限ID
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function getMenusAttribute()
    {
        $menus = [];
        $hasMenus = $this->hasMenus;
        foreach ($hasMenus as $v) {
            $menus[] = $v->menu_name;
        }

        return $menus;
    }

    public function getStatusTextAttribute()
    {
        $text = [
            0 => '否',    // 否
            1 => '是',    // 是
        ];
        return isset($text[$this->status]) ? $text[$this->status] : '';
    }
}
