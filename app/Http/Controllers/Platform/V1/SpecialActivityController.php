<?php

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Models\SpecialActivity;
use ShopEM\Models\SpecialActivityItem;
use ShopEM\Models\SpecialActivityApply;
use ShopEM\Models\SpecialActivitySend;
use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\SpecialActivityRequest;
use ShopEM\Http\Requests\Platform\SpecialActivityReviewRequest;

class SpecialActivityController extends BaseController
{
    /**
     * [lists 专题活动列表]
     * @Author mssjxzw
     * @param  Request $request [请求对象]
     * @return [type]           [description]
     */
    public function lists(Request $request)
    {
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
     * [saveData 保存专题活动]
     * @Author mssjxzw
     * @param  Request $request [请求对象]
     * @return [type]           [description]
     */
    public function saveData(SpecialActivityRequest $request)
    {
        $data = $request->only('id', 'name', 'desc', 'type', 'range', 'limit', 'img', 'star_apply', 'end_apply', 'star_time', 'end_time', 'shop_type', 'goods_class');
        if (isset($data['id']) && $data['id']) {
            $id = $data['id'];
            unset($data['id']);
            $act = SpecialActivity::find($id);
            if (!$act) {
                return $this->resFailed(701,'没有此活动');
            }
            $now = time();
            $star = strtotime($act->star_time);
            $stop = strtotime($act->end_time);
            if ($now > $star && $now < $stop) {
                return $this->resFailed(701,'该活动已生效，不能更改');
            }
            $act->name = $data['name'];
            $act->desc = $data['desc']??'';
            $act->type = $data['type'];
            $act->range = $data['range'];
            $act->limit = $data['limit']??0;
            $act->img = $data['img']??'';
            $act->star_apply = $data['star_apply'];
            $act->end_apply = $data['end_apply'];
            $act->star_time = $data['star_time'];
            $act->end_time = $data['end_time'];
            $act->shop_type = $data['shop_type']??[];
            $act->goods_class = $data['goods_class']??[];
            $act->save();
            if ($request->filled('send_goods')) {
                foreach ($request->send_goods as $key => $value) {
                    if (is_string($value)) {
                        $value = json_decode($value);
                    }
                    $in[] = $value->id;
                    $send = SpecialActivitySend::where([['act_id','=',$id],['goods_id','=',$value->id]])->first();
                    if ($send) {
                        $send->goods_id = $value->id;
                        $send->goods_name = $value->goods_name;
                        $send->goods_price = $value->goods_price;
                        $send->goods_image = $value->goods_image;
                        $send->num = $value->num;
                        $send->save();
                    }else{
                        $send_goods = [
                            'act_id'        =>  $id,
                            'goods_id'      =>  $value->id,
                            'goods_name'    =>  $value->goods_name,
                            'goods_price'   =>  $value->goods_price,
                            'goods_image'   =>  $value->goods_image,
                            'num'           =>  $value->num,
                            'gm_id'         =>  $this->GMID,
                        ];
                        SpecialActivitySend::create($send_goods);
                    }
                }
                SpecialActivitySend::where('act_id',$id)->whereNotIn('goods_id',$in)->delete();
            }
        }else{
            $data['gm_id'] = $this->GMID;
            $act = SpecialActivity::create($data);
            if ($request->filled('send_goods')) {
                foreach ($request->send_goods as $key => $value) {
                    if (is_string($value)) {
                        $value = json_decode($value);
                    }
                    $send_goods = [
                        'act_id'        =>  $act->id,
                        'goods_id'      =>  $value->id,
                        'goods_name'    =>  $value->goods_name,
                        'goods_price'   =>  $value->goods_price,
                        'goods_image'   =>  $value->goods_image,
                        'num'           =>  $value->num,
                        'gm_id'         =>  $this->GMID,
                    ];
                    SpecialActivitySend::create($send_goods);
                }
            }
        }
        return $this->resSuccess();
    }

    /**
     * [detail 专题活动详情]
     * @Author mssjxzw
     * @param  integer $id [活动id]
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

    /**
     * [delete 删除专题活动]
     * @Author mssjxzw
     * @param  integer $id [活动id]
     * @return [type]      [description]
     */
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $coupon = SpecialActivity::find($id);
        if (!$coupon) {
            return $this->resFailed(701,'没有此优惠券');
        }
        $now = time();
        $star = strtotime($coupon->start_at);
        $stop = strtotime($coupon->end_at);
        if ($now > $star && $now < $stop) {
            return $this->resFailed(701,'该优惠券已生效，不能删除');
        }
        try {
            SpecialActivity::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * [lists 专题活动报名列表]
     * @Author mssjxzw
     * @param  Request $request [请求对象]
     * @return [type]           [description]
     */
    public function applyLists(Request $request)
    {
        $size = request('size',10);
        $lists = SpecialActivityApply::where('act_id',$request->act_id)->orderBy('status', 'asc')->paginate($size);
        if (empty($lists)) {
            return $this->resFailed(700);
        }
        foreach ($lists as $key => $value) {
            $apply_goods = [
                'data'  =>  $value->goods_info,
                'field' =>  [
                                ['key' => 'goods_image', 'dataIndex' => 'goods_image', 'title' => '商品主图','scopedSlots'=>['customRender'=>'goods_image']],
                                ['key' => 'goods_name', 'dataIndex' => 'goods_name', 'title' => '商品名称'],
                                ['key' => 'goods_price', 'dataIndex' => 'goods_price', 'title' => '商品价格'],
                            ],
            ];
            switch ($value->act_info->type) {
                case 1:
                    $apply_goods['field'][] = ['key' => 'reduce_price', 'dataIndex' => 'reduce_price', 'title' => '优惠'];
                    break;
                case 2:
                    $apply_goods['field'][] = ['key' => 'discount', 'dataIndex' => 'discount', 'title' => '折扣'];
                    break;
            }
            $lists['apply_goods'] = $apply_goods;
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => [
                ['dataIndex' => 'id', 'title' => 'ID'],
                ['dataIndex' => 'shop_info.shop_name', 'title' => '店铺名称'],
                ['dataIndex' => 'shop_info.shop_type_text', 'title' => '店铺类型'],
                ['dataIndex' => 'status', 'title' => '审核状态'],
            ],
        ]);
    }

    /**
     * [review 专题活动报名审核]
     * @Author mssjxzw
     * @param  SpecialActivityReviewRequest $request [description]
     * @return [type]                                [description]
     */
    public function review(SpecialActivityReviewRequest $request)
    {
        $apply = SpecialActivityApply::find($request->id);
        if (!$apply) {
            return $this->resFailed(500, '无此报名记录');
        }
        $status = floor($request->status);
        if ($request->status > 0 && $request->status < 3) {
            try {
                $apply->status = $status;
                $apply->save();
                foreach ($apply->goods_info as $key => $value) {
                    SpecialActivityItem::where('id',$value->id)->update(['status'=>$status]);
                }
            } catch (\Exception $e) {
                return $this->resFailed(701, $e->getMessage());
            }
            return $this->resSuccess();
        }
    }
}
