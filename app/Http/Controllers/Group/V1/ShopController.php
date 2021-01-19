<?php

/**
 * @Author: swl
 * @Date:   2020-03-10 
 */
namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Requests\Platform\ShopActRequest;
use ShopEM\Http\Requests\Platform\ShopCreateRequest;
use ShopEM\Http\Requests\Platform\ShopRequest;
use ShopEM\Http\Requests\Platform\ShopDoExamineRequest;
use ShopEM\Models\SellerAccount;
use ShopEM\Models\Shop;
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
            'field' => $shopRepository->listShowFields('group'),
        ]);
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

        return $this->resSuccess($return);
    }
}
