<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class TradeRewards extends Model
{
    //
    protected $guarded = [];


    protected $appends = ['shop_name','goods_infos','wx_info' ,'type_text'];



    /**
     * 追加店铺名称
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getShopNameAttribute()
    {
        $info = Shop::where('id',$this->shop_id)->select('shop_name')->first();
        return isset($info['shop_name'])?$info['shop_name']:'';
    }


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
    public function getTypeTextAttribute()
    {
        $res='';
        switch ($this->type) {
            case '0':
                $res = '平台分销';
                break;
            case '1':
                $res = '商家返利';
                break;
        }
        return $res;
    }
}
