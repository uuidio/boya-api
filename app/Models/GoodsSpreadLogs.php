<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsSpreadLogs extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['goods_infos','wx_info' ,'status_text' ,'remaining_time'];


    /**
     * 追加商品信息
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getGoodsInfosAttribute()
    {
        $info = Goods::where('id',$this->goods_id)->select('goods_name')->first();
        return isset($info['goods_name'])?$info['goods_name']:'';
    }



    /**
     * 追加微信会员信息
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getWxInfoAttribute()
    {
        $info = WxUserinfo::where('user_id',$this->user_id)->select('nickname','headimgurl')->first();
        return !empty($info)?$info:'';
    }




    /**
     * 追加状态信息
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getStatusTextAttribute()
    {
        $res='';
        switch ($this->status) {
            case '0':
                $res = '未购买';
                break;
            case '1':
                $res = '已过期';
                break;
            case '2':
                $res = '已购买';
                break;
            case '3':
                $res = '已退款';
                break;
        } 
        return $res;
    }


    /**
     * 返回剩余时间
     * @Author hfh_wind
     * @return int
     */
    public function getRemainingTimeAttribute()
    {
        $time=$this->diffBetweenTwoDays($this->created_at,7);
        return $time;
    }



    public function diffBetweenTwoDays ($day)
    {
        $second1 = strtotime($day);
        $second1 = strtotime("+7day",$second1); //创建时间的七天后时间戳
        $second2 = time(); //当前时间

        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        $res=floor($second1 - $second2) / 86400;
        return intval(round($res));
    }
}
