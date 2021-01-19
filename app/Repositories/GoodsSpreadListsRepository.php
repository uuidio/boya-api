<?php
/**
 * @Filename GoodsSpreadListsRepository.php
 * Created by lanlnk
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-02-24
 * @Time: 15:15
 */

namespace ShopEM\Repositories;


use ShopEM\Models\GoodsSpreadLogs;
use ShopEM\Models\WxUserinfo;

class GoodsSpreadListsRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id'         => ['field' => 'id', 'operator' => '='],
        'nickname'   => ['field' => 'nickname', 'operator' => '='],
        'goods_id'   => ['field' => 'goods_id', 'operator' => '='],
        'user_id'    => ['field' => 'user_id', 'operator' => '='],
        'pid'        => ['field' => 'pid', 'operator' => '='],
        'status'     => ['field' => 'status', 'operator' => '='],
        'status_arr' => ['field' => 'status', 'operator' => 'in_string'],
        'tid'        => ['field' => 'tid', 'operator' => '='],
        'oid'        => ['field' => 'oid', 'operator' => '='],
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
            ['dataIndex' => 'goods_infos.goods_name', 'title' => '商品名称'],
            ['dataIndex' => 'wx_info.nickname', 'title' => '用户昵称'],
            ['dataIndex' => 'wx_info.headimgurl', 'title' => '用户头像'],
            ['dataIndex' => 'remaining_time', 'title' => '剩余解绑天数'],
            ['dataIndex' => 'status_text', 'title' => '状态'],
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
        $model = new GoodsSpreadLogs();

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