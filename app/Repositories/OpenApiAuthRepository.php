<?php


namespace ShopEM\Repositories;


use ShopEM\Models\OpenapiAuth;

class OpenApiAuthRepository
{
    /**
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'name' => ['field' => 'name', 'operator' => '='],
        'appid' => ['field' => 'appid', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author mssjxzw
     * @return array
     */
    public function listFields()
    {
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'name', 'dataIndex' => 'name', 'title' => '名称'],
            ['key' => 'appid', 'dataIndex' => 'appid', 'title' => 'appid'],
//            ['key' => 'api_auth', 'dataIndex' => 'api_auth', 'title' => 'api权限'],
//            ['key' => 'gm_auth', 'dataIndex' => 'gm_auth', 'title' => '项目权限'],
        ];
    }

    /**
     * 获取列表数据
     *
     * @Author mssjxzw
     * @param int $per_page
     * @return mixed
     */
    public function listItems($per_page = 10)
    {
        return OpenapiAuth::paginate($per_page);
    }


    /**
     * 搜索
     * @Author mssjxzw
     * @param $request
     * @param int $isPage
     * @param int $perPage
     * @return mixed
     */
    public function search($request,$isPage = 1, $perPage = 10)
    {
        $model = new  OpenapiAuth();
        $model = filterModel($model, $this->filterables, $request)->orderBy('id', 'desc');

        if ($isPage) {
            $lists = $model->paginate($perPage);
        } else {
            $lists = $model->get();
        }

        return $lists;
    }
}
