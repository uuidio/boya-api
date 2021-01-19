<?php
/**
 * @Filename        LivesRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Notice;

class NoticeRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields()
    {
        //根据前端要求修改返回的数据格式
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'title', 'dataIndex' => 'title', 'title' => '标题'],
            ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '创建时间'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 列表
     *
     * @Author hfh
     * @param $request
     * @return mixed
     */
    public function listItems($request)
    {
        $request['per_page'] = isset($request['per_page']) && $request['per_page'] ? $request['per_page'] : config('app.per_page');
        $LiveUsersModel = new Notice();
        $LiveUsersModel = filterModel($LiveUsersModel, $this->filterables, $request);

       # $lists = $LiveUsersModel->select(listFieldToSelect($this->listShowFields()))->get()->paginate($request['per_page']);
        $lists = $LiveUsersModel->orderBy('id', 'desc')->paginate($request['per_page']);

        return $lists;
    }


    /**
     * 搜索店铺
     *
     * @Author hfh
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $LiveUsersModel = new Notice();
        $LiveUsersModel = filterModel($LiveUsersModel, $this->filterables, $request);

        $lists = $LiveUsersModel->select(listFieldToSelect($this->listShowFields()))->get();

        return $lists;
    }
}