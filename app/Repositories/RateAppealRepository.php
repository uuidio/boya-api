<?php
/**
 * @Filename        RateAppealRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Repositories;

use ShopEM\Models\RateAppeal;

class RateAppealRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'is_wait' => ['field' => 'status', 'operator' => '='],
        'is_not_wait' => ['field' => 'status', 'operator' => '!='],
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => '申诉ID'],
            ['dataIndex' => 'status_text', 'title' => '申诉状态'],
            ['dataIndex' => 'appeal_type_text', 'title' => '申诉类型	'],
            ['dataIndex' => 'reject_reason', 'title' => '驳回理由'],
            ['dataIndex' => 'goods_name', 'title' => '商品名称'],
            ['dataIndex' => 'content', 'title' => '申述内容'],
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'created_at', 'title' => '申诉时间'],
            ['dataIndex' => 'updated_at', 'title' => '最后修改时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 订单查询
     *
     * @Author moocde <mo@mocode.cn>
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $model = new RateAppeal();
        $model = filterModel($model, $this->filterables, $request);
        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }
}