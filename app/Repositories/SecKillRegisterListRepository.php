<?php
/**
 * @Filename SecKillRegisterListRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use ShopEM\Models\SecKillAppliesRegister;

class SecKillRegisterListRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'id' => ['field' => 'id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'seckill_ap_id' => ['field' => 'seckill_ap_id', 'operator' => '='],
        'verify_status' => ['field' => 'verify_status', 'operator' => '='],
        'valid_status' => ['field' => 'valid_status', 'operator' => '='],
        'created_at' => ['field' => 'created_at', 'operator' => '='],
        'created_start_at' => ['field' => 'created_at', 'operator' => '>='],
        'created_end_at' => ['field' => 'created_at', 'operator' => '<='],
        'is_delete' => ['field' => 'is_delete', 'operator' => '='],

    ];

    /**
     * 查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => 'id'],
            ['dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['dataIndex' => 'activity_name', 'title' => '活动名称'],
            ['dataIndex' => 'verify_status_text', 'title' => '审核状态'],
//            ['dataIndex' => 'valid_status_text', 'title' => '有效状态'],
            ['dataIndex' => 'refuse_reason', 'title' => '拒绝理由'],
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
     * 订单查询
     *
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function search($request)
    {

        $model = new SecKillAppliesRegister();
        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }

}