<?php
/**
 * @Filename        LivesRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Repositories;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Lives;

class LivesRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
       # 'id'            => ['field' => 'id', 'operator' => '='],
        'status'     => ['field' => 'status', 'operator' => '='],
        'title'     => ['field' => 'title', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author linzhe
     * @return array
     */
    public function listFields()
    {
        //根据前端要求修改返回的数据格式
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'title', 'dataIndex' => 'title', 'title' => '直播间标题'],
            ['key' => 'number', 'dataIndex' => 'number', 'title' => '直播间编号'],
            # ['key' => 'subtitle', 'dataIndex' => 'subtitle', 'title' => '直播间副标题'],
            # ['key' => 'rollitle', 'dataIndex' => 'rollitle', 'title' => '滚动字幕'],
            ['key' => 'shop_id', 'dataIndex' => 'shop_id', 'title' => '店铺id'],
            ['key' => 'img_url', 'dataIndex' => 'img_url', 'title' => '直播间封面图'],
            # ['key' => 'introduce', 'dataIndex' => 'introduce', 'title' => '直播间简介'],
            # ['key' => 'listorder', 'dataIndex' => 'listorder', 'title' => '排序'],
            # ['key' => 'login_account', 'dataIndex' => 'login_account', 'title' => '平台助理登录账号'],
            # ['key' => 'password', 'dataIndex' => 'password', 'title' => '登录密码'],
            ['key' => 'mobile', 'dataIndex' => 'mobile', 'title' => '主播手机号'],
            # ['key' => 'goods_serial', 'dataIndex' => 'goods_serial', 'title' => '商品货号'],
            ['key' => 'streamname', 'dataIndex' => 'streamname', 'title' => '直播流名称'],
            ['key' => 'shop_name', 'dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['key' => 'rebroadcast', 'dataIndex' => 'rebroadcast', 'title' => '转播授权状态'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author linzhe
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 获取列表
     *
     * @Author linzhe
     * @param Request $request
     * @param int $page_count
     * @return mixed
     */
    public function list($request, $page_count = 0)
    {
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;
        $liveModel = new Lives();
        $status = $request['status'];

        unset($request['status']);
        $liveModel = $liveModel->leftJoin('shops', 'shops.id', '=', 'lives.shop_id')->leftJoin('live_users', 'live_users.live_id', '=', 'lives.id')->where('lives.status', $status)->select('lives.*','shops.shop_name','shops.shop_type','live_users.mobile');
        if(isset($request['live_status'])){
            $liveModel = $liveModel->where('lives.live_status', $request['live_status']);
            unset($request['live_status']);
        }
        //店铺分类
        if (isset($request['shop_name'])) {
            $liveModel = $liveModel->where('shops.shop_name',$request['shop_name']);
        }
        if (isset($request['mobile'])) {
            $liveModel = $liveModel->where('live_users.mobile', $request['mobile']);
            unset($request['mobile']);
        }
        if (isset($request['live_status'])) {
            $liveModel = $liveModel->where('lives.live_status',$request['live_status']);
            unset($request['live_status']);
        }
        if (isset($request['number'])) {
            $liveModel = $liveModel->where('lives.number',$request['number']);
            unset($request['number']);
        }
        if($request['id']){
            $id = $request['id'];
            $liveModel = $liveModel->where('lives.id',$id);
            unset($request['id']);
        }
        $liveModel = filterModel($liveModel, $this->filterables, $request);
        $lists = $liveModel->orderBy('id', 'desc')->paginate($page_count);

        return $lists;
    }



    /**
     * 商品详情
     *
     * @Author linzhe
     * @param $id
     * @return mixed
     */
    public function detail($id)
    {
        return Goods::find($id);
    }



}
