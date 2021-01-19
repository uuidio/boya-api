<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class UserDepositCash extends Model
{
    protected $guarded = [];
    protected $appends = ['user_phone','status_text'];

    const STATUS_TEXTS = [
        'TO_VERIFY'     =>  '未审核',
        'VERIFIED'     =>  '已审核',
        'DENIED'     =>  '已驳回',
        'COMPELETE'     =>  '已同意',
    ];

    public function getUserPhoneAttribute()
    {
        $user = UserAccount::select('mobile')->where('id',$this->user_id)->first();

        return $user->mobile;
    }

    public function getStatusTextAttribute()
    {
        return self::STATUS_TEXTS[$this->status];
    }
}
