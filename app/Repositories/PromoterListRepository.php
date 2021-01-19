<?php
/**
 * @Filename PromoterListRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;


use ShopEM\Models\PartnerRelatedLog;
use ShopEM\Models\WxUserinfo;

class PromoterListRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id'               => ['field' => 'id', 'operator' => '='],
        'user_id'          => ['field' => 'user_id', 'operator' => '='],
        'partner_id'       => ['field' => 'partner_id', 'operator' => '='],
        'nick_name'        => ['field' => 'nick_name', 'operator' => '='],
        'type'             => ['field' => 'type', 'operator' => '='],
        'status'           => ['field' => 'status', 'operator' => '='],
        'updated_at_start' => ['field' => 'updated_at', 'operator' => '>='],
        'updated_at_end'   => ['field' => 'updated_at', 'operator' => '<='],
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
            ['key' => 'wx_info.nickname', 'dataIndex' => 'wx_info.nickname', 'title' => '昵称'],
            ['key' => 'wx_info.headimgurl', 'dataIndex' => 'wx_info.headimgurl', 'title' => '头像'],
            ['key' => 'reward_info.reward_value', 'dataIndex' => 'reward_info.reward_value', 'title' => '成交额'],
            ['key' => 'reward_info.count', 'dataIndex' => 'reward_info.count', 'title' => '订单数'],
            ['key' => 'updated_at', 'dataIndex' => 'updated_at', 'title' => '绑定时间'],
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
     * 推广人分销统计查询字段
     *
     * @Author huiho
     * @return array
     */
    public function PromoterListsFields()
    {
        return [
            ['key' => 'wx_info.nickname', 'dataIndex' => 'wx_info.nickname', 'title' => '昵称'],
            ['key' => 'wx_info.headimgurl', 'dataIndex' => 'wx_info.headimgurl', 'title' => '头像'],
            ['key' => 'reward_info.reward_value', 'dataIndex' => 'reward_info.reward_value', 'title' => '成交额'],
            ['key' => 'reward_info.count', 'dataIndex' => 'reward_info.count', 'title' => '订单数'],
            ['key' => 'updated_at', 'dataIndex' => 'updated_at', 'title' => '绑定时间'],
        ];
    }

    /**
     * 推广人分销统计显示字段
     *
     * @Author huiho
     * @return array
     *
     */
    public function PromoterListsShowFields()
    {
        return listFieldToShow($this->PromoterListsFields());
    }

    /**
     * 搜索申请数据
     *
     * @Author huiho
     * @param $request
     * @return mixed
     */
    public function search($request, $down = '')
    {
        $model = new PartnerRelatedLog();

        if (isset($request['nick_name'])) {
            $user_info = WxUserinfo::where('nickname', 'like', '%' . $request['nick_name'] . '%')->where(['user_type' => 1])->first();
            if (!empty($user_info)) {
                $request['user_id'] = $user_info['user_id'];
                unset($request['nick_name']);
            } else {
                $lists['data']=[];
                return $lists;
            }
        }

        $model = filterModel($model, $this->filterables, $request);

        if ($down) {
            $lists = $model->orderBy('id', 'desc')->get();

        } else {
            $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);
        }

        return $lists;
    }


}