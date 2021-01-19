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
use ShopEM\Models\Goods;
use ShopEM\Models\LivesLog;
use ShopEM\Models\Shop;

class LivesLogRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'id'         => ['field' => 'id', 'operator' => '='],
        'live_id'    => ['field' => 'live_id', 'operator' => '='],
        'type'       => ['field' => 'type', 'operator' => '='],
        'delete'     => ['field' => 'delete', 'operator' => '='],
        'shop_id'    => ['field' => 'shop_id', 'operator' => '='],
        'shop_name'    => ['field' => 'shop_name', 'operator' => '='],
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
            #['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            #['key' => 'live_id', 'dataIndex' => 'live_id', 'title' => '直播间id'],
            ['key' => 'title', 'dataIndex' => 'title', 'title' => '直播间标题'],
            #['key' => 'surface_img', 'dataIndex' => 'surface_img', 'title' => '直播间封面图'],
            ['key' => 'like', 'dataIndex' => 'like', 'title' => '点赞数'],
            ['key' => 'heat', 'dataIndex' => 'heat', 'title' => '热度'],
            ['key' => 'audience', 'dataIndex' => 'audience', 'title' => '观众数'],
            ['key' => 'collect', 'dataIndex' => 'collect', 'title' => '关注数'],
            #['key' => 'type', 'dataIndex' => 'type', 'title' => '直播模式'],
            ['key' => 'shop_name', 'dataIndex' => 'shop_name', 'title' => '店铺名称'],
            ['key' => 'start_at', 'dataIndex' => 'start_at', 'title' => '开始时间'],
            ['key' => 'end_at', 'dataIndex' => 'end_at', 'title' => '结束时间'],
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
    public function list($request ,$page_count)
    {
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;
        $logModel = new LivesLog();
        if (isset($request['shop_name'])) {
            $logModel = $logModel->leftJoin('shops', 'shops.id', '=', 'lives_log.shop_id')->where('shops.shop_name', $request['shop_name'])->select('lives_log.*','shops.shop_name','shops.shop_type');
        }
        if (isset($request['null'])) {
            $logModel = $logModel->whereNotNull('lives_log.end_at');
        }
        $logModel = filterModel($logModel, $this->filterables, $request);
        $lists = $logModel->orderBy('id', 'desc')->paginate($page_count);

        foreach($lists as $key => $value) {
            $shop = Shop::where('id', '=', $value['shop_id'])->select('shop_name')->first();
            if($shop){
                $lists[$key]['shop_name'] = $shop['shop_name'];
            }
            $goods = json_decode($value['limit_goods'], true);
            if (!empty($goods)) {
                $lists[$key]['goods'] = Goods::whereIn('id',$goods)->where('goods_state','=','1')->get();
            } else {
                $lists[$key]['goods'] = [];
            }
        }
        return $lists;
    }

    public function search($request,$page_count)
    {
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;
        $logModel = new LivesLog();
        $logModel = filterModel($logModel, $this->filterables, $request);
        $lists = $logModel->orderBy('id', 'desc')->paginate($page_count)->first();
        $goods = json_decode($lists['limit_goods'],true);

            if(!empty($goods)) {
                $lists['goods'] = Goods::whereIn('id',$goods)->where('goods_state','=','1')->get();
            }else{
                $lists['goods'] = [];
            }

        return $lists;
    }


}