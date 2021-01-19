<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class YiTianUserCard extends Model
{
    protected $guarded = [];
    protected $appends = ['gm_name','gm_address'];
    
    /**
     * 追加项目名称
     * @Author swl
     * @return string
     */
    public function getGmNameAttribute()
    {
        $platform_name = GmPlatform::where('gm_id', '=', $this->gm_id)->value('platform_name');

        return !empty($platform_name) ? $platform_name : '';
    }

     /**
     * 追加项目详细地址
     * @Author swl
     * @return string
     */
    public function getGmAddressAttribute()
    {
        $address = GmPlatform::where('gm_id', '=', $this->gm_id)->value('address');

        return !empty($address) ? $address : '';
    }



    public function getCardInfo($gm_id,$card_code='')
    {
        $img = env('APP_URL').'/images/card1.png';
        $cardData = ['card_type_text'=>'V卡','card_img'=>$img,'card_name'=>''];
        $card = [];
        if ($card_code) 
        {
            $card = self::where(['gm_id'=>$gm_id,'card_code'=>$card_code])->first();
        }
        if (!empty($card)) 
        {
            if (!empty($card->card_name)) {
                $cardData['card_type_text'] = $card->card_name;
            }
            if (!empty($card->card_img)) {
                $cardData['card_img'] = $card->card_img;
            }
             if (!empty($card->card_name)) {
                $cardData['card_name'] = $card->card_name;
            }
        }
        // 获取会员卡项目名称
        $gm_name = GmPlatform::where('gm_id', '=', $gm_id)->value('platform_name');
        $cardData['gm_name'] = $gm_name??'';
        return $cardData;
        # card_code card_type_text 
        # https://ytxspt.ytholidayplaza.com/images/card1.png
    }
}
