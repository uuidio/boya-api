<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class IntegralBySelf extends Model
{
    //
    protected $guarded = [];

    const READY = 'ready';
    const SUCCESS = 'success';
    const REJECT = 'reject';


    public static $ProcessingStatus = [
        self::READY  => '待处理',
        self::SUCCESS     => '已通过',
        self::REJECT      => '未通过',
    ];

    protected $appends = [
        'status_text',
        'grade_name_text',
        'push_crm_text',
    ];

    public function getPushCrmTextAttribute()
    {
        switch ($this->push_crm) {
            case '1':
                $text = 'CRM推送中';
                break;
            case '2':
                $text = 'CRM推送成功';
                break;
            case '3':
                $text = 'CRM推送失败';
                break;
            default:
                $text = 'CRM未推送';
                break;
        }
        return $text;
    }

    public function getStatusTextAttribute()
    {
        return self::$ProcessingStatus[$this->attributes['status']];
    }

    public function getStatusAttribute($value)
    {
        if ($value == 'reject')
        {
            return '2';
        }
        elseif($value == 'success')
        {
            return '1';
        }
        else
        {
            return '0';
        }

    }

    public function getGradeNameTextAttribute()
    {
       return  YiTianUserCard::where('card_code',$this->attributes['grade_name'])->value('card_name') ?? '--' ;
    }





}
