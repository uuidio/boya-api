<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class GroupsUserJoin extends Model
{
    //
    protected $guarded = [];
//    protected $appends = ['get_tid'];
//
//
//    /**
//     *
//     *
//     * @Author hfh_wind
//     * @return mixed
//     */
//    public function getGetTidAttribute()
//    {
//
//        $groupUser = TradePaybill::where(['payment_id' => $this->payment_id])->select('tid')->first();
//        $tid = '';
//        if (!empty($groupUser)) {
//            $tid = $groupUser['tid'];
//        }
//        return $tid;
//    }
}
