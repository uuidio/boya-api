<?php
/**
 * @Filename        TradeRewardsListsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;


use ShopEM\Models\TradeRewards;
use ShopEM\Models\WxUserinfo;

class TradeRewardsListsRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id'               => ['field' => 'id', 'operator' => '='],
        'nickname'         => ['field' => 'nickname', 'operator' => '='],
        'shop_id'          => ['field' => 'shop_id', 'operator' => '='],
        'goods_id'         => ['field' => 'goods_id', 'operator' => '='],
        'user_id'          => ['field' => 'user_id', 'operator' => '='],
        'pid'              => ['field' => 'pid', 'operator' => '='],
        'tid'              => ['field' => 'tid', 'operator' => '='],
        'oid'              => ['field' => 'oid', 'operator' => '='],
        'type'             => ['field' => 'type', 'operator' => '='],
        'iord'             => ['field' => 'iord', 'operator' => '='],
        'created_start_at' => ['field' => 'created_at', 'operator' => '>='],
        'created_end_at'   => ['field' => 'created_at', 'operator' => '<='],
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
            ['dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['dataIndex' => 'goods_infos.goods_name', 'title' => '商品名称'],
            ['dataIndex' => 'wx_info.nickname', 'title' => '用户昵称'],
            ['dataIndex' => 'wx_info.headimgurl', 'title' => '用户头像'],
            ['dataIndex' => 'reward_value', 'title' => '佣金'],
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
        $model = new TradeRewards();

        $lists=[];
        if (isset($request['nickname'])) {
            $user_info = WxUserinfo::where(['nickname' => $request['nickname'], 'user_type' => 1])->first();
            if (!empty($user_info)) {
                $request['user_id'] = $user_info['user_id'];
                unset($request['nickname']);
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