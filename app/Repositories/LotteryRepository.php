<?php

namespace ShopEM\Repositories;

use ShopEM\Models\Lottery;

class LotteryRepository
{
    /**
     * 定义搜索过滤字段
     * @var array
     */
    protected $filterSearch = [
        'id' => ['field' => 'id', 'operator' => '='],
        'status' => ['field' => 'status', 'operator' => '='],
        'parent_id' => ['field' => 'parent_id', 'operator' => '='],
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
    ];

    /**
     * 查询字段
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'name', 'title' => '奖项名称'],
            ['dataIndex' => 'image', 'title' => '奖项图片'],
            ['dataIndex' => 'type_name', 'title' => '奖品类型'],
            ['dataIndex' => 'prize', 'title' => '中奖积分'],
            ['dataIndex' => 'number', 'title' => '奖品数量'],
            ['dataIndex' => 'remnant_num', 'title' => '剩余奖品数量'],
            ['dataIndex' => 'probability', 'title' => '中奖概率'],
            ['dataIndex' => 'status_name', 'title' => '启用状态'],
            ['dataIndex' => 'is_show_text', 'title' => '展示状态'],
        ];
    }

    /**
     *后台表格列表显示字段
     * @return mixed
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 数据列表
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $model = new Lottery();
        $model = filterModel($model, $this->filterSearch, $request);
        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }

    public function activityListFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => 'ID'],
            ['dataIndex' => 'name', 'title' => '抽奖活动名称'],
            ['dataIndex' => 'desc', 'title' => '抽奖规则'],
            ['dataIndex' => 'use_type_txt', 'title' => '使用类型'],
            ['dataIndex' => 'luck_draw_num', 'title' => '每天抽奖次数'],
            ['dataIndex' => 'integral', 'title' => '抽奖扣减积分'],
            ['dataIndex' => 'wx_mini_page', 'title' => '公众号菜单跳转链接'],
            ['dataIndex' => 'wx_mini_qr', 'title' => '微信小程序二维码'],
            ['dataIndex' => 'status_name', 'title' => '启用状态'],
        ];
    }

    /**
     *后台表格列表显示字段
     * @return mixed
     */
    public function activityListShowFields()
    {
        return listFieldToShow($this->activityListFields());
    }

    public function activitySearch($request)
    {
        $model = new Lottery();
        $model = filterModel($model, $this->filterSearch, $request);
        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }
}
