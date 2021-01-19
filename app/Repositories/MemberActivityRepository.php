<?php
/**
 * @Filename MemberActivityRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          swl 
 */

namespace ShopEM\Repositories;

use ShopEM\Models\MemberActivity;
use ShopEM\Models\MemberActivitySku;


class MemberActivityRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'is_show' => ['field' => 'is_show', 'operator' => '='],
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'type' => ['field' => 'type', 'operator' => '='],//活动类型
        'verify_status' => ['field' => 'verify_status', 'operator' => '='],//审核状态 0 待审核 1 通过 2驳回

    ];
    /**
     * 活动列表字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function activityListField($is_show='')
    {
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'title', 'dataIndex' => 'title', 'title' => '标题'],
            ['key' => 'gm_name','dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group'])],
            // ['key' => 'type', 'dataIndex' => 'type', 'title' => '活动类型'],
            ['key' => 'verify_status', 'dataIndex' => 'verify_name', 'title' => '审核状态'],
            ['key' => 'listorder', 'dataIndex' => 'listorder', 'title' => '列表顺序'],
            ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '发布时间'],

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
     * 活动查询
     *
     * @Author swl
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $model = new MemberActivity();
        $model = filterModel($model, $this->filterables, $request);
        $filter = listFieldToSelect($this->activityListField());
        $filter[] = 'gm_id';//追加属性需要但不展示在列表
  
        $lists = $model->select($filter)->orderBy('listorder', 'desc')->orderBy('id', 'desc')->paginate($request['per_page']);
        // dd($lists->toArray()['data']);

        // 获取关联的子活动
        $lists =  $lists->toArray();
        foreach ($lists['data'] as $key => &$value) {
            $value['sku'] = MemberActivitySku::where('activity_id',$value['id'])->get()->toArray();
        }
        return $lists;
    }

    // 查询主活动
    public function searchActivity($request){
        $model = new MemberActivity();
        $model = filterModel($model, $this->filterables, $request);
        $filter = listFieldToSelect($this->activityListField());
        $lists = $model->select($filter)->orderBy('listorder', 'desc')->orderBy('id', 'desc')->paginate($request['per_page']);
        return $lists;
    }
}