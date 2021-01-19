<?php
/**
 * @Filename IntegralBySelfRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          Huiho
 */

namespace ShopEM\Repositories;

use ShopEM\Models\IntegralBySelf;

class IntegralBySelfRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'created_start'  => ['field' => 'created_at', 'operator' => '>='],
        'created_end'  => ['field' => 'created_at', 'operator' => '<='],
        'user_id'  => ['field' => 'user_id', 'operator' => '='],
        'mobile'    => ['field' => 'mobile', 'operator' => '='],
        'status'        => ['field' => 'status', 'operator' => '='],
        'gm_id'        => ['field' => 'gm_id', 'operator' => '='],
    ];


    //状态转换
    private $processing_status = [
        0 => 'ready',      //待处理
        1 => 'success',    //成功
        2 => 'reject',     //驳回
    ];

    /**
     * 查询字段
     *
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => '编号'],
            ['dataIndex' => 'uploaded_data', 'title' => '缩略图'],
            ['dataIndex' => 'login_account', 'title' => '姓名'],
            ['dataIndex' => 'mobile', 'title' => '电话'],
            ['dataIndex' => 'grade_name_text', 'title' => '会员等级'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],
            ['dataIndex' => 'status_text', 'title' => '状态'],
            ['dataIndex' => 'push_crm_text', 'title' => 'CRM推送状态'],
            ['dataIndex' => 'crm_msg', 'title' => 'CRM返回信息'],
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
     * 订单查询
     *
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        //如果status传的是数字的话，需要转换成对应的状态值
        if (isset($request['status']))
        {
            if (isset($this->processing_status[$request['status']])) {
                $request['status'] = $this->processing_status[$request['status']];
            }
        }

        $model = new IntegralBySelf();
        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }

}