<?php
/**
 * @Filename        GoodsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\SetGoodsRelatedRequest;
use ShopEM\Models\Goods;
use ShopEM\Models\GoodsSku;
use ShopEM\Models\GoodsSpreadLogs;
use ShopEM\Models\GoodsSpreadQrs;
use ShopEM\Models\Group;
use ShopEM\Models\SecKillGood;
use ShopEM\Models\PointActivityGoods;
use ShopEM\Models\RelatedLogs;
use ShopEM\Models\UserAccount;
use ShopEM\Services\SecKillService;
use ShopEM\Repositories\GoodsRepository;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Response;
use ShopEM\Services\WeChatMini\CreateQrService;

class GoodsController extends BaseController
{
    /**
     * 商品列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param GoodsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, GoodsRepository $repository)
    {
        $input = $request->all();
        $input['is_point_activity'] = 0;
        if (isset($input['gc_id']) && $input['gc_id']) {
            $class = \ShopEM\Models\GoodsClass::find($input['gc_id']);
            if ($class) {
                switch ($class->class_level) {
                    case 1:
                        $input['gc_id_1'] = $input['gc_id'];
                        unset($input['gc_id']);
                        break;
                    case 2:
                        $input['gc_id_2'] = $input['gc_id'];
                        unset($input['gc_id']);
                        break;
                }
            }
        }
         if (!isset($input['gm_id']))
         {
             $input['gm_id'] = $this->GMID;
         }

        return $this->resSuccess($repository->search($input,20));
    }

    /**
     * 商品详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @param string $entrance
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0, $entrance = '', GoodsRepository $repository)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(700,'商品已下架');
        }

        $detail = $repository->detail($id);
        if (empty($detail)) {
            return $this->resFailed(700, '商品不存在!');
        }

        if ($detail['shop']['shop_state'] != 1) {
            return $this->resFailed(700, '店铺未开启!');
        }

        if ($entrance != 'point_activity') {
            $point_goods = PointActivityGoods::where('goods_id', $id)->select('point_fee')->first();
            if ($point_goods) {
                $pointData['goods_id'] = $id;
                $pointData['activity_type'] = 'point_activity';
                $pointData['point'] = $point_goods->point_fee;
                $json = [
                    'code' => 101,
                    'message' => '该商品需到积分专区购买',
                    'result' => $pointData,
                ];
                return Response::json($json);
            }
        }
        //如果商品有规格
        // 查询所有规格商品
        // 各规格商品，前端使用
        $spec_array = $this->getGoodsSpecListByGoodsId($id);
        if (count($spec_array) > 0) {
            $detail['spec_list'] = $spec_array;
        }
        if (count($spec_array) == 1) {
            $detail['goods_stock'] = $spec_array[0]['goods_stock'];
        } else {
            $goods_stock = array_column($spec_array, 'goods_stock');
            $detail['goods_stock'] = array_sum($goods_stock);
        }


        $favorite = \ShopEM\Services\User\UserGoodsFavoriteService::existFavorite($this->user['id'], $id);
        $detail['is_favorite'] = $favorite ? true : false;
        $post_rule = \ShopEM\Models\ShopShip::where([['shop_id', '=', $detail->shop_id], ['default', '=', 1]])->first();
        if ($post_rule) {
            $detail['post_content'] = $post_rule->content;
        } else {
            $detail['post_content'] = ($detail->shop->post_fee > 0) ? $detail->shop->post_fee . '元' : '免邮';
        }


        //团购
        $nowTime = date('Y-m-d H:i:s', time());
        $group_activiy = Group::where('start_time', '<=', $nowTime)->where('end_time', '>=',
            $nowTime)->where('goods_id', '=', $id)->get();
        if (count($group_activiy) > 0) {
            $group_activiy=$group_activiy->toArray();

            //拼团的sku价格
            $sign = 0;
            foreach ($spec_array as $key => $value) {
                foreach ($group_activiy as $group_key => $group_value) {

                    $group_stock = $group_value['group_stock'];
//                    $group_stock_key = $group_value['sku_id'] . '_group_stock_' . $group_value['id'];//购团库存值
                    $group_sale_stock_key = $group_value['sku_id'] . '_group_sale_stock_' . $group_value['id']; //已卖

//                    $group_stock = Redis::get($group_stock_key);
                    $group_sale_stock = Redis::get($group_sale_stock_key);

//                    $check_group_sale_stock=$group_stock-$group_sale_stock;

                   /*if ($group_sale_stock == $group_stock) {
                        //如果团购库存没了,那就正常显示商品
                        unset($group_activiy[$group_key]);
                   }*/

//                    if ($value['id'] == $group_value['sku_id'] && $check_group_sale_stock > 0) {
                    if ($value['id'] == $group_value['sku_id']) {
                       if ($value['goods_stock'] <= 0) {
                           //如果团购库存没了,那就正常显示商品
                           unset($group_activiy[$group_key]);
                       } else {
                           $spec_array[$key]['goods_price'] = $group_value['group_price'];
                           //                        $spec_array[$key]['goods_stock'] = $check_group_sale_stock; //缓存里的库存
                           $spec_array[$key]['group_activty_id'] = $group_value['id'];
                           $sign = 1;
                       }
                    }
                }
            }

            if ($sign == 1) {
//                $detail['spec_list'] = $spec_array;
                $detail['is_group'] = '1';
                $detail['is_group_info'] = array_values($group_activiy);
                $detail['group_spec_list'] = $spec_array;
            }
//            $detail['group_spec_list'] = $spec_array;
            if ($detail['spec_name'] == '') {
                $detail['group_goods_price'] = $group_value['group_price'];
                $detail['group_goods_stock'] = $group_value['group_stock'];
            }
        }


        if ($entrance == 'point_activity') {
            $point_goods = PointActivityGoods::where('goods_id', $id)->latest()->first();
            if (!$point_goods) {
                return $this->resFailed(700, '非积分活动商品');
            }
            $detail['point_goods_info'] = $point_goods;
            $detail['point_price'] = $point_goods->point_price;
            $detail['goods_marketprice'] = $detail['goods_price'];//把商品售价变为商品原价
            $detail['goods_price'] = $point_goods->point_price;//把商品售价变为商品的积分活动价格
            $detail['point_fee'] = $point_goods->point_fee;//商品所需积分

            foreach ($spec_array as $key => $value) {
                $spec_array[$key]['goods_price'] = $point_goods->point_price;
            }
            $detail['spec_list'] = $spec_array;
        }

        //获取商品正在参加的促销活动
        $Activity = new \ShopEM\Services\Marketing\Activity();
        $detail['activity'] = $Activity->GoodsActing($id);

        $detail['seckill_goods'] = ['status'=>false];
        $seckill_goods = SecKillGood::joinSecKill($id);
        if ($seckill_goods) {
            $seckill['status'] = true;
            $seckill['gm_id'] = $seckill_goods->gm_id;
            $seckill['goods_id'] = $seckill_goods->goods_id;
            $seckill['sku_id'] = $seckill_goods->sku_id;
            $seckill['activity_id'] = $seckill_goods->seckill_ap_id;;
            $seckill['msg'] = '商品正在参与活动，去看看吧';
            $detail['seckill_goods'] = $seckill;
        }
       /* //获取商品正在参加的促销活动
        $detail['sekill_goods'] = false;
        foreach ($detail['sku'] as $sku) {
            $seckill_good_key = 'seckill_good_' . $sku['id']; //秒杀商品的缓存key
            if (Cache::get($seckill_good_key)) {
                $detail['sekill_goods'] = true;
            }
        }*/

        return $this->resSuccess($detail);
    }


    /**
     * 获得商品规格数组
     * @Author hfh_wind
     * @param $id 商品id
     * @return mixed
     */
    public function getGoodsSpecListByGoodsId($id)
    {
        $spec_array = GoodsSku::where(['goods_id' => $id])->get()->toArray();
        return $spec_array;
    }


    /**
     * 获取商品评价
     *
     * @Author djw
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function rate($id = 0, Request $request)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $per_page = config('app.per_page');
        $input_data = [
            'goods_id' => $id,
            'per_page' => $per_page,
        ];

        $result = ['good', 'neutral', 'bad'];
        if (in_array($request->result, $result)) {
            $input_data['result'] = $request->result;
        }

        $rateRepository = new \ShopEM\Repositories\RateRepository;
        $lists = $rateRepository->search($input_data);

        return $this->resSuccess($lists);
    }


    /**
     * 获取商品筛选条件
     *
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function filter(Request $request)
    {
        $gc_id = intval($request->gc_id);
        $goodsService = new \ShopEM\Services\GoodsService;
        $result = $goodsService->getGoodsFilter($gc_id);
        return $this->resSuccess($result);
    }


    /**
     * 获取商品热门搜索关键字
     *
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function hotkeyword(Request $request)
    {
        $per_page = $request->per_page;
        $repository = new \ShopEM\Repositories\GoodsHotkeywordsRepository;
        $request['no_disabled'] = 1;
        if (!$request->has('gm_id')) {
            $request['gm_id'] = $this->GMID;
        }
        $result = $repository->listItems($request, $per_page);
        return $this->resSuccess($result);
    }


    /**
     * 生成个人小程序商品二维码
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function CreateWxMiniQr(Request $request)
    {
        $id = $request['id'] ?? 0;

        $user_id = $this->user->id;
        $gm_id=$this->GMID;
        if (empty($id)) {
            return $this->resFailed(414, '商品id参数错误!');
        }

        //判断是否推广员
        if ($this->user->is_promoter != 1) {
            $check = UserAccount::where('id', $user_id)->select('is_promoter')->first();
            if ($check['is_promoter'] != 1) {
                return $this->resFailed(700, '您尚未拥有推广权限!');
            }
        }

        $check = Goods::where('id', $id)->count();

        if (empty($check)) {
            return $this->resFailed(700, '找不到商品数据!');
        }

        //小程序二维码
        $service = new CreateQrService();
        $scene = "t=g&id=" . $user_id . "&gid=" . $id;
        $page = "pagesA/goods/detail";
//        $page="pages/index/index";
        $res = $service->GetWxQr($scene, $page,$gm_id);

        if(!empty($res)){
            $insert_data['goods_id'] = $id;
            $insert_data['user_id'] = $user_id;
            $insert_data['wx_mini_goods_person_qr'] = $res;
            $insert_data['gm_id'] = $gm_id;

            GoodsSpreadQrs::create($insert_data);
            $messge=$this->resSuccess($insert_data, '生成成功!');
        }else{

            $messge=$this->resFailed(700, '请稍后再试!');
        }

        return $messge;
    }

    /**
     * 获取个人分享商品信息
     *
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetWxMiniGoodsPerson(Request $request)
    {

        $id = $request['id'] ?? 0;

        $user_id = $this->user->id;

        if (empty($id)) {
            return $this->resFailed(414, '商品id参数错误!');
        }

        //判断是否推广员
        if ($this->user->is_promoter != 1) {

            $check = UserAccount::where('id', $user_id)->select('is_promoter')->first();
            if ($check['is_promoter'] != 1) {
                return $this->resFailed(700, '您尚未拥有推广权限!');
            }
        }


        $qr_info = GoodsSpreadQrs::where(['user_id' => $user_id, 'goods_id' => $id])->first();
        //二维码
        if (!empty($qr_info)) {
            $return['wx_mini_goods_person_qr'] = $qr_info['wx_mini_goods_person_qr'];
        }
        //商品信息
        $return['goods_info'] = Goods::find($id);

        return $this->resSuccess($return);
    }

    /**
     * 推广商品关联会员关系
     *
     * @param SetGoodsRelatedRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function SetGoodsRelated(SetGoodsRelatedRequest $request)
    {
        $user_id = $this->user->id;

        $data = $request->all();

        //判断是否推广员
        $is_promoter = UserAccount::where('id',$data['pid'])->value('is_promoter');
        if ($is_promoter != 1) {
            return $this->resSuccess([], '推广员不存在!');
        }
        if ($user_id == $data['pid']) {
            return $this->resSuccess([], '自己不能绑定自己!');
        }

        try {

            //尚未购买的
            $check = GoodsSpreadLogs::where([
                'goods_id' => $data['goods_id'],
                'user_id' => $user_id,
                'status' => 0
            ])->select('pid')->first();
            //存在就覆盖,更新创建时间和覆盖父级
            if ($check) {
//                $return['goods_id'] = $data['goods_id'];
//                $return['user_id'] = $user_id;
                //如果推广员不是一个人
                if ($check['pid'] != $data['pid']) {


                    $return['goods_id'] = $data['goods_id'];
                    $return['user_id'] = $user_id;
                    $return['pid'] = $data['pid'];
                    GoodsSpreadLogs::create($return);

                    //老数据过期
                    $update_out_time['status'] = 1;
                    $update_out_time['created_at'] = date('Y-m-d H:i:s', time());
                    GoodsSpreadLogs::where([
                        'goods_id' => $data['goods_id'],
                        'user_id' => $user_id,
                        'status' => 0,
                        'pid' => $check['pid']
                    ])->update($update_out_time);

                } else {
                    $return['pid'] = $data['pid'];
                    $return['created_at'] = date('Y-m-d H:i:s', time());
                    GoodsSpreadLogs::where([
                        'goods_id' => $data['goods_id'],
                        'user_id' => $user_id,
                        'status' => 0
                    ])->update($return);
                }

            } else {
                $return['goods_id'] = $data['goods_id'];
                $return['user_id'] = $user_id;
                $return['pid'] = $data['pid'];
                GoodsSpreadLogs::create($return);
            }
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess([], '绑定关系成功!');
    }

    /**
     * 推广商品关联会员关系
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function SetRelated(Request $request)
    {

        $pid = $request['pid'] ?? 0;
        //判断是否推广员
        if ($pid <= 0) {
            return $this->resSuccess([], '推广员id必填!');
        }
        $user_id = $this->user->id;

        $data = $request->all();

        //判断是否推广员
        $is_promoter = UserAccount::where('id',$data['pid'])->value('is_promoter');
        if ($is_promoter != 1) {
            return $this->resSuccess([], '推广员不存在!');
        }
        if ($user_id == $data['pid']) {
            return $this->resSuccess([], '自己不能绑定自己!');
        }

        //检查是否有尚未解绑的会员
        $check = RelatedLogs::where([
            'user_id' => $user_id,
//                'status'  => 1,
            'pid' => $data['pid'],
        ])->select('pid', 'id', 'status')->first();

        DB::beginTransaction();
        try {

            //存在就覆盖,更新创建时间和覆盖父级
            if (!empty($check)) {
                //如果推广员不是同一个人
                if ($check['status'] != 1) {
                    //老数据过期
                    $update_out_time['created_at'] = date('Y-m-d H:i:s', time());
                    $update_out_time['status'] = 0;
                    RelatedLogs::where([
                        'user_id' => $user_id,
                        'status' => 1
                    ])->update($update_out_time);

                    $return['user_id'] = $user_id;
                    $return['pid'] = $data['pid'];
                    $return['status'] = 1;
                    $return['created_at'] = date('Y-m-d H:i:s', time());
//                    RelatedLogs::create($return);

                    RelatedLogs::where([
                        'user_id' => $user_id,
                        'pid' => $data['pid']
                    ])->update($return);

                } else {
                    //老数据过期
                    $update_out_time['created_at'] = date('Y-m-d H:i:s', time());
                    $update_out_time['status'] = 0;
                    RelatedLogs::where([
                        'user_id' => $user_id,
                        'status' => 1
                    ])->update($update_out_time);

                    $return['pid'] = $data['pid'];
                    $return['status'] = 1;
                    $return['created_at'] = date('Y-m-d H:i:s', time());
                    RelatedLogs::where([
                        'id' => $check['id'],
                    ])->update($return);
                }

            } else {
                //老数据过期
                $update_out_time['created_at'] = date('Y-m-d H:i:s', time());
                $update_out_time['status'] = 0;
                RelatedLogs::where([
                    'user_id' => $user_id,
                    'status' => 1
                ])->update($update_out_time);

                $return['user_id'] = $user_id;
                $return['pid'] = $data['pid'];
                $return['status'] = 1;
                RelatedLogs::create($return);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }


        return $this->resSuccess([], '绑定关系成功!');
    }

    /**
     * 商品参团详情
     * @Author hfh_wind
     * @param Request $request
     * @param GoodsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function GroupDetail(Request $request, GoodsRepository $repository)
    {
        $id = $request['id']??0;
        if ($id <= 0) {
            return $this->resFailed(414, "缺少商品id");
        }

        $detail = $repository->detail($id);
        if (empty($detail)) {
            return $this->resFailed(700, '商品不存在!');
        }

        if ($detail['shop']['shop_state'] != 1) {
            return $this->resFailed(700, '店铺未开启!');
        }

        //如果商品有规格
        // 查询所有规格商品
        // 各规格商品，前端使用
        $spec_array = $this->getGoodsSpecListByGoodsId($id);
        if (count($spec_array) > 0) {
//            $detail['spec_list'] = $spec_array;
        }
        if (count($spec_array) == 1) {
            $detail['goods_stock'] = $spec_array[0]['goods_stock'];
        } else {
            $goods_stock = array_column($spec_array, 'goods_stock');
            $detail['goods_stock'] = array_sum($goods_stock);
        }

        if (empty($detail)) {
            return $this->resFailed(700);
        }
        $group_spec_list = [];
        //团购
        $nowTime = date('Y-m-d H:i:s', time());
        $group_activiy = Group::where('start_time', '<=', $nowTime)->where('end_time', '>=',
            $nowTime)->where('goods_id', '=', $id)->get();
        if (count($group_activiy) > 0) {
            $group_activiy->toArray();
            //拼团的sku价格

            foreach ($spec_array as $key => $value) {
                foreach ($group_activiy as $group_key => $group_value) {

                    if ($value['id'] == $group_value['sku_id']) {

                        $value['goods_price'] = $group_value['group_price'];
                        $value['group_activty_id'] = $group_value['id'];
                        $value['goods_spec'] = $value['goods_spec'] ?? [];
                        $group_spec_list[] = $value;
                    }
                }
            }

        }
        $detail['group_spec_list'] = $group_spec_list;

        return $this->resSuccess($detail);
    }
}
