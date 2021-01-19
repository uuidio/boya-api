<?php
/**
 * @Filename        ListsTree.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Traits;

trait ListsTree
{
    protected $formatTree;

    /**
     * 把返回的数据集转换成Tree
     *
     * @author moocde <mo@mocode.cn>
     * @param type $list 要转换的数据集
     * @param type $pk
     * @param type $parent_id
     * @param type $child
     * @param type $root
     * @return type
     */
    protected function list_to_tree($list, $pk = 'id', $parent_id = 'parent_id', $child = '_child', $root = 0)
    {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = &$list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$parent_id];
                if ($root == $parentId) {
                    $tree[] = &$list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
//                        $parent['children'][] = $data[$pk];
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * 生成树形商品分类
     *
     * @Author moocde <mo@mocode.cn>
     * @param $list
     * @return array
     */
    protected function goodsClassToTree($list) {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $tmp = [];
                $tmp['value'] = strval($data['id']);
                $tmp['label'] = $data['gc_name'];
                $tmp['parent_id'] = $data['parent_id'];
                $tmp['type_id'] = $data['type_id'];
                $tmp['type_name'] = $data['type_name'];
                $tmp['class_icon'] = $data['class_icon'];
                $tmp['class_level'] = $data['class_level']; //添加层级字段 djw
                $refer[$data['id']] = $tmp;
            }
            foreach ($refer as $key => $data) {
                // 判断是否存在parent
                $parentId = $data['parent_id'];
                if ($parentId == 0) {
                    $tree[] = &$refer[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
                        $parent['children'][] = &$refer[$key];
//                        $level = 'lv'.$data['class_level']; //children改成子级的层级 djw
//                        $parent[$level][] = &$refer[$key];
                    }
                }
            }
        }

        return $tree;
    }


    /**
     * 生成树形店铺分类
     *
     * @Author moocde <mo@mocode.cn>
     * @param $list
     * @return array
     */
    protected function shopCatsToTree($list) {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $tmp = [];
                $tmp['value'] = strval($data['id']);
                $tmp['label'] = $data['cat_name'];
                $tmp['parent_id'] = $data['parent_id'];
                //$tmp['class_icon'] = $data['class_icon'];
                $tmp['class_level'] = $data['level']; //添加层级字段 djw
                $refer[$data['id']] = $tmp;
            }
            foreach ($refer as $key => $data) {
                // 判断是否存在parent
                $parentId = $data['parent_id'];
                if ($parentId == 0) {
                    $tree[] = &$refer[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
                        $parent['children'][] = &$refer[$key];
//                        $level = 'lv'.$data['class_level']; //children改成子级的层级 djw
//                        $parent[$level][] = &$refer[$key];
                    }
                }
            }
        }

        return $tree;
    }

    /**
     * 将树子节点加层级成列表
     *
     * @author moocde <mo@mocode.cn>
     * @param type $tree
     * @param type $level
     */
    protected function _toFormatTree($tree, $level = 1)
    {
        foreach ($tree as $key => $value) {
            $temp = $value;
            if (isset($temp['_child'])) {
                $temp['_child'] = true;
                $temp['level'] = $level;
            } else {
                $temp['_child'] = false;
                $temp['level'] = $level;
            }
            array_push($this->formatTree, $temp);
            if (isset($value['_child'])) {
                $this->_toFormatTree($value['_child'], ($level + 1));
            }
        }
    }

    protected function cat_empty_deal($cat, $next_parentid, $parent_id = 'parent_id', $empty = '   ')
    {
        $str = "";
        if ($cat[$parent_id]) {
            for ($i = 2; $i < $cat['level']; $i++) {
                $str .= $empty . " │ ";
            }
            if ($cat[$parent_id] != $next_parentid && !$cat['_child']) {
                $str .= $empty . " └─ ";
            } else {
                $str .= $empty . " ├─ ";
            }
        }
        return $str;
    }

    public function toFormatTree($list, $title = 'title', $pk = 'id', $parent_id = 'parent_id', $root = 0)
    {
        if (empty($list)) {
            return false;
        }
        $list = $this->list_to_tree($list, $pk, $parent_id, '_child', $root);
        $this->formatTree = array();
        $this->_toFormatTree($list);
        $data = [];
        foreach ($this->formatTree as $key => $value) {
            $index = ($key + 1);
            $next_parentid = isset($this->formatTree[$index][$parent_id]) ? $this->formatTree[$index][$parent_id] : '';
//            $value['level_show'] = $this->cat_empty_deal($value, $next_parentid);
            $value[$title] = $this->cat_empty_deal($value, $next_parentid) . $value[$title];
            $data[] = $value;
        }
        return $data;
    }


    /**
     * 生成树形文章分类
     *
     * @Author djw
     * @param $list
     * @return array
     */
    protected function platformArticleClassToTree($list) {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $tmp = [];
                $tmp['value'] = strval($data['id']);
                $tmp['label'] = $data['name'];
                $tmp['parent_id'] = $data['parent_id'];
                $tmp['class_level'] = $data['parent_id'] == 0 ? 1 : 2;
                $refer[$data['id']] = $tmp;
            }
            foreach ($refer as $key => $data) {
                // 判断是否存在parent
                $parentId = $data['parent_id'];
                if ($parentId == 0) {
                    $tree[] = &$refer[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
                        $parent['children'][] = &$refer[$key];
                    }
                }
            }
        }

        return $tree;
    }
}