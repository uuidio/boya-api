<?php

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use ShopEM\Models\SpecialActivity;
use ShopEM\Models\SpecialActivityItem;
use ShopEM\Models\SpecialActivityApply;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Http\Requests\Seller\SpecialActivityRequest;

class SpecialActivityController extends BaseController
{
    /**
     * [lists 专题活动报名列表]
     * @Author mssjxzw
     * @param  Request $request [请求对象]
     * @return [type]           [description]
     */
    public function applyLists(Request $request)
    {
        $data = $request->all();
        $data['apply_time']['today'] = date('Y-m-d');
        $repository = new \ShopEM\Repositories\SpecialActivityRepository();
        $lists = $repository->listItems($request->all(),10);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * [actList 店铺报名历史列表]
     * @Author mssjxzw
     * @param  string  $value [description]
     * @return [type]         [description]
     */
    public function actList()
    {
        $size = request('size',10);
        $lists = SpecialActivityApply::where('shop_id',$this->shop->id)->orderBy('id', 'desc')->paginate($size);
        if (empty($lists)) {
            return $this->resFailed(700);
        }
        foreach ($lists as $key => $value) {
            $act_goods = [
                'data'  =>  $value->goods_info,
                'field' =>  [
                                ['key' => 'goods_image', 'dataIndex' => 'goods_image', 'title' => '商品主图','scopedSlots'=>['customRender'=>'goods_image']],
                                ['key' => 'goods_name', 'dataIndex' => 'goods_name', 'title' => '商品名称'],
                                ['key' => 'goods_price', 'dataIndex' => 'goods_price', 'title' => '商品价格'],
                                ['key' => 'discount', 'dataIndex' => 'discount', 'title' => '优惠数额'],
                            ],
            ];
            $lists['act_goods'] = $act_goods;
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => [
                ['dataIndex' => 'id', 'title' => 'ID'],
                ['dataIndex' => 'act_info.name', 'title' => '活动名称'],
                ['dataIndex' => 'act_info.type', 'title' => '活动类型'],
                ['dataIndex' => 'act_info.apply_time', 'title' => '报名时间'],
                ['dataIndex' => 'act_info.effective_time', 'title' => '生效时间'],
                ['dataIndex' => 'status', 'title' => '审核状态'],
            ],
        ]);
    }

    /**
     * [lists 专题活动生效列表]
     * @Author mssjxzw
     * @param  Request $request [请求对象]
     * @return [type]           [description]
     */
    public function useLists(Request $request)
    {
        $data = $request->all();
        $data['use_time']['today'] = date('Y-m-d');
        $repository = new \ShopEM\Repositories\SpecialActivityRepository();
        $lists = $repository->listItems($request->all(),10);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * [apply 报名活动]
     * @Author mssjxzw
     * @param  SpecialActivityRequest $request [description]
     * @return [type]                          [description]
     */
    public function apply(SpecialActivityRequest $request)
    {
        try {
            $apply = [
                'act_id'    => $request->act_id,
                'shop_id'   => $this->shop->id
            ];
            $act = SpecialActivity::find($request->act_id);
            SpecialActivityApply::create($apply);
            $apply_goods = $request->goods_info;
            foreach ($apply_goods as $key => $value) {
                $insert = [
                    'act_id'        => $request->act_id,
                    'shop_id'       => $this->shop->id,
                    'goods_id'      => $value->id,
                    'goods_name'    => $value->goods_name,
                    'goods_price'   => $value->goods_price,
                    'goods_image'   => $value->goods_image,
                ];
                switch ($act->type) {
                    case 1:
                        $insert['reduce_price'] = $request->discount;
                        $insert['act_price'] = $value->goods_price-$insert['reduce_price'];
                        $insert['discount'] = round($insert['act_price']/$value->goods_price);
                        break;
                    case 2:
                        $insert['discount'] = $request->discount;
                        $insert['act_price'] = $request->discount*$value->goods_price/100;
                        $insert['reduce_price'] = $value->goods_price-$insert['act_price'];
                        break;

                }
                SpecialActivityItem::create($insert);
            }
        } catch (Exception $e) {
            return $this->resFailed(414,$e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * [detail 专题活动详情]
     * @Author mssjxzw
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = SpecialActivity::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }
}
