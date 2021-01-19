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
use ShopEM\Models\LiveUsers;

class LiveUsersRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'id'            => ['field' => 'id', 'operator' => '='],
        'login_account'     => ['field' => 'login_account', 'operator' => '='],
        'shop_id'     => ['field' => 'shop_id', 'operator' => '='],
        'platform_id'     => ['field' => 'platform_id', 'operator' => '='],
        'mobile'     => ['field' => 'mobile', 'operator' => '='],
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
            ['key' => 'mobile', 'dataIndex' => 'mobile', 'title' => '手机号码'],
            ['key' => 'shop_id', 'dataIndex' => 'shop_id', 'title' => '门店'],
            ['key' => 'platform_id', 'dataIndex' => 'platform_id', 'title' => '品牌'],
            ['key' => 'company', 'dataIndex' => 'company', 'title' => '公司名称'],
            ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '注册时间'],
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
    public function list($request)
    {
        $LiveUsersModel = new LiveUsers();
        $LiveUsersModel = filterModel($LiveUsersModel, $this->filterables, $request);

        $lists = $LiveUsersModel->select(listFieldToSelect($this->listShowFields()))->get();

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
        $LiveUsersModel = new LiveUsers();
        $LiveUsersModel = filterModel($LiveUsersModel, $this->filterables, $request);

        $lists = $LiveUsersModel->select(listFieldToSelect($this->listShowFields()))->get();

        return $lists;
    }

    /**
     * 会员信息
     *
     * @Author linzhe
     * @param $user_id
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|null|object
     */
    public function userinfo($user_id)
    {
        return LiveUsers::select('id', 'login_account', 'mobile','live_id','shop_id','username','img_url')
            ->where('id', $user_id)
            ->first();
    }

}