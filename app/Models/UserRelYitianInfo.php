<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class UserRelYitianInfo extends Model
{
    protected $guarded = [];
    protected $appends = ['gm_name'];
    
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

    public function getSelfPoint($mobile)
    {
        $point = 0;
        $gm_id = GmPlatform::gmSelf();
        if ($gm_id) {
            $selfService = new \ShopEM\Services\YitianGroupServices($gm_id);
            $point = $selfService->updateUserRewardTotal($mobile);
        }
        return $point;
    }
}
