<?php
/**
 * @Filename        UserDepositLogsListsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;


use ShopEM\Models\UserAccount;
use ShopEM\Models\UserDepositLog;


class UserDepositLogsListsRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id'         => ['field' => 'id', 'operator' => '='],
        'user_phone' => ['field' => 'user_phone', 'operator' => '='],
        'user_id'    => ['field' => 'user_id', 'operator' => '='],
        'message'   => ['field' => 'message', 'operator' => 'like'],
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
            ['dataIndex' => 'id', 'title' => 'id'],
//            ['dataIndex' => 'user_phone', 'title' => '手机号'],
            ['dataIndex' => 'fee', 'title' => '金额'],
            ['dataIndex' => 'message', 'title' => '备注'],
            ['dataIndex' => 'type_text', 'title' => '类型'],
            ['dataIndex' => 'created_at', 'title' => '记录时间'],
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
     * 搜索申请数据
     *
     * @Author huiho
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $model = new UserDepositLog();

        $lists=[];
        if (isset($request['user_phone'])) {
            $user_info = UserAccount::where(['mobile' => $request['user_phone']])->first();
            if (!empty($user_info)) {
                $request['user_id'] = $user_info['user_id'];
                unset($request['user_phone']);
            } else {
                $lists['data']=[];
                return $lists;
            }
        }

        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }


}