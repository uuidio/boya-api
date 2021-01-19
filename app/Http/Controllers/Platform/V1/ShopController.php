<?php
/**
 * @Filename ShopController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\ShopActRequest;
use ShopEM\Http\Requests\Platform\ShopCreateRequest;
use ShopEM\Http\Requests\Platform\ShopRequest;
use ShopEM\Http\Requests\Platform\ShopDoExamineRequest;
use ShopEM\Models\SellerAccount;
use ShopEM\Models\Shop;
use ShopEM\Models\ShopAttr;
use ShopEM\Models\ShopClassRelations;
use ShopEM\Models\Goods;
use ShopEM\Models\ShopRelBrand;
use ShopEM\Models\ShopRelSeller;
use ShopEM\Models\ShopInfo;
use ShopEM\Repositories\ShopRepository;
use ShopEM\Repositories\ShopSellerRepository;

class ShopController extends BaseController
{


    /**
     * 店铺列表
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, ShopSellerRepository $repository, ShopRepository $shopRepository)
    {
        $data = $request->all();
        $data['gm_id'] = $this->GMID;
        $lists = $shopRepository->search($data, 1);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        foreach ($lists as $key => $value) {
            $seller = [
                'data'  => DB::table('seller_accounts')->select('seller_accounts.*')->leftJoin('shop_rel_sellers',
                    'shop_rel_sellers.seller_id', '=', 'seller_accounts.id')->where('shop_rel_sellers.shop_id',
                    $value->id)->get(),
                'field' => $repository->listShowFields(),
            ];
            $num = DB::table('goods')->where('goods_state','1')->where('shop_id',$value['id'])->count();
            $lists[$key]['shop_goods_sum'] = $num;
            $lists[$key]['account'] = $seller;
            $lists[$key]['is_open'] = $value['shop_state'];
            $lists[$key]['shop_state'] = $value['shop_state'] ? '开启' : '关闭';

        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $shopRepository->listShowFields(),
        ]);
    }

    /**
     * 店铺详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = Shop::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }
        if ($detail->gm_id != $this->GMID) 
        {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }


    /**
     * 更新店铺数据
     *
     * @Author moocde <mo@mocode.cn>
     * @param ShopRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ShopCreateRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        //分类
        $rel_cat_id = isset($request->rel_cat_id) ? $request->rel_cat_id : [];

        $data = $request->only('shop_name', 'class_id', 'company_name', 'province_id', 'city_id', 'area_id',
            'street_id', 'address', 'zip_code', 'shop_logo', 'shop_banner', 'shop_phone', 'shop_keywords',
            'shop_description', 'is_recommend', 'is_own_shop', 'point_id', 'housing_id', 'floors_id', 'rel_cat_id_data',
            'shopRate', 'manage_fee', 'user_obtain_point', 'internal', 'shop_type', 'store_code','erp_storeCode','erp_posCode','is_push_erp','address','longitude','latitude');
        if (isset($data['rel_cat_id_data'])) {
            $data['rel_cat_id_data'] = is_array($data['rel_cat_id_data']) ? json_encode($data['rel_cat_id_data']) : $data['rel_cat_id_data'];
        }

        $shop = Shop::find($id);
        if (empty($shop)) {
            return $this->resFailed(701);
        }

        $msg_text = $shop->shop_name . "更新店铺数据";
        try {

            if (isset($data['shop_name'])) {
                $flag = Shop::where('shop_name', $data['shop_name'])->where('gm_id',$this->GMID)->where('id', '!=', $shop->id)->count();
                if ($flag) {
                    return $this->resFailed(701, '店铺名称已存在!');
                }
            }

            //修改为内部店铺即可选楼层
            /*if ($data['internal'] == '1') {
                if (!isset($data['floors_id']) || !$rel_cat_id) {
                    return $this->resFailed(701, '请选择关联品牌和楼层!');
                } else {
                    $rel_cat_id_data = $request['rel_cat_id_data'] ?? '';
                    $rel_cat_id_data = is_array($rel_cat_id_data) ? json_encode($rel_cat_id_data) : $rel_cat_id_data;
                    $data['rel_cat_id_data'] = $rel_cat_id_data;
                }
            }else{
                //场外不能选自营
                if($data['shop_type'] == 'flag'){
                    return $this->resFailed(701, '外场店铺不能选自营!');
                }
                //改为场外后删除关联的分类
                ShopClassRelations::where('shop_id', $shop->id)->delete();
                $data['floors_id'] = 0;
                $data['rel_cat_id_data'] = null;
            }*/

            //修改确认收货赠送积分的配置时，作相应校验
            if (isset($data['user_obtain_point'])) {
                if (!isset($data['user_obtain_point']['fee']) || $data['user_obtain_point']['fee'] <= 0) {
                    return $this->resFailed(701, '确认收货赠送积分的“金额”参数有误');
                }
                if (!isset($data['user_obtain_point']['point']) || $data['user_obtain_point']['point'] <= 0) {
                    return $this->resFailed(701, '确认收货赠送积分的“积分”参数有误');
                }
            }
            $shop->update($data);


            if ($rel_cat_id) {
                if (!is_array($rel_cat_id)) {
                    $rel_cat_id = [$rel_cat_id];
                }
                //先把已有的删除
                ShopClassRelations::where('shop_id', $shop->id)->delete();
                //为店铺分类关系表插入关系记录
                $inset_data = [];
                foreach ($rel_cat_id as $cat_id) {
                    $inset_data[] = [
                        'shop_id'  => $shop->id,
                        'class_id' => $cat_id,
                    ];
                }
                if ($inset_data) {
                    ShopClassRelations::insert($inset_data);
                }
            }

        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess();
    }

    /**
     * [updatePoint 更新积分设置]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function updatePoint(Request $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $shop = Shop::find($id);
        if (empty($shop)) {
            return $this->resFailed(701);
        }
        $data = $request->only('open_point_deduction','user_obtain_point');
        //修改确认收货赠送积分的配置时，作相应校验
        if (isset($data['user_obtain_point'])) {
            if (!isset($data['user_obtain_point']['fee']) || $data['user_obtain_point']['fee'] <= 0) {
                return $this->resFailed(701, '确认收货赠送积分的“金额”参数有误');
            }
            if (!isset($data['user_obtain_point']['point']) || $data['user_obtain_point']['point'] <= 0) {
                return $this->resFailed(701, '确认收货赠送积分的“积分”参数有误');
            }
        }
        try {
            $msg_text = $shop->shop_name . "更新店铺积分配置.".$data['open_point_deduction'];

            $shop->update($data);
        } catch (Exception $e) {
           //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess();
    }

    /**
     * 删除店铺
     *
     * @Author moocde <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $msg_text = "删除店铺数据";
        try {
            Shop::destroy($id);
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess();
    }


    /**
     * 关闭或者开启店铺
     *
     * @Author hfh_wind
     * @param ShopRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function actShop(ShopActRequest $request)
    {
        $request = $request->only('status', 'id');
        $id = $request['id'];
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        if ($request['status'] == "open") {
            $update_data['shop_state'] = '1';
            $msg = "开启";
        } else {
            $update_data['shop_state'] = '0';
            $msg = "关闭";
        }
        $shop = Shop::find($id);
        if (empty($shop)) {
            return $this->resFailed(701);
        }
        $msg_text = $shop['shop_name'] . "店铺" . $msg;

        DB::beginTransaction();
        try
        {
            //删除首页挂件缓存
            $page = 'index_fit';
            $cache_key = 'CONFIGITEMS_INDEX_PAGE_'.$page.'_GM_'.$this->GMID;
            Cache::forget($cache_key);

            //修改店铺状态为关闭
            $shop->update($update_data);
            //店铺关闭同时下架该店铺所有商品
            Goods::where('shop_id',$id)
                    ->where('goods_state',1)
                    ->update(['goods_state'=>0]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess();
    }


    /**
     * 创建店铺(此处关联管理员账号)
     *
     * @Author hfh
     * @param SellerAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createShop(ShopCreateRequest $request)
    {
        $rel_cat_id = isset($request->rel_cat_id) ? $request->rel_cat_id : [];
        $request = $request->only('shop_name', 'seller_name', 'is_own_shop', 'shop_type', 'floors_id',
            'rel_cat_id_data', 'internal', 'store_code','erp_storeCode','erp_posCode','is_push_erp','address','longitude','latitude');

        DB::beginTransaction();
        try {

            $sellerAccount = SellerAccount::where('username', 'like',
                '%' . $request['seller_name'] . '%')->where(['seller_type' => '0'])->first();
            if (empty($sellerAccount)) {
                return $this->resFailed(701, '商户名称错误,找不到关联的商家账号!');
            }

            $flag = ShopRelSeller::where('seller_id', $sellerAccount->id)->count();
            if ($flag) {
                return $this->resFailed(701, '该账号下已有店铺!');
            }

            $flag = Shop::where('shop_name', $request['shop_name'])->where('gm_id',$this->GMID)->count();
            if ($flag) {
                return $this->resFailed(701, '店铺名称已存在!');
            }

            $shop = new Shop();
            //修改为内部店铺即可选楼层
            if ($request['internal'] == '1') {
                if (!isset($request['floors_id']) || !$rel_cat_id) {
                    return $this->resFailed(701, '请选择有二级的商场分类!');
                } else {
                    $shop->floors_id = $request['floors_id'];

                    $rel_cat_id_data = $request['rel_cat_id_data'] ?? '';
                    $rel_cat_id_data = is_array($rel_cat_id_data) ? json_encode($rel_cat_id_data) : $rel_cat_id_data;
                    $shop->rel_cat_id_data = $rel_cat_id_data;
                }
            }else{
                //场外不能选自营
//                if($request['shop_type'] == 'flag'){
//                    return $this->resFailed(701, '外场店铺不能选自营!');
//                }
            }

            $shop->shop_name = $request['shop_name'];
            $shop->is_own_shop = $request['shop_type'] == 'flag' ? 1 : 0;
            $shop->status = 'successful';
            $shop->internal = $request['internal']??0;
            $shop->shop_type = $request['shop_type'];
            $shop->gm_id = $this->GMID;
            $shop->store_code = $request['store_code'] ?? '';
            $shop->erp_storeCode = $request['erp_storeCode'] ?? '';
            $shop->erp_posCode = $request['erp_posCode'] ?? '';
            $shop->is_push_erp = $request['is_push_erp']??1;//是否推送erp
            $shop->address = $request['address']??'';
            $shop->longitude = $request['longitude']??'';
            $shop->latitude = $request['latitude']??'';
            $shop->save();
            //记录到关联表
            $rel['shop_id'] = $shop->id;
            $rel['seller_id'] = $sellerAccount->id;
            $rel['shop_name'] = $request['shop_name'];
            $rel['gm_id'] = $this->GMID;

            ShopRelSeller::create($rel);


            if ($rel_cat_id) {
                if (!is_array($rel_cat_id)) {
                    $rel_cat_id = [$rel_cat_id];
                }
                //先把已有的删除
                ShopClassRelations::where('shop_id', $shop->id)->delete();
                //为店铺分类关系表插入关系记录
                $inset_data = [];
                foreach ($rel_cat_id as $cat_id) {
                    $inset_data[] = [
                        'shop_id'  => $shop->id,
                        'class_id' => $cat_id,
                    ];
                }
                if ($inset_data) {
                    ShopClassRelations::insert($inset_data);
                }
            }


            //新增创建店铺同时生成店铺分销配置
            $ShopAttr['shop_id'] = $shop->id;
            ShopAttr::create($ShopAttr);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            $this->adminlog("店铺创建", 0);
            return $this->resFailed(702, $e->getMessage());
        }

        //日志
        $this->adminlog($request['shop_name'] . "店铺创建", 1);

        return $this->resSuccess();
    }


    /**
     * 审核商家
     *
     * @Author hfh_wind
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function enterapply($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $pagedata['shopApplay'] = ShopInfo::where(['id' => $id])->first();
        $pagedata['seller'] = SellerAccount::where(['id' => $pagedata['shopApplay']->seller_id])->first();

        if (empty($pagedata)) {
            return $this->resFailed(414, '数据不存在!');
        }

        return $pagedata;
    }


    /**
     * 执行审核操作
     *
     * @Author hfh_wind
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function doExamine(ShopDoExamineRequest $request)
    {
        // active-未审核,locked-审核中,successful-审核通过,failing-审核驳回,finish-开店完成
        $postdata = $request->only('shop_id', 'status', 'reason');

        $updateinfo = [];

        try {
            $status = $postdata['status'];
            switch ($status) {
                case 'active':
                    return $this->resFailed(414, '非法操作!');
                    break;
                case 'locked':
                    $updateinfo['shop_state'] = 0;
                    $updateinfo['status'] = 'locked';
                    break;
                case 'successful':
                    $updateinfo['shop_state'] = 1;
                    $updateinfo['status'] = 'successful';
                    break;
                case 'failing':
                    $updateinfo['shop_state'] = 0;
                    $updateinfo['status'] = 'failing';
                    $updateinfo['reason'] = !empty($postdata['reason']) ? $postdata['reason'] : '';
                    break;
            }

            Shop::where(['id' => $postdata['shop_id']])->update($updateinfo);

        } catch (\Exception $e) {
            //日志
            $this->adminlog("店铺审核", 0);
            return $this->resFailed(702, $e->getMessage());
        }

        //日志
        $this->adminlog("店铺审核", 1);

        return $this->resSuccess([], '审核成功!');
    }

    /**
     * [listNoPage 无分页列表]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    public function listNoPage()
    {
        return $this->resSuccess(Shop::where('shop_state', 1)->get());
    }
    
    /**
     * 店铺列表导出
     *
     * @Author Huiho
     * @return \Illuminate\Http\JsonResponse
     */
    public function ExportList(Request $request)
    {
        $input_data = $request->all();
        $input_data['gm_id'] = $this->GMID;
        $repository = new ShopRepository();
        $sign = '';
        $isDown = true;
        $lists = $repository->search($input_data,$sign,$isDown);
        if (empty($lists))
        {
            return $this->resFailed(700);
        }
        foreach ($lists as $key => $value) {
            $num = DB::table('goods')->where('goods_state','1')->where('shop_id',$value['id'])->count();
            $lists[$key]['shop_goods_sum'] = $num;
            $lists[$key]['is_open'] = $value['shop_state'];
            $lists[$key]['shop_state'] = $value['shop_state'] ? '开启' : '关闭';
        }
        $filed = $repository->listFields();
        $return['order']['tHeader']= array_column($filed,'title'); //表头
        $head= array_column($filed,'dataIndex'); //表头字段
        foreach ($head as $key => $value) {
            if($value=='shop_state'){
                $head[$key] = 'shop_state_text';
            }
        }
        $return['order']['filterVal']= $head;
        $return['order']['list']= $lists; 
       // $sql = DB::table('shops as c')->leftJoin('shop_rel_sellers as a' , 'a.shop_id', '=', 'c.id' )->leftJoin('seller_accounts as b', 'a.seller_id', '=', 'b.id')->select('b.username','a.shop_name','a.shop_id')->where('c.gm_id' , $input_data['gm_id'])->groupBy('c.id');

       // if(isset($input_data['shop_id']))
       //  {
       //      $sql = $sql->where('c.id' ,$input_data['shop_id']);
       //  }

       //  $lists = $sql->get();

       //  if (empty($lists))
       //  {
       //      return $this->resFailed(700);
       //  }
        
       //  $filed = [
       //      ['key'=> 'shop_id','dataIndex' => 'shop_id', 'title' => 'ID'],
       //      ['key'=> 'shop_name','dataIndex' => 'shop_name', 'title' => '店铺名称'],
       //      ['key'=> 'account','dataIndex' => 'account', 'title' => '商家账户'],
       //  ];

       //  $return['order']['tHeader']= array_column($filed,'title'); //表头
       //  $return['order']['filterVal']= array_column($filed,'dataIndex'); //表头字段

       //  $return['order']['list']= $lists; //表头

        return $this->resSuccess($return);
    }


}
