<?php
/**
 * @Filename        UserPointLogsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          swl 
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\UserPointLog;
use ShopEM\Models\GmPlatform;

class UserPointLogsRepository
{
      /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'behavior_type' => ['field' => 'behavior_type', 'operator' => '='],
        'user_id' => ['field' => 'user_id', 'operator' => '='],
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'log_type' => ['field' => 'log_type', 'operator' => '='],
        'push_crm' => ['field' => 'push_crm', 'operator' => '='],

    ];

    /*
     * 字段信息
     */
    protected $listFields = [
        ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
        ['key' => 'behavior', 'dataIndex' => 'behavior', 'title' => '行为描述'],
        ['key' => 'behavior_type', 'dataIndex' => 'behavior_type', 'title' => '类型'],
        ['key' => 'remark', 'dataIndex' => 'remark', 'title' => '备注'],
        ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '创建时间'],
    ];

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields);
    }

    /**
     * 获取所有积分明细
     *
     * @Author swl
     * @return mixed
     */
    public function listItems($request)
    {
        $page_count = isset($request['page_size']) ? $request['page_size']: config('app.per_page');
        $userPointLog = new UserPointLog();
        $userPointLog = filterModel($userPointLog, $this->filterables, $request);
        $lists = $userPointLog->orderBy('id', 'desc')->paginate($page_count);

        return $lists;
    }


    public function search($request=[],$page_count=0)
    {
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;

        $userPointLog = new UserPointLog();
        $userPointLog = filterModel($userPointLog, $this->filterables, $request);
        $selfGmid = GmPlatform::gmSelf();

        if (isset($request['is_self']) && $request['is_self'] > 0) 
        {
            $userPointLog = $userPointLog->where('gm_id','=',$selfGmid);
        }
        else
        {
            $userPointLog = $userPointLog->where('gm_id','!=',$selfGmid);
        }


        $lists = $userPointLog->orderBy('updated_at', 'desc')->paginate($page_count);
        return $lists;
    }
}
