<?php
/**
 * @Filename RejectMsgRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          zhp <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use ShopEM\Models\RejectMsg;
use Illuminate\Support\Facades\Log;

class RejectMsgRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [];

    /**
     * 查询字段
     *
     * @Author zhp <mo@mocode.cn>
     * @return array
     */
    public function listFields($is_show = '')
    {
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'term', 'dataIndex' => 'term', 'title' => '驳回消息'],
            ['key' => 'reject_status', 'dataIndex' => 'reject_status', 'title' => '消息状态'],
            ['key' => 'reject_sort', 'dataIndex' => 'reject_sort', 'title' => '排序'],
            ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '消息创建时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author zhp <mo@mocode.cn>
     * @return array
     */
    public function listShowFields($is_show = '')
    {
        return listFieldToShow($this->listFields($is_show));
    }

    /**
     * 获取列表数据
     *
     * @Author zhp <mo@mocode.cn>
     * @return mixed
     */
    public function listItems()
    {
        return RejectMsg::select()->paginate(config('app.per_page'));
    }

    /**
     * 消息信息
     *
     * @Author moocde <mo@mocode.cn>
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        return RejectMsg::find($id);
    }
    /**
     * 搜索消息
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return mixed
     */
    public function search($request)
    {
        $rejectMsgModel = new RejectMsg();

        $request['per_page'] = isset($request['per_page']) && $request['per_page'] ? $request['per_page'] : config('app.per_page');



        $rejectMsgModel = filterModel($rejectMsgModel, $this->filterables, $request);
        $lists = $rejectMsgModel->orderBy('reject_sort', 'desc')->paginate($request['per_page']);



        return $lists;
    }
}
