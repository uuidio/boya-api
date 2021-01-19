<?php

/**
 * CouponOffRepository.php
 * @Author: nlx
 * @Date:   2020-04-18 15:46:30
 */
namespace ShopEM\Repositories;

use Carbon\Carbon;
use ShopEM\Models\CouponWriteOff;

class CouponOffRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'bn' => ['field' => 'bn', 'operator' => 'like'],
        'trade_no' => ['field' => 'trade_no', 'operator' => '='],
        'user_mobile' => ['field' => 'user_mobile', 'operator' => '='],
        'source_shop_id' => ['field' => 'source_shop_id', 'operator' => '='],
        'created_start'  => ['field' => 'created_at', 'operator' => '>='],
        'created_end'  => ['field' => 'created_at', 'operator' => '<='],
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
            ['dataIndex' => 'id', 'title' => 'id'],
            ['dataIndex' => 'source_type', 'title' => '发行渠道'],
            ['dataIndex' => 'coupon_id', 'title' => '优惠券id'],
            ['dataIndex' => 'coupon_name', 'title' => '优惠券名称'],
            ['dataIndex' => 'bn', 'title' => '核销码'],
            ['dataIndex' => 'user_mobile', 'title' => '客户手机号'],
            ['dataIndex' => 'trade_no', 'title' => '小票号'],
            ['dataIndex' => 'voucher', 'title' => '凭证'],
            ['dataIndex' => 'remark', 'title' => '备注'],
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
     * 获取列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @param int $page_count
     * @return mixed
     */
    public function listItems($request, $page_count = 0)
    {
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;
        $couponModel = new CouponWriteOff();
        $couponModel = filterModel($couponModel, $this->filterables, $request);

        $lists = $couponModel->orderBy('id', 'desc')->paginate($page_count);

        return $lists;
    }
}