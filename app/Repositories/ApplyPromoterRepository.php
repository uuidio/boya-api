<?php
/**
 * @Filename ApplyPromoterRepository.php
 * Created by lanlnk
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-02-24
 * @Time: 15:15
 */

namespace ShopEM\Repositories;


use ShopEM\Models\ApplyPromoter;

class ApplyPromoterRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id' => ['field' => 'id', 'operator' => '='],
        'user_id' => ['field' => 'user_id', 'operator' => '='],
        'mobile' => ['field' => 'mobile', 'operator' => '='],
        'job_number' => ['field' => 'job_number', 'operator' => '='],
        'apply_status' => ['field' => 'apply_status', 'operator' => '='],
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
            ['dataIndex' => 'user_id', 'title' => '会员ID'],
            ['dataIndex' => 'real_name', 'title' => '真实姓名'],
//            ['dataIndex' => 'job_number', 'title' => '工号','hide' => true],
            ['dataIndex' => 'mobile', 'title' => '手机号'],
//            ['dataIndex' => 'id_number', 'title' => '身份证号'],
//            ['dataIndex' => 'department', 'title' => '部门'],
            ['dataIndex' => 'apply_status_text', 'title' => '审核状态'],
            ['dataIndex' => 'fail_reason', 'title' => '失败原因'],
            ['dataIndex' => 'created_at', 'title' => '申请时间'],
            ['dataIndex' => 'updated_at', 'title' => '更新时间'],
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
     * @param $request
     * @param string $down
     * @return mixed
     */
    public function search($request, $down = '')
    {
        $model = new ApplyPromoter();

        $model = filterModel($model, $this->filterables, $request);

        if ($down == 'down') {
            $lists = $model->orderBy('id', 'desc')->get();
        }else {
            $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);
        }

        return $lists;
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
            ['key' => 'user_phone', 'dataIndex' => 'user_phone', 'title' => '手机号'],
            [
                'key' => 'wx_info',
                'value' => 'nickname',
                'set_key' => 'nickname',
                'dataIndex' => 'wx_info.nickname',
                'title' => '到账账号'
            ],
            [
                'key' => 'amount',
                'dataIndex' => 'amount',
                'title' => '分销金额'
            ],
            [
                'key' => 'estimated_count_all',
                'value' => 'reward_value',
                'set_key' => 'estimated_rewards',
                'dataIndex' => 'estimated_count_all.reward_value',
                'title' => '预估提现金额'
            ],
            [
                'key' => 'estimated_count_all',
                'value' => 'count',
                'set_key' => 'estimated_count',
                'dataIndex' => 'estimated_count_all.count',
                'title' => '预估提现订单数'
            ],
            [
                'key' => 'rewards_count',
                'value' => 'reward_value',
                'set_key' => 'rewards_rewards',
                'dataIndex' => 'rewards_count.reward_value',
                'title' => '可提现金额'
            ],
            [
                'key' => 'rewards_count',
                'value' => 'count',
                'set_key' => 'rewards_count',
                'dataIndex' => 'rewards_count.count',
                'title' => '可提现佣金订单数'
            ],
            ['key' => 'CountSon', 'dataIndex' => 'CountSon', 'title' => '邀请好友数'],
            [
                'key' => 'customer',
                'value' => 'total',
                'set_key' => 'customer_total',
                'dataIndex' => 'customer',
                'title' => '客户数'
            ],
        ];
    }


    /**
     * 推荐分销统计查询字段
     *
     * @Author huiho
     * @return array
     */
    public function PromoterShopListsFields()
    {
        return [
            ['key' => 'user_phone', 'dataIndex' => 'user_phone', 'title' => '手机号'],
            ['key' => 'count_promoter', 'dataIndex' => 'count_promoter', 'title' => '推广员数量'],
            ['key' => 'profit_sharing', 'dataIndex' => 'profit_sharing', 'title' => '分佣佣金数'],
            ['key' => 'reward_value', 'dataIndex' => 'reward_value', 'title' => '推广佣金'],
        ];
    }

    /**
     * @return array
     */
    public function UserPromoterListsFields()
    {
        return [
            ['dataIndex' => 'real_name', 'title' => '真实姓名'],
            ['key' => 'user_phone', 'dataIndex' => 'user_phone', 'title' => '手机号'],
            ['key' => 'CountSon', 'dataIndex' => 'CountSon', 'title' => '邀请好友数'],
            ['key' => 'customer', 'dataIndex' => 'customer', 'title' => '客户数'],
        ];
    }


    /**
     * 推广人分销统计显示字段
     *
     * @Author huiho
     * @return array
     *
     */
    public function PromoterListsShowFields($type)
    {
        if ($type == 1) {
            $res = listFieldToShow($this->PromoterListsFields());
        } elseif ($type == 4) {
            $res = listFieldToShow($this->UserPromoterListsFields());
        } else {
            $res = listFieldToShow($this->PromoterShopListsFields());
        }
        return $res;
    }

}
