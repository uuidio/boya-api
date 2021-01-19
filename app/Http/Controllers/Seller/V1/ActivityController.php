<?php
/**
 * @Filename    商家端活动控制器
 *
 * @Copyright   Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License     Licensed <http://www.shopem.cn/licenses/>
 * @authors     Mssjxzw (mssjxzw@163.com)
 * @date        2019-03-19 15:16:03
 * @version     V1.0
 */
namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Http\Requests\Seller\ActivityRequest;
use ShopEM\Repositories\ActivityRepository;
use Illuminate\Http\Request;
use ShopEM\Models\Activity;

class ActivityController extends BaseController
{
    /**
     * 活动列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param CouponRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request)
    {
        $data = $request->all();
        $data['shop_id'] = $this->shop->id;
        $repository = new \ShopEM\Repositories\ActivityRepository();
        $lists = $repository->listItems($data,10);
        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 保存活动
     *
     * @Author moocde <mo@mocode.cn>
     * @param CouponRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveData(ActivityRequest $request)
    {
        $data = $request->only('id', 'shop_id', 'name', 'desc', 'channel', 'is_use_point', 'status', 'reason', 'type', 'rule', 'user_type', 'goods_class', 'is_bind_goods', 'is_bind_shop', 'limit_shop', 'limit_goods', 'star_time', 'end_time');
        $data['shop_id'] = $this->shop->id;
        $data['gm_id'] = $this->GMID;
        $model = new Activity();
        $res = $model->saveData($data);
        if ($res['code']) {
            return $this->resFailed(701,$res['msg']);
        }
        return $this->resSuccess();
    }

    /**
     * 活动详情
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

        $detail = Activity::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }
        if ($detail->shop_id != $this->shop->id) {
            return $this->resFailed(700);
        }
        if ($detail->is_bind_goods > 0) {
            $detail['limit_goods'] = \ShopEM\Models\ActivityGoods::where('act_id',$detail->id)->get();
        }else{
            $detail['limit_goods'] = null;
        }

        return $this->resSuccess($detail);
    }

    /**
     * 删除活动
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
        $activity = Activity::find($id);
        if (!$activity) {
            return $this->resFailed(701,'没有此活动');
        }
        if ($activity->shop_id != $this->shop->id) {
            return $this->resFailed(700);
        }

        $now = time();
        $star = strtotime($activity->star_time);
        $stop = strtotime($activity->end_time);
        if ($now > $star && $now < $stop && !in_array($activity['status'], [0,4])) {
            return $this->resFailed(701,'该活动已生效，不能删除');
        }

        try {
            Activity::destroy($id);
            \ShopEM\Models\ActivityGoods::where('act_id',$id)->delete();
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 终止活动
     *
     * @Author swl
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function stop($id = 0){
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $activity = Activity::find($id);
        if (!$activity) {
            return $this->resFailed(701,'没有此活动');
        }

        try{
            Activity::where('id',$id)->update(['status'=>3]);

        } catch(\Exception $e){
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
    }
}
