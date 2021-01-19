<?php
/**
 * @Filename SetPartnersLogsRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */
namespace ShopEM\Repositories;


use ShopEM\Models\SetPartnersLog;

class SetPartnersLogsRepository
{

    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id'      => ['field' => 'id', 'operator' => '='],
        'user_id' => ['field' => 'user_id', 'operator' => '='],
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
            ['dataIndex' => 'user_name', 'title' => '店铺ID'],
            ['dataIndex' => 'before_role_text', 'title' => '修改前身份'],
            ['dataIndex' => 'new_role_text', 'title' => '修改后身份'],
            ['dataIndex' => 'created_at', 'title' => '修改时间'],
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
     * 搜索店铺配置数据
     *
     * @Author huiho
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $model = new SetPartnersLog();

        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('updated_at', 'desc')->paginate($request['per_page']);;
        return $lists;
    }


}