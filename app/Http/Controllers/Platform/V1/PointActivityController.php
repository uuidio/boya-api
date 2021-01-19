<?php
/**
 * @Filename        PointActivityController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Support\Facades\Redis;
use ShopEM\Models\Goods;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\PointActivityGoods;
use ShopEM\Models\YiTianUserCard;
use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\PointActivityRequest;
use ShopEM\Repositories\PointActivityRepository;
use ShopEM\Services\GroupService;
use ShopEM\Services\SecKillService;
use ShopEM\Services\Marketing\Activity;

class PointActivityController extends BaseController
{
    /**
     * [lists 积分专区商品列表]
     * @Author djw
     * @param  Request $request [请求对象]
     * @return [type]           [description]
     */
    public function lists(Request $request, PointActivityRepository $repository)
    {
        $data = $request->all();
        $data['orderby'] = 'created_at';
        $data['direction'] = 'desc';
        $data['per_page'] = isset($data['per_page']) ? $data['per_page'] : config('app.per_page');
        $data['gm_id'] = $this->GMID;
        $lists = $repository->search($data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        $type = 'normal';
        if ($this->GMID == GmPlatform::gmSelf())
        {
           $type = 'self';
        }

        $lists = $lists->toArray();
        foreach ($lists['data'] as $key => &$value)
        {
            if ($value['is_grade_limit'] > 0 && !empty($value['grade_limit'])) {
                $card_names = YiTianUserCard::where('gm_id',$this->GMID)->whereIn('card_code',$value['grade_limit'])->pluck('card_name');
                foreach ($card_names as $val) {
                    $name[] = $val;
                }
                $value['grade_text'] = implode(',', $name);
            }else{
                $value['grade_text'] = '不限制';
            }

        }
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields($type),
        ]);
    }

    /**
     * [saveData 保存参加积分专区的商品]
     * @Author djw
     * @param  Request $request [请求对象]
     * @return [type]           [description]
     */
    public function saveData(PointActivityRequest $request)
    {
        $data = $request->only('goods_id', 'point_price', 'point_fee', 'sort','point_class_id','buy_max','day_buy_max',
            'write_off_start','write_off_end','allow_after','active_start','active_end','activity_buy_max','is_grade_limit','grade_limit'
        );
        if ($data['point_price'] < 0) {
            return $this->resFailed(700, '金额不能小于0');
        }
        if ($data['point_fee'] <= 0) {
            return $this->resFailed(700, '积分不能小于0');
        }
        if (strtotime($data['active_start']) <= time()) {
            return $this->resFailed(700,'兑换时间要大于当前时间');
        }

        $date = date('Y-m-d H:i:s');
        $goods = Goods::find($data['goods_id']);
        if (!$goods) {
            return $this->resFailed(700);
        }
        $point_goods = PointActivityGoods::where('goods_id', $goods->id)
            ->where('active_end','>=',$data['active_start'])
            ->first();
        if ($point_goods && compareTime($data['active_end'],$point_goods['active_start']) >= 0) {
            return $this->resFailed(700, '无法添加，商品已在积分专区中!');
        }

        $secKillService = new SecKillService();
        if ($secKillService->actingSecKill($goods->id)) {
            return $this->resFailed(700, '无法添加，商品参加了秒杀活动!');
        }
        $groupService = new GroupService();
        if ($groupService->actingGroup($goods->id)) {
            return $this->resFailed(700, '无法添加，商品参加了团购活动!');
        }
        //店家营销活动的判断
        $actService = new Activity();
        if ($actService->actingAct($goods->id)) {
            return $this->resFailed(700, '无法添加，商品参加了营销活动!');
        }

        if(isset($data['is_grade_limit']))
        {
            if($data['is_grade_limit'] == 1)
            {
                if(!isset($data['grade_limit']))
                {
                    return $this->resFailed(702, '必须选择一种等级设置');
                }
                if ( empty($data['grade_limit']) || !is_array($data['grade_limit']) )
                {
                    return $this->resFailed(702, '必须选择一种等级设置');
                }
                if (!empty($data['grade_limit']))
                {
                    $data['grade_limit'] =  implode(',', $data['grade_limit']);
                }
            }
            else
            {
                $data['grade_limit'] = '';
            }
        }
        $msg_text="商品-".$goods['id']."-".$goods['goods_name']."添加积分专区";

        try {
            $data['goods_name'] = $goods->goods_name;
            $data['shop_id'] = $goods->shop_id;
            $data['goods_price'] = $goods->goods_price;
            $data['goods_image'] = $goods->goods_image;
            $data['gm_id'] = $this->GMID;
            PointActivityGoods::create($data);

            Goods::where('id',$goods->id)->update(['is_point_activity'=>1]);
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess();
    }

    public function update(PointActivityRequest $request)
    {
        $data = $request->only('id','goods_id', 'point_price', 'point_fee', 'sort','point_class_id','buy_max','day_buy_max',
            'write_off_start','write_off_end','allow_after','active_start','active_end','activity_buy_max','is_grade_limit' , 'grade_limit');
        if (!isset($data['id']) || $data['id'] <= 0) {
            return $this->resFailed(700,'参数id缺失');
        }
        $id = intval($data['id']);
        unset($data['id']);
        $detail = PointActivityGoods::find($id);
        if (empty($detail)) {
            return $this->resFailed(700);
        }
        if ($data['goods_id'] != $detail->goods_id) {
            return $this->resFailed(700,'不允许变更商品');
        }
        if (!empty($detail->active_start) && strtotime($detail->active_start) <= time()) {
            // unset($data);
            // $data = $request->only('id','goods_id','sort','point_class_id','buy_max','day_buy_max','is_grade_limit','grade_limit',);
            return $this->resFailed(700,'积分活动已开始不允许修改');
        }


        if(isset($data['is_grade_limit']))
        {
            if($data['is_grade_limit'] == 1)
            {
                if(!isset($data['grade_limit']))
                {
                    return $this->resFailed(702, '必须选择一种等级设置');
                }
                if (empty($data['grade_limit'])||!is_array($data['grade_limit']))
                {
                    return $this->resFailed(702, '必须选择一种等级设置');
                }
                if (!empty($data['grade_limit']))
                {
                    $data['grade_limit'] =  implode(',', $data['grade_limit']);
                }
            }
            else
            {
                $data['grade_limit'] = '';
            }
        }
        $goods = Goods::find($data['goods_id']);
        $msg_text="积分专区:商品-".$goods['id']."-".$goods['goods_name']."进行更新";
        try {
            $detail->update($data);

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
     * [detail 详情]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function detail($id='')
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $detail = PointActivityGoods::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess($detail);
    }
    /**
     * [delete 删除积分专区商品]
     * @Author mssjxzw
     * @param  integer $id [活动id]
     * @return [type]      [description]
     */
    public function delete(Request $request)
    {
        $id = $request->id;
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $goods = PointActivityGoods::find($id);
        if (!$goods) {
            return $this->resFailed(701, '此商品不在积分专区内');
        }
        $msg_text="商品-".$goods['id']."-".$goods['goods_name']."移除积分专区";
        try {
            PointActivityGoods::destroy($id);
            Goods::where('id',$goods->goods_id)->update(['is_point_activity'=>0]);
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 0);
        return $this->resSuccess();
    }
}
