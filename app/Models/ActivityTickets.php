<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityTickets extends Model
{
    //
    protected $guarded = [];
    protected $appends = ['user_name', 'status_text', 'type_text','ticket_code_encode' ,'ticket_type_text'];


    /**
     * 追加会员信息
     * @Author hfh_wind
     * @return string
     */
    public function getUserNameAttribute()
    {
        $info = UserAccount::where('id', $this->user_id)->select('mobile')->first();

        return !empty($info) ? $info['mobile'] : '';
    }


    /**
     * 追加下兑换状态
     * @Author hfh_wind
     * @return string
     */
    public function getStatusTextAttribute()
    {
        return $this->status ? '已兑换' : '未兑换';
    }


    /**
     * 追加应用类型说明
     * @Author hfh_wind
     * @return array
     */
    public function getTypeTextAttribute()
    {
        $text = '';
        if ($this->type == 'kanjia') {
            $text = '砍价';
        } elseif ($this->type == 'choujiang') {
            $text = '转盘抽奖';
        }
        return $text;
    }


    /**
     * 列表加密
     * @Author hfh_wind
     * @return array
     */
    public function getTicketCodeEncodeAttribute()
    {
        $res=substr_replace($this->ticket_code,'****',2,7);
        return $res;
    }



    /**
     * 追加票劵类型说明
     * @Author hfh_wind
     * @return array
     */
    public function getTicketTypeTextAttribute()
    {

        $text = '';
        switch ($this->ticket_type) {
            case 0:
                $text = '电影票';
                break;
            case 1:
                $text = '有妖气体验卡';
                break;
            case 2:
                $text = '学英语年卡';
                break;
        }
        return $text;
    }
}
