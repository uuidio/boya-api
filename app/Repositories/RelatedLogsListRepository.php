<?php
/**
 * @Filename GoodsSpreadListsRepository.php
 * Created by lanlnk
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-02-24
 * @Time: 15:15
 */

namespace ShopEM\Repositories;


use ShopEM\Models\RelatedLogs;
use ShopEM\Models\WxUserinfo;

class RelatedLogsListRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id'         => ['field' => 'id', 'operator' => '='],
        'nickname'   => ['field' => 'nickname', 'operator' => '='],
        'user_id'    => ['field' => 'user_id', 'operator' => '='],
        'pid'        => ['field' => 'pid', 'operator' => '='],
        'status'     => ['field' => 'status', 'operator' => '='],
        'status_arr' => ['field' => 'status', 'operator' => 'in_string'],
        'is_buy'     => ['field' => 'is_buy', 'operator' => '='],
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
            ['dataIndex' => 'user_id', 'title' => '会员id', 'isshow_models' => ['platform']],
            ['dataIndex' => 'mobile', 'title' => '手机号', 'isshow_models' => ['platform']],
            ['dataIndex' => 'wx_info.nickname', 'title' => '用户昵称'],
            ['dataIndex' => 'wx_info.headimgurl', 'title' => '用户头像'],
            ['dataIndex' => 'remaining_time', 'title' => '剩余解绑天数'],
            ['dataIndex' => 'status_text', 'title' => '状态'],
            ['dataIndex' => 'order_infos.reward_value', 'title' => '成交额'],
            ['dataIndex' => 'order_infos.trade_count', 'title' => '订单数'],
//            ['dataIndex' => 'is_buy_text', 'title' => '是否购买'],
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
        $model = new RelatedLogs();

        $lists = [];
        if (isset($request['nickname'])) {
            $user_info = WxUserinfo::where(['nickname' => $request['nickname'], 'user_type' => 1])->first();
            if (!empty($user_info)) {
                $request['user_id'] = $user_info['user_id'];
                unset($request['nickname']);
            } else {
                $lists['data'] = [];
                return $lists;
            }
        }


        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($request['per_page']);


        return $lists;
    }


}
