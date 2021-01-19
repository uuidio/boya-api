<?php
/**
 * @Filename        UserDepositCashesListRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;

use ShopEM\Models\UserAccount;
use ShopEM\Models\UserDepositCash;

class UserDepositCashesListRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id'         => ['field' => 'id', 'operator' => '='],
        'user_id'    => ['field' => 'user_id', 'operator' => '='],
//        'user_phone' => ['field' => 'user_phone', 'operator' => '='],
        'status'     => ['field' => 'status', 'operator' => '='],
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
            ['dataIndex' => 'id', 'title' => '申请ID'],
            ['dataIndex' => 'batch_no', 'title' => '提现流水号'],
            ['dataIndex' => 'user_phone', 'title' => '会员手机号'],
            ['dataIndex' => 'amount', 'title' => '申请提现金额'],
            ['dataIndex' => 'real_amount', 'title' => '实际到账金额'],
            ['dataIndex' => 'hand_fee', 'title' => '提现手续费'],
            ['dataIndex' => 'status_text', 'title' => '提现状态'],
            ['dataIndex' => 'created_at', 'title' => '申请时间'],
            ['dataIndex' => 'updated_at', 'title' => '更新时间'],
            ['dataIndex' => 'examined_at', 'title' => '审核时间'],
            ['dataIndex' => 'arrive_time', 'title' => '到账时间'],
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
    public function search($request,$export = false)
    {
        $model = new UserDepositCash();

        if (isset($request['user_phone'])) {
            $user_info = UserAccount::where('mobile', $request['user_phone'])->first();
            if (!empty($user_info)) {
                $request['user_id'] = $user_info['id'];
                unset($request['user_phone']);
            } else {
                return [];
            }
        }


        $model = filterModel($model, $this->filterables, $request);

        $per_page = $request['per_page'] ?? 10;

        if ($export) {
            $lists = $model->orderBy('id', 'desc')->get();
        } else {
            $lists = $model->orderBy('id', 'desc')->paginate($per_page);
        }

        return $lists;
    }


}
