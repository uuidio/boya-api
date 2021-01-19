<?php
/**
 * @Filename        CouponRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */
namespace ShopEM\Repositories;

use Carbon\Carbon;
use ShopEM\Models\Coupon;

class CouponRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'channel' => ['field' => 'channel', 'operator' => '='],
        'name' => ['field' => 'name', 'operator' => '='],
        'type' => ['field' => 'type', 'operator' => '='],
        'scenes' => ['field' => 'scenes', 'operator' => '='],
        'get_star'   => ['field' => 'get_star', 'operator' => '='],
        'get_end'   => ['field' => 'get_end', 'operator' => '='],
        'start_at'   => ['field' => 'start_at', 'operator' => '='],
        'end_at'   => ['field' => 'end_at', 'operator' => '='],
        'status'   => ['field' => 'status', 'operator' => '='],
        'is_hand_push'   => ['field' => 'is_hand_push', 'operator' => '='],
        'is_distribute'   => ['field' => 'is_distribute', 'operator' => '='],
        'valid_at'   => ['field' => 'get_end', 'operator' => '>'],
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
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'name', 'title' => '优惠券名称'],
            ['dataIndex' => 'issue_num', 'title' => '发行数量'],
            ['dataIndex' => 'rec_num', 'title' => '已领数量'],
            ['dataIndex' => 'type_text', 'title' => '使用类型'],
            ['dataIndex' => 'scenes_text', 'title' => '使用场景'],
            ['dataIndex' => 'get_time', 'title' => '领取时间'],
            ['dataIndex' => 'use_time', 'title' => '生效时间'],
            ['dataIndex' => 'status_text', 'title' => '审核状态'],
            ['dataIndex' => 'reason', 'title' => '驳回原因'],
        ];
    }

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function PlatformListFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'name', 'title' => '优惠券名称'],
            ['dataIndex' => 'issue_num', 'title' => '发行数量'],
            ['dataIndex' => 'rec_num', 'title' => '已领数量'],
            ['dataIndex' => 'used_num', 'title' => '已核销数量'],
            ['dataIndex' => 'get_time', 'title' => '领取时间'],
            ['dataIndex' => 'use_time', 'title' => '生效时间'],
            ['dataIndex' => 'type_text', 'title' => '使用类型'],
            ['dataIndex' => 'scenes_text', 'title' => '使用场景'],
            ['dataIndex' => 'status_text', 'title' => '审核状态'],
            ['dataIndex' => 'reason', 'title' => '驳回原因'],
            ['dataIndex' => 'is_hand_push_text', 'title' => '是否手动推送'],
            ['dataIndex' => 'is_distribute_text', 'title' => '是否上架'],
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
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function PlatformListShowFields()
    {
        return listFieldToShow($this->PlatformListFields());
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
        $couponModel = new Coupon();
        $couponModel = filterModel($couponModel, $this->filterables, $request);

        //判断是商家券列表还是平台券列表
        if (isset($request['is_shop_coupon']) && $request['is_shop_coupon'] == 'true') {
            $couponModel = $couponModel->where('shop_id', '!=', 0);
        } elseif (isset($request['is_shop_coupon']) && $request['is_shop_coupon'] == 'false') {
            $couponModel = $couponModel->where('shop_id', '=', 0);
        }
        if (isset($request['is_hand_push']) && $request['is_hand_push'] == 0) {
            $couponModel = $couponModel->where('is_hand_push', '=', 0);
        }
        $lists = $couponModel->orderBy('id', 'desc')->paginate($page_count);

        return $lists;
    }

    /**
     * 获取抽奖优惠卷
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @param int $page_count
     * @return mixed
     */
    public function luckDrawItems($request, $page_count = 0)
    {
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;
        $couponModel = new Coupon();
        $couponModel = filterModel($couponModel, $this->filterables, $request);

        //判断是否抽奖列表
        if (isset($request['search_type']) && $request['search_type'] == 'luckDraw')
        {
            $couponModel = $couponModel->whereColumn('issue_num', '>', 'rec_num');
        }

        $lists = $couponModel->orderBy('id', 'desc')->paginate($page_count);

        return $lists;
    }
}
