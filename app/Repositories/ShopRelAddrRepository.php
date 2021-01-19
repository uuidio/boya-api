<?php
/**
 * @Filename ShopAttrRepository.php
 * Created by lanlnk
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-03-25
 * @Time: 17:57
 */

namespace ShopEM\Repositories;


use ShopEM\Models\ShopRelAddr;

class ShopRelAddrRepository
{

    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'tel' => ['field' => 'tel', 'operator' => '='],
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
            ['dataIndex' => 'name', 'title' => '收货人姓名'],
            ['dataIndex' => 'tel', 'title' => '收货人手机号'],
            ['dataIndex' => 'address', 'title' => '详细地址'],
            ['dataIndex' => 'is_default', 'title' => '是否默认地址'],
            ['dataIndex' => 'created_at', 'title' => '申请时间'],
            ['dataIndex' => 'updated_at', 'title' => '修改时间'],
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
        $model = new ShopRelAddr();
        
        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('updated_at', 'desc')->paginate($request['per_page']);
 ;
        return $lists;
    }



}