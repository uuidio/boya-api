<?php
/**
 * @Filename ActivitiesRewardsSendLogsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;


use ShopEM\Models\ActivitiesRewardsSendLogs;

class ActivitiesRewardsSendLogsRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id'                   => ['field' => 'id', 'operator' => '='],
        'activities_id'        => ['field' => 'activities_id', 'operator' => '='],
        'activities_reward_id' => ['field' => 'activities_reward_id', 'operator' => '='],
        'user_id'              => ['field' => 'user_id', 'operator' => '='],
        'type'                 => ['field' => 'type', 'operator' => '='],
        'reward_type'          => ['field' => 'type', 'operator' => '<>'],
        'is_redeem'            => ['field' => 'is_redeem', 'operator' => '='],
        'gm_id'            => ['field' => 'gm_id', 'operator' => '='],
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
            ['dataIndex' => 'id', 'title' => '活动id'],
            ['dataIndex' => 'user_name', 'title' => '会员'],
            ['dataIndex' => 'activities_text', 'title' => '活动名称'],
            ['dataIndex' => 'lottery_info', 'title' => '奖项名称'],
            ['dataIndex' => 'activities_reward_info.goods_name', 'title' => '奖品名称'],
            ['dataIndex' => 'activities_reward_info.goods_image', 'title' => '奖品图片'],
            ['dataIndex' => 'pick_type_name', 'title' => '提货方式'],
            ['dataIndex' => 'is_redeem_txt', 'title' => '是否兑换'],
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
    public function search($request, $downData = '')
    {
        $model = new ActivitiesRewardsSendLogs();
        $model = filterModel($model, $this->filterables, $request);

        if ($downData) {
            //下载提供数据
            $lists = $model->orderBy('id', 'desc')->get();
        } else {
            $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);
        }

        return $lists;
    }

}
