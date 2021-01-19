<?php
/**
 * @Filename    平台端活动控制器
 *
 * @Copyright   Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License     Licensed <http://www.shopem.cn/licenses/>
 * @authors     Mssjxzw (mssjxzw@163.com)
 * @date        2019-03-19 15:16:03
 * @version     V1.0
 */
namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\ActivityRequest;
use Illuminate\Http\Request;
use ShopEM\Models\Shop;
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
        $repository = new \ShopEM\Repositories\ActivityRepository();
        $data = $request->all();
        $data['gm_id'] = $this->GMID;
        $lists = $repository->listItems($data, 10);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        $lists = $lists->toArray();
        foreach ($lists['data'] as $key => $value) {
            $lists['data'][$key]['shop_name'] = Shop::where('id',$value['shop_id'])->value('shop_name');
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields('platform'),
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
        $data = $request->only('id', 'name', 'desc', 'channel', 'status', 'reason', 'type', 'rule', 'user_type',
            'goods_class', 'is_bind_goods', 'is_bind_shop', 'limit_shop', 'limit_goods', 'star_time', 'end_time');
        $msg_text="创建活动" . $data['name'];
        try {
            $model = new Activity();
            $res = $model->saveData($data);
            if ($res['code']) {
                return $this->resFailed(701, $res['msg']);
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
        if ($detail->is_bind_goods > 0) {
            $detail['limit_goods'] = \ShopEM\Models\ActivityGoods::where('act_id', $detail->id)->get();
        } else {
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
            return $this->resFailed(701, '没有此活动');
        }
        $now = time();
        $star = strtotime($activity->start_time);
        $stop = strtotime($activity->end_time);
        if ($now > $star && $now < $stop) {
            return $this->resFailed(701, '该活动已生效，不能删除');
        }
        $msg_text="删除活动" . $activity['name'];
        try {
            Activity::destroy($id);
            \ShopEM\Models\ActivityGoods::where('act_id', $id)->delete();


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
     * [check 审核活动]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function check(Request $request)
    {
        $data = $request->only('id', 'status', 'reject_reason');
        $check = checkInput($data, 'check', 'activity');
        if ($check['code']) {
            return $this->resFailed(414, $check['msg']);
        }
        $activity = Activity::find($data['id']);
        //日志
        $msg_text="审核活动-".$activity['id']."-". $activity['name'];
        try {

            if (!$activity) {
                return $this->resFailed(701, '没有此活动');
            }
            if ($data['status'] == 1) {
                $now = time();
                $star = strtotime($activity->star_time);
                $stop = strtotime($activity->end_time);
                if ($now > $stop) {
                    return $this->resFailed(701, '活动已过期，不能通过');
                }
            }

            //驳回的时候才需要驳回原因
            if ($data['status'] == 4) {
                $activity->reason = isset($data['reject_reason']) ? $data['reject_reason'] : null;
            }
            $activity->status = $data['status'];
            $activity->save();

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
     * 活动修改活动名
     *
     * @Author huiho <429294135@qq.com>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function editName(Request $request)
    {
        $data = $request->only('id', 'shop_id', 'name');

        //检验数据
        if ($data['id'] <= 0) {
            return $this->resFailed(414,'活动ID不能为空');
        }

        if ($data['shop_id'] <= 0) {
            return $this->resFailed(414,'店铺ID不能为空');
        }

        if(Activity::where('id', $data['id'])->value('shop_id')!=$data['shop_id'])
        {
            $this->adminlog('修改失败', 0);
            return $this->resFailed(414,'修改失败,信息不匹配');
        }

        //更改数据
        $Activity = new Activity();
        $vail = $Activity->editName($data);
        if($vail){
            $this->adminlog('修改成功', 1);
            return $this->resSuccess('修改成功');
        }
        $this->adminlog('修改失败', 0);
        return $this->resFailed(414,'修改失败');
    }

    /**
     * 中止活动
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stop(Request $request)
    {
        $id = $request->id;

        //检验数据
        if ($id <= 0) {
            return $this->resFailed(414,'活动ID不能为空');
        }

        $detail = Activity::find($id);

        if (empty($detail)) {
            return $this->resFailed(701, '没有此活动');
        }

        $date = date('Y-m-d H:i:s');
        if ($detail->status != 1 || $detail->star_time > $date || $detail->end_time <= $date) {
            return $this->resFailed(701, '无需中止');
        }

        $msg_text="中止活动-".$id."-". $detail->name;

        try
        {
            $detail->status = 3;
            $detail->save();
            $this->adminlog($msg_text, 1);
        }
        catch (\Exception $e)
        {
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701,'修改失败');
        }

        return $this->resSuccess('修改成功');
    }

}
