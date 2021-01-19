<?php
/**
 * @Filename GroupController.php
 *  团购
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Support\Facades\Redis;
use ShopEM\Http\Controllers\Shop\BaseController;
use Illuminate\Http\Request;
use ShopEM\Models\GoodsClass;
use ShopEM\Models\GoodsSku;
use ShopEM\Models\Group;
use ShopEM\Models\GroupsUserJoin;
use ShopEM\Models\GroupsUserOrder;
use ShopEM\Models\TradePaybill;
use ShopEM\Repositories\GroupGoodRepository;
use ShopEM\Repositories\GroupListRepository;


class GroupController extends BaseController
{


    /**
     *  团购列表
     *
     * @Author hfh_wind
     * @param Request $request
     * @param SecKillGoodRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function GroupList(Request $request, GroupListRepository $repository)
    {

        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $nowTime = date('Y-m-d H:i:s', time());
        $input_data['is_show'] = '1';
        $input_data['start_time_at'] = $nowTime;
        $input_data['end_time_at'] = $nowTime;
        if (!$request->has('gm_id'))
        {
            $input_data['gm_id'] = $this->GMID;
        }

        $lists = $repository->search($input_data);


        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);

    }


    /**
     *  团购列表(废弃)
     *
     * @Author hfh_wind
     * @param Request $request
     * @param SecKillGoodRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodsGroupList(Request $request, GroupGoodRepository $repository)
    {

        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $nowTime = date('Y-m-d H:i:s', time());
        $input_data['is_show'] = '1';
        $input_data['start_time_at'] = $nowTime;
        $input_data['end_time_at'] = $nowTime;
//        $input_data['group_stock_gt'] = 0;
        if (!$request->has('gm_id')) 
        {
            $input_data['gm_id'] = $this->GMID;
        }
        $redis=new Redis();
        $lists = $repository->groupBySearch($input_data);
        $lists = $lists->toArray();

        $lists_data=[];
        foreach($lists['data']  as $key=> $value){
            $sku = GoodsSku::where(['id' => $value['sku_id']])->first();
            //如果是售完,就不显示
            if ($sku->goods_stock <= 0) {
                unset($lists[$key]);
            }
            /*$group_sale_stock_key = $value['sku_id'] . '_group_sale_stock_' . $value['id'];//已经销售
            $group_stock_key = $value['sku_id'] . "_group_stock_" . $value['id'];//团购库存

            $group_sale_stock = $redis::get($group_sale_stock_key);//已经销售
            $group_stock = $redis::get($group_stock_key);//团购库存
            //如果是售完,就不显示
            if($group_stock <= $group_sale_stock){
                unset($lists[$key]);
//                $lists_data['data'][$key]=$value;
            }*/
        }
        $lists['data'] = array_values($lists['data']);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);

    }

    /**
     * 团购分类(当前活动存在的商品三级分类)
     *
     * @Author hfh_wind
     * @return int
     */
    public function GetGoodsGroupCate(Request $request)
    {
        $nowTime = date('Y-m-d H:i:s', time());
        $model = new Group;
        $model = $model->where(['is_show' => '1'])->where('end_time', '>=',$nowTime);
        if (!$request->has('gm_id')) 
        {
            $model = $model->where('gm_id','=',$this->GMID);
        }
        $list = $model->select('gc_id_3')->get();
        $cate_arr = [];
        $redis=new Redis();
        if (count($list) > 0) {
            $cate = [];
            foreach ($list as $key => $value) {

                $group_sale_stock_key = $value['sku_id'] . '_group_sale_stock_' . $value['id'];//已经销售
                $group_stock_key = $value['sku_id'] . "_group_stock_" . $value['id'];//团购库存

                $group_sale_stock = $redis::get($group_sale_stock_key);//已经销售
                $group_stock = $redis::get($group_stock_key);//团购库存
                //如果尚未售完,显示在列表
                if ($group_stock > $group_sale_stock) {
                    $cate[] = $value['gc_id_3'];
                }
            }
            $cate_arr = GoodsClass::whereIn('id', $cate)->select('id', 'gc_name')->get();
        }

        return $this->resSuccess($cate_arr);
    }


    /**
     *  发起的团购订单详情
     *
     * @Author hfh_wind
     * @return int
     */
    public function GetUserGoodsGroup(Request $request)
    {
        $groups_bn = $request->groups_bn;
        if (empty($groups_bn)) {
            return $this->resFailed(406, '参数错误,请传入必要参数!');
        }

//        $nowTime = date('Y-m-d H:i:s', time());
//        $info = GroupsUserOrder::where(['groups_bn' => $groups_bn])->where('end_time', '>=', $nowTime)->first();
        $return['GroupOrder'] = GroupsUserOrder::where(['groups_bn' => $groups_bn])->first();
        $return['Group'] = Group::where(['id' => $return['GroupOrder']['groups_id']])->first();
        if (!$return['Group']) {
            return $this->resFailed(406, '非团购商品!');
        }
//        $return['sku'] = GoodsSku::where('id', '=', $return['info']['sku_id'])->first();

        return $this->resSuccess($return);
    }


    /**
     *  当前正在进行的拼团列表
     *
     * @Author hfh_wind
     * @return int
     */
    public function GoodsGroupOrderList(Request $request)
    {
        $goods_id = $request->id;
        if (empty($goods_id)) {
            return $this->resFailed(406, '参数错误,请传入id!');
        }
        $nowTime = date('Y-m-d H:i:s', time());
        $list = GroupsUserOrder::where(['status' => '1', 'goods_id' => $goods_id])->where('end_time', '>=',
            $nowTime)->get();

        $return['list'] = $list;
        $count = 0;
        //当前正在拼团的数量
        if (count($list) > 0) {
            $count = GroupsUserOrder::where(['status' => '1','goods_id'=>$goods_id])->count();
        }

        $return['now_join_group'] = $count;

        return $this->resSuccess($return);
    }


    /**
     *  支付成功后获取拼团信息
     *
     * @Author hfh_wind
     * @return int
     */
    public function GoodsGroupOrderInfo(Request $request)
    {
        $payment_id = $request->payment_id;
        if (empty($payment_id)) {
            return $this->resFailed(406, '参数错误,请传入payment_id!');
        }

        $join = GroupsUserJoin::where(['payment_id' => $payment_id])->first();
//        $tid = TradePaybill::where(['payment_id' => $payment_id])->first();
        $return['tid'] = $join['tid'];
        $return['GroupOrder'] = GroupsUserOrder::where(['groups_bn' => $join['groups_bn']])->first();
        $return['Group'] = Group::where(['id' => $return['GroupOrder']['groups_id']])->first();
        return $this->resSuccess($return);
    }


}
