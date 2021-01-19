<?php
/**
 * @Filename        ArticleService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */
namespace ShopEM\Services;

use ShopEM\Models\Article;
use ShopEM\Models\ArticleClass;
use ShopEM\Models\ShopArticleClass;
use ShopEM\Services\Marketing\Coupon;
class ArticleService
{
    //校验分类数据
    public static function classesCheckData($data, $id = false)
    {
        if ($data['parent_id'] != 0) {
            $parent = ArticleClass::find($data['parent_id']);
            if (empty($parent)) {
                throw new \Exception('所选父类不存在!');
            }
        }
        //更新情况下
        if ($id) {
            $class = ArticleClass::find($id);
            if (empty($class)) {
                throw new \Exception('数据不存在');
            }
            $hasChild = ArticleClass::where('parent_id', $id)->count();
            if ($data['parent_id'] != 0 && $hasChild) {
                throw new \Exception('存在子类，所以无法修改成子类!');
            }
            if ($id == $data['parent_id']) {
                throw new \Exception('不能选择自己作为父类!');
            }
        }
        return true;
    }
    //校验店铺文章分类数据
    public static function shopClassesCheckData($data, $id = false)
    {
        if ($data['parent_id'] != 0) {
            $parent = ShopArticleClass::where('id', $data['parent_id'])->where('shop_id', $data['shop_id'])->first();
            if (empty($parent)) {
                throw new \Exception('所选父类不存在!');
            }
        }
        //更新情况下
        if ($id) {
            $class = ShopArticleClass::where('id', $id)->where('shop_id', $data['shop_id'])->first();
            if (empty($class)) {
                throw new \Exception('数据不存在');
            }
            $hasChild = ShopArticleClass::where('parent_id', $id)->where('shop_id', $data['shop_id'])->count();
            if ($data['parent_id'] != 0 && $hasChild) {
                throw new \Exception('存在子类，所以无法修改成子类!');
            }
            if ($id == $data['parent_id']) {
                throw new \Exception('不能选择自己作为父类!');
            }
        }
        return true;
    }
}