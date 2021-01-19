<?php
/**
 * @Filename        SellerRoleRepository.phptory.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Repositories;

use ShopEM\Models\SellerRole;

class SellerRoleRepository
{
    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterAbles = [
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
    ];

    /**
     * 前台列表显示字段
     *
     * @var array
     */
    public $listFields = [
        ['dataIndex' => 'id', 'title' => 'ID'],
        ['dataIndex' => 'name', 'title' => '角色名称'],
        ['dataIndex' => 'status_text', 'title' => '是否启用'],
    ];

    /**
     * 列表搜索
     *
     * @Author moocde <mo@mocode.cn>
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $page_size = empty($request['page_size']) ? config('app.page_size') : $request['page_size'];

        $model = new SellerRole();
        $model = filterModel($model, $this->filterAbles, $request);
        $lists = $model->orderBy('id', 'desc')->paginate($page_size);

        return $lists;
    }
}