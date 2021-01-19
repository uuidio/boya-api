<?php
/**
 * @Filename        RaffleRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Repositories;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\LiveRebroadcast;
use ShopEM\Models\shop;

class LiveRebroadcastRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        #  'id'            => ['field' => 'id', 'operator' => '='],
        'live_id'     => ['field' => 'live_id', 'operator' => '='],
        'live_rebroadcasts.shop_id'     => ['field' => 'shop_id', 'operator' => '='],
        #  'status'     => ['field' => 'status', 'operator' => '='],
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
            #['key' => 'rebroadcasts_live', 'dataIndex' => 'rebroadcasts_live', 'title' => '转播直播间'],
            ['key' => 'title', 'dataIndex' => 'title', 'title' => '转播直播间标题'],
            ['key' => 'shop_name', 'dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['key' => 'rebroadcasts_status_name', 'dataIndex' => 'rebroadcasts_status', 'title' => '授权状态'],
            #  ['key' => 'rebroadcasts_name', 'dataIndex' => 'rebroadcasts', 'title' => '平台转播'],
            ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '发起时间'],
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
        $RebroadcastModel = new LiveRebroadcast();
        $RebroadcastModel = $RebroadcastModel->leftJoin('lives', 'lives.id', '=', 'live_rebroadcasts.rebroadcasts_live')->where('live_rebroadcasts.shop_id', $request['shop_id'])->select('live_rebroadcasts.*','lives.shop_id');

//        if($request['live_id']){
//            $raffleModel = $raffleModel->leftJoin('wx_userinfos', 'wx_userinfos.user_id', '=', 'live_raffle_logs.user_id')->where('live_raffle_logs.live_id', $request['live_id'])->where('live_raffle_logs.status','2')->select('live_raffle_logs.*','wx_userinfos.nickname');
//        }

        $raffleModel = filterModel($RebroadcastModel, $this->filterables, $request);
        $lists = $RebroadcastModel->orderBy('id', 'desc')->paginate($page_count);

        foreach ($lists as $key => $value) {
            $shop = shop::where('id','=',$value['shop_id'])->select('shop_name')->first();
            $lists[$key]['shop_name'] = $shop['shop_name'];
            $lists[$key]['rebroadcasts_status_name'] = $this->rebroadcasts_status($value['rebroadcasts_status']);
            $lists[$key]['rebroadcasts_name'] = $this->rebroadcasts($value['rebroadcasts']);
        }

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


    protected function rebroadcasts_status($rebroadcasts_status)
    {
        $status[0] = '未接受';
        $status[1] = '已接受';
        $status[2] = '拒绝';
        $status[3] = '已取消';

        return $status[$rebroadcasts_status];
    }

    protected function rebroadcasts($rebroadcasts)
    {
        $status[0] = '关闭';
        $status[1] = '开启';

        return $status[$rebroadcasts];
    }
}
