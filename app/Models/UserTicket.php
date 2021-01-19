<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class UserTicket extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['ticket_source_text', 'status_text', 'mobile' ,'type_text'];

    public function getStatusTextAttribute()
    {
        $statusMap = [
            0 => '不可用',    // 不可用
            1 => '可用',    // 可用
            2 => '过期',    // 过期
        ];
        return $statusMap[$this->ticket_status] ??  '不可用';
    }

    public function getTicketSourceTextAttribute()
    {
        $sourceMap = [
            'kanjia' => '砍价活动',    // 砍价活动
            'choujiang' => '抽奖活动',    // 抽奖活动
        ];
        return isset($sourceMap[$this->ticket_source]) ? $sourceMap[$this->ticket_source] : '其他活动';
    }

    public function getTicketImageTextAttribute($value)
    {
        return $value ?: 'https://alphamember.oss-cn-shenzhen.aliyuncs.com/images/default/201912/27/beikengxiong.png';
    }

    public function getMobileAttribute()
    {
        $user = UserAccount::select('mobile')->find($this->user_id);
        return $user['mobile'] ?? '';
    }


    /**
     * 兑换劵类型
     * @Author hfh_wind
     * @return string
     */
    public function getTypeTextAttribute()
    {
        $text='';
        switch ($this->type) {
            case '0':
                $text="电影票";
                break;
            case '1':
                $text="有妖气体验卡";
                break;
            case '2':
                $text='学英语年卡';
                break;
        }
        return $text;
    }



}
