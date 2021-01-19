<?php
/**
 * @Filename MemberActivityApplyRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          swl 
 */

namespace ShopEM\Repositories;

// use ShopEM\Models\MemberActivity;
// use ShopEM\Models\MemberActivitySku;
use ShopEM\Models\MemberActivityApply;

class MemberActivityApplyRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'user_id' => ['field' => 'user_id', 'operator' => '='],
        'verify_status' => ['field' => 'verify_status', 'operator' => '='],//审核状态 0 待审核 1 通过 2驳回
    ];
    /**
     * 报名列表字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function activityListField($is_show='')
    {
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'user_name', 'dataIndex' => 'user_name', 'title' => '会员昵称'],
            // ['key' => 'gm_name','dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group'])],
            ['key' => 'activity', 'dataIndex' => 'activity', 'title' => '活动名称'],
            // ['key' => 'activity_sku', 'dataIndex' => 'activity_sku', 'title' => '场次名称'],
            ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '报名时间'],
            ['key' => 'verify_status', 'dataIndex' => 'verify_name', 'title' => '审核状态'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author djw
     * @return array
     */
    public function listShowFields($is_show='')
    {
        return listFieldToShow($this->listFields($is_show));
    }


    /**
     * 报名查询
     *
     * @Author swl
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $model = new MemberActivityApply();
        $model = filterModel($model, $this->filterables, $request);
        // $filter = listFieldToSelect($this->activityListField());        
        $lists = $model->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($request['per_page']);
        return $lists;
    }

}