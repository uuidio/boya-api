<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Activity extends Model
{
	protected $table = 'activities';
    protected $guarded = [];
    protected $appends = ['limit_goods','send_goods', 'type_text', 'status_text','shop_name'];

    public function getTypeTextAttribute()
    {
        $typeMap = [
            1 => '满减',    // 减券
            2 => '满折',  // 满折
            3 => '满赠',  // 满赠
            4 => '满X件Y折',  // 满X件Y折
            4 => '限时特价',  // 限时特价
        ];
        return isset($typeMap[$this->type]) ? $typeMap[$this->type] : '';
    }

    public function getStatusTextAttribute()
    {
        $statusMap = [
            0 => '未审核',    // 待审核
            1 => '通过',    // 通过
            2 => '已生效',  // 已生效
            3 => '中止',  // 中止
            4 => '驳回',  // 驳回
        ];
        return isset($statusMap[$this->status]) ? $statusMap[$this->status] : '';
    }

    /**
     * [saveData 保存数据]
     * @Author mssjxzw
     * @param  [type]  $data [要保存的数据]
     * @return [type]        [状态码加信息]
     *
     * $data['type']: 1(满减),2(满折),3(满赠),4(满X件Y折),5(限时特价)
     */
    public function saveData($data)
    {
        //活动规则的有效性判断
        $rule = array_values($data['rule']);
        foreach ($rule as $k => $v){
            if (is_object($v)) {
                $v = get_object_vars($v);
            }
            if ((isset($rule[$k+1]['condition']) && $v['condition'] >= $rule[$k+1]['condition']) || $v['condition'] <= 0) {
                return ['code'=>1,'msg'=>'活动规则不规范'];
            }
            if ($data['type'] == 2) {
                if ($v['num'] <= 0 || $v['num'] > 100) {
                    return ['code'=>1,'msg'=>'活动规则不规范'];
                }
            } else {
                if ($v['condition'] <= $v['num'] || $v['num'] <= 0) {
                    return ['code'=>1,'msg'=>'活动规则不规范'];
                }
            }
        }

    	//数据转换组装
    	if (isset($data['limit_goods'])) {
	    	$goods = $data['limit_goods'];
	    	unset($data['limit_goods']);
    	}
        if (isset($data['send_goods'])) {
            $send = $data['send_goods'];
            unset($data['send_goods']);
        }
        //存储事务
    	DB::beginTransaction();
        try {
	        if (isset($data['id']) && $data['id']) {
	            $id = $data['id'];
	            unset($data['id']);
	            $activity = $this->find($id);
	            if (!$activity) {
	                return ['code'=>1,'msg'=>'没有此活动'];
	            }
                if ($activity->status == 2) {
                    return ['code'=>1,'msg'=>'该活动已生效，不能更改'];
                }
                foreach ($data as $key => $value) {
                    $activity->$key = $value;
                }
                $activity->save();
	        }else{
                if (!isset($data['user_type']) || !$data['user_type']) {
                    $data['user_type'] = 'all';
                }
	            $res = $this->create($data);
	            $id = $res->id;
	        }
            if (isset($goods)) {
                $this->saveSubTable($goods,$id,$data['shop_id']);
            }
            if (isset($send)) {
                $this->saveSend($send,$id);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        	return ['code'=>1,'msg'=>$e->getMessage()];
        }
        return ['code'=>0,'msg'=>''];
    }

    /**
     * [saveSubTable 保存副表]
     * @Author mssjxzw
     * @param  [array]  $data   [数据]
     * @param  [int]  $act_id [活动id]
     * @return [type]          [description]
     */
    private function saveSubTable($data,$act_id,$shop_id)
    {
        $service = new \ShopEM\Services\Marketing\Activity();
        $in = [];
        foreach ($data as $key => $value) {
            $in[] = $value['id'];
            $sub = ActivityGoods::where([['act_id','=',$act_id],['goods_id','=',$value['id']]])->first();
            if ($sub) {
                if ($sub->goods_id != $value['id']) {
                    $check = $service->checkGoods($value['id'], true);
                    if ($check['code']) {
                        $msg = $value['goods_name'].':'.$check['msg'];
                        throw new \Exception($msg);
                    }
                }
                $sub->goods_id = $value['id'];
                $sub->goods_name = $value['goods_name'];
                $sub->goods_price = $value['goods_price'];
                $sub->goods_image = $value['goods_image'];
                $sub->save();
            }else{
                $check = $service->checkGoods($value['id'], true);
                if ($check['code']) {
                    $msg = $value['goods_name'].':'.$check['msg'];
                    throw new \Exception($msg);
                }
                $sub = [
                    'act_id'        =>  $act_id,
                    'goods_id'      =>  $value['id'],
                    'goods_name'    =>  $value['goods_name'],
                    'goods_price'   =>  $value['goods_price'],
                    'goods_image'   =>  $value['goods_image'],
                ];
                ActivityGoods::create($sub);
            }
        }
        ActivityGoods::where('act_id',$act_id)->whereNotIn('goods_id',$in)->delete();
    }

    /**
     * [saveSend 保存赠送商品]
     * @Author mssjxzw
     * @param  [array]  $send   [赠送商品数据]
     * @param  [int]  $act_id [活动id]
     * @return [type]          [description]
     */
    public function saveSend($send,$act_id)
    {
        $in = [];
        foreach ($send as $key => $value) {
            if (is_string($value)) {
                $value = json_decode($value,true);
            }
            $in[] = $value['id'];
            $send = ActivitySendGoods::where([['act_id','=',$act_id],['goods_id','=',$value['id']]])->first();
            if ($send) {
                $send->goods_id = $value['id'];
                $send->goods_name = $value['goods_name'];
                $send->goods_price = $value['goods_price'];
                $send->goods_image = $value['goods_image'];
                $send->num = $value['num'];
                $send->save();
            }else{
                $send_goods = [
                    'act_id'        =>  $act_id,
                    'goods_id'      =>  $value['id'],
                    'goods_name'    =>  $value['goods_name'],
                    'goods_price'   =>  $value['goods_price'],
                    'goods_image'   =>  $value['goods_image'],
                    'num'           =>  $value['num'],
                ];
                ActivitySendGoods::create($send_goods);
            }
        }
        ActivitySendGoods::where('act_id',$act_id)->whereNotIn('goods_id',$in)->delete();
    }


    public function getLimitGoodsAttribute()
    {
        if ($this->is_bind_goods == 1) {
            return ActivityGoods::where('act_id',$this->id)->get();
        }else{
            return [];
        }
    }


    public function getSendGoodsAttribute()
    {
        if ($this->type == 3 || $this->type == 4) {
            return ActivitySendGoods::where('act_id',$this->id)->get();
        }else{
            return [];
        }
    }

    /**
     * *****  rule   ****
     */

    public function getRuleAttribute($value)
    {
        $data = explode(';', $value);
        foreach ($data as $k => $v) {
            $x = explode('-', $v);
            $i['condition'] = $x[0];
            $i['num'] = $x[1];
            $out[] = $i;
        }
        return $out;
    }

    public function setRuleAttribute($value)
    {
        foreach ($value as $k => $v) {
            if (is_object($v)) {
                $v = get_object_vars($v);
            }
            $rules[] = $v['condition'].'-'.$v['num'];
        }
        $this->attributes['rule'] = implode(';', $rules);
    }

    /**
     * *****  limit_shop   ****
     */

    // public function getLimitShopAttribute($value)
    // {
    //     if ($value) {
    //         $ids = explode(',', $value);
    //         $model = new \ShopEM\Models\Shop();
    //         return $model->whereIn('id',$ids)->get();
    //     }
    // }

    public function setLimitShopAttribute($value)
    {
        foreach ($value as $k => $v) {
            $ids[] = $v['id'];
        }
        $this->attributes['limit_shop'] = implode(',',$ids);
    }

    /**
     * *****  user_type   ****
     */

    public function getUserTypeAttribute($value)
    {
        if ($value == 'all') {
            return '全部';
        }else{
            return $value;
        }
    }
    public function setUserTypeAttribute($value)
    {
        if ($value !== 'all' && is_array($value)) {
            $this->attributes['user_type'] = implode(',', $value);
        }elseif (is_string($value)) {
            $this->attributes['user_type'] = $value;
        }else{
            $this->attributes['user_type'] = '';
        }
    }


    /**
     * *****  channel   ****
     */
    public function setChannelAttribute($value)
    {
        if ($value !== 'all') {
            $this->attributes['channel'] = implode(',', $value);
        }
    }

    public function getChannelAttribute($value)
    {
        if ($value == 'all') {
            return '全部';
        }else{
            return $value;
        }
    }

    /**
     * *****  time   *****
     */
    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = Carbon::parse($value)->toDateTimeString();
    }

    public function setEndTimeAttribute($value)
    {
        $this->attributes['end_time'] = Carbon::parse($value)->toDateTimeString();
    }

    /**
     * 活动修改活动名
     *
     * @Author huiho <429294135@qq.com>
     */
    public function editName($params)
    {
        if(empty($params))
        {
            return false;
        }
        try
        {
            $this->where('id',$params['id'])
                ->where('shop_id',$params['shop_id'])
                ->update(['name'=>$params['name']]);
            return true;
        }
        catch (\Exception $e)
        {
            //日志
            return false;
        }

    }

    // 追加店铺名称
    public function getShopNameAttribute(){
        $shop_name = Shop::find($this->shop_id);
        return $shop_name['shop_name']??'';
    }

}
