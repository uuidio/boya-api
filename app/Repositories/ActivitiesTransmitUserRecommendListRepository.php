<?php
/**
 * @Filename ActivitiesTransmitUserlistRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\UserAccount;
use ShopEM\Models\ActivitiesTransmitUsers;

class ActivitiesTransmitUserRecommendListRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id'          => ['field' => 'id', 'operator' => '='],
        'transmit_id' => ['field' => 'transmit_id', 'operator' => '='],
        'user_id'     => ['field' => 'user_id', 'operator' => '='],
        'user_mobile' => ['field' => 'user_mobile', 'operator' => '='],
        'created_at'  => ['field' => 'created_at', 'operator' => '='],
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
            ['dataIndex' => 'transmit_name', 'title' => '活动名称'],
            ['dataIndex' => 'user_name', 'title' => '会员名称'],
            ['dataIndex' => 'recommend_user', 'title' => '推荐会员数'],
            ['dataIndex' => 'created_at', 'title' => '参与时间'],
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
    public function search($request,$downData='')
    {
        $model = new ActivitiesTransmitUsers();

        if (isset($request['user_mobile'])) {
            $user_info = UserAccount::where('mobile', $request['user_mobile'])->first();
            if (!empty($user_info)) {
                $request['user_id'] = $user_info['id'];
                unset($request['user_mobile']);
            } else {
                return [];
            }
        }

        $model = filterModel($model, $this->filterables, $request);

        if ($downData) {
            //下载提供数据
            $lists = $model->orderBy('id', 'desc')->get();
        } else {
            $lists = $model->select(DB::raw('(select count(*) from `em_user_accounts` as a where a.pid =em_activities_transmit_users.user_id) as recommend_user,em_activities_transmit_users.*'))->orderBy('recommend_user',
                'desc')->orderBy('id', 'desc')->paginate($request['per_page']);
        }
        return $lists;
    }

}