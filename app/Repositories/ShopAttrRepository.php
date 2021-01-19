<?php
/**
 * @Filename ShopAttrRepository.php
 * Created by lanlnk
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-02-24
 * @Time: 15:15
 */

namespace ShopEM\Repositories;


use ShopEM\Models\ShopAttr;

class ShopAttrRepository
{

    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author huiho
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => '申请ID'],
            ['dataIndex' => 'shop_id', 'title' => '店铺ID'],
            ['dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['dataIndex' => 'created_at', 'title' => '申请时间'],
            ['dataIndex' => 'updated_at', 'title' => '审核时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author huiho
     * @return array
     *
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 搜索店铺配置数据
     *
     * @Author huiho
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $model = new ShopAttr();
        
        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('updated_at', 'desc')->paginate($request['per_page']);
 ;
        return $lists;
    }



}