<?php
/**
 * @Filename PlatformRuleRepository.php
 * @Author  swl 2020-4-7
 */

namespace ShopEM\Repositories;

use ShopEM\Models\PlatformRule;

class PlatformRuleRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'is_show' => ['field' => 'is_show', 'operator' => '='],
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'type' => ['field' => 'type', 'operator' => '='],//规则类型 0 积分

    ];
    /**
     * 规则列表字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function ruleListField($is_show='')
    {
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'title', 'dataIndex' => 'title', 'title' => '标题'],
            ['key' => 'gm_name','dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group'])],
            ['key' => 'type', 'dataIndex' => 'type_name', 'title' => '规则类型'],
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
     * 文章查询
     *
     * @Author moocde <mo@mocode.cn>
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $model = new PlatformRule();
        $model = filterModel($model, $this->filterables, $request);
        $filter = listFieldToSelect($this->ruleListField());
        $filter[] = 'gm_id';//追加属性需要但不展示在列表
   
        $lists = $model->select($filter)->orderBy('listorder', 'desc')->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }
}