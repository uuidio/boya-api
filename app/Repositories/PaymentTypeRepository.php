<?php
/**
 * @Filename PaymentTypeRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          zhp
 */
namespace ShopEM\Repositories;

use ShopEM\Models\PanymentType;

class PaymentTypeRepository
{
    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [ ];

    /**
     * 查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'pay_gm_name', 'title' => '项目名称'],
            ['dataIndex' => 'pay_gm_id', 'title' => '项目ID'],
            ['dataIndex' => 'pay_type', 'title' => '支付类型名'],
            ['dataIndex' => 'pay_type_code', 'title' => '类型代码'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author hfh_wind
     * @return array
     *
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 支付类型代码详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        return PanymentType::find($id);
    }
    /**
     * 搜索消息
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return mixed
     */
    public function search($request)
    {
        $pay = new PanymentType();

        $request['per_page'] = isset($request['per_page']) && $request['per_page'] ? $request['per_page'] : config('app.per_page');



        $pay = filterModel($pay, $this->filterables, $request);
        $lists = $pay->orderBy('id', 'desc')->paginate($request['per_page']);



        return $lists;
    }
}