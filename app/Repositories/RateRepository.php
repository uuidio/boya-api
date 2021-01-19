<?php
/**
 * @Filename        RateRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Repositories;

use ShopEM\Models\RateTraderate;

class RateRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'result' => ['field' => 'result', 'operator' => '='],
        'content_not_null' => ['field' => 'content', 'operator' => '!='],
        'content_not_default' => ['field' => 'content', 'operator' => '!='],
        'is_pic' => ['field' => 'rate_pic', 'operator' => '!='],
        'is_reply'  => ['field' => 'is_reply', 'operator' => '!='],
        'goods_name'  => ['field' => 'goods_name', 'operator' => 'like'],
        'goods_id'  => ['field' => 'goods_id', 'operator' => '='],
        'rate_start_time'  => ['field' => 'created_at', 'operator' => '>='],
        'rate_end_time'  => ['field' => 'created_at', 'operator' => '<='],
        'tid'  => ['field' => 'tid', 'operator' => '='],
        'shop_id'  => ['field' => 'shop_id', 'operator' => '='],
        'appeal_again' => ['field' => 'appeal_again', 'operator' => '='],
        'appeal_status' => ['field' => 'appeal_status', 'operator' => '='],
        'is_appeal' => ['field' => 'appeal_status', 'operator' => '!='],
        'appeal_start_time'  => ['field' => 'appeal_time', 'operator' => '>='],
        'appeal_end_time'  => ['field' => 'appeal_time', 'operator' => '<='],
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
            ['dataIndex' => 'result_text', 'title' => '评价等级'],
            ['dataIndex' => 'content', 'title' => '评价内容'],
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'goods_id', 'title' => '商品ID'],
            ['dataIndex' => 'goods_name', 'title' => '商品名称'],
            ['dataIndex' => 'goods_price', 'title' => '商品价格'],
            ['dataIndex' => 'user_name', 'title' => '评价人'],
            ['dataIndex' => 'rate_pic', 'title' => '晒单图片'],
            ['dataIndex' => 'created_at', 'title' => '时间'],
        ];
    }

    /**
     * 平台后台查询字段
     *
     * @Author djw
     * @return array
     */
    public function platformLFields($is_show='')
    {
        return [
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'user_name', 'title' => '会员'],
            ['dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group'])],
            ['dataIndex' => 'shop_name', 'title' => '所属商家'],
            ['dataIndex' => 'goods_name', 'title' => '商品标题'],
            ['dataIndex' => 'reply_date', 'title' => '回复时间'],
            ['dataIndex' => 'appeal_status_text', 'title' => '申诉状态'],
            ['dataIndex' => 'appeal_date', 'title' => '申诉时间'],
            ['dataIndex' => 'created_at', 'title' => '创建时间'],
            ['dataIndex' => 'updated_at', 'title' => '最后修改时间'],
        ];
    }

    /**
     * 商家后台申诉列表的查询字段
     *
     * @Author djw
     * @return array
     */
    public function appealFields()
    {
        return [
            ['dataIndex' => 'result_text', 'title' => '评价等级'],
            ['dataIndex' => 'appeal_content', 'title' => '申诉内容'],
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'goods_id', 'title' => '商品ID'],
            ['dataIndex' => 'goods_name', 'title' => '商品名称'],
            ['dataIndex' => 'user_name', 'title' => '评价人'],
            ['dataIndex' => 'appeal_again_text', 'title' => '进度'],
            ['dataIndex' => 'appeal_status_text', 'title' => '申诉结果'],
            ['dataIndex' => 'reject_reason', 'title' => '驳回理由'],
//            ['dataIndex' => 'appeal_id', 'title' => '申诉ID'],
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
     * 平台后台表格列表显示字段
     *
     * @Author djw
     * @return array
     */
    public function platformListShowFields($is_show='')
    {
        return listFieldToShow($this->platformLFields($is_show));
    }

    /**
     * 商家后台申诉列表显示字段
     *
     * @Author djw
     * @return array
     */
    public function appealListShowFields()
    {
        return listFieldToShow($this->appealFields());
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
        $model = new RateTraderate();
        $model = filterModel($model, $this->filterables, $request);
        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }
}