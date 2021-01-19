<?php
/**
 * @Filename        RateTraderate.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class RateTraderate extends Model
{
    protected $table = 'rate_traderate';
    protected $guarded = [];

    protected $appends = ['result_text', 'user_name', 'shop_name', 'reply_date', 'appeal_status_text', 'appeal_date', 'appeal_again_text', 'appeal', 'append', 'result_star', 'headimgurl', 'appeal_content', 'reject_reason','gm_name'];

    public static $resultMap = [
        'good' => '好评',
        'neutral' => '中评',
        'bad' => '差评',
    ];
    public static $appealStatusMap = [
        'NO_APPEAL' => '未申诉',
        'WAIT' => '等待批准',
        'REJECT' => '申诉驳回',
        'SUCCESS' => '申诉成功',
        'CLOSE' => '申诉关闭',
    ];
    public static $appealAgainMap = [
        0 => '第一次申诉',
        1 => '第二次申诉',
    ];

    public function getResultStarAttribute()
    {
        switch ($this->result) {
            case 'good':
                $result_star = 5;
                break;
            case 'neutral':
                $result_star = 3;
                break;
            case 'bad':
                $result_star = 1;
                break;
            default:
                $result_star = 5;
        }
        return $result_star;
    }

    public function getResultTextAttribute()
    {
        return self::$resultMap[$this->result];
    }

    public function getAppealStatusTextAttribute()
    {
        return self::$appealStatusMap[$this->appeal_status];
    }

    public function getUserNameAttribute()
    {
        $nickname = '匿名';
        if (!$this->anony) {
            $user = UserAccount::find($this->user_id);
            if ($user['openid']) {
                $info = WxUserinfo::select('nickname')->where('openid',$user['openid'])->first();
                $nickname = isset($info['nickname']) ? $info['nickname'] : ($user['mobile'] ? substr_replace($user['mobile'], '****',3, 4) : '');
            }
        }
        return $nickname;
    }

    public function getHeadimgurlAttribute()
    {
        $headimgurl = '';
        $user = UserAccount::find($this->user_id);
        if ($user['openid']) {
            $info = WxUserinfo::select('headimgurl')->where('openid',$user['openid'])->first();
            $headimgurl = isset($info['headimgurl']) ? $info['headimgurl'] : '';
        }
        return $headimgurl;
    }

    public function getShopNameAttribute()
    {
        return Shop::find($this->shop_id)['shop_name'] ?: '已被删除';
    }

    public function getReplyDateAttribute()
    {
        return $this->reply_time ? date('Y-m-d H:i:s', $this->reply_time) : null;
    }

    public function getAppealDateAttribute()
    {
        return $this->appeal_time ? date('Y-m-d H:i:s', $this->appeal_time) : null;
    }

    public function getAppealAgainTextAttribute()
    {
        return $this->appeal_status != 'NO_APPEAL' ? self::$appealAgainMap[$this->appeal_again] : '';
    }

    public function getAppealAttribute()
    {
        if ($this->appeal_status != 'NO_APPEAL') {
            return RateAppeal::where('rate_id', $this->id)->first() ?: null;
        }
        return null;
    }

    public function getAppendAttribute()
    {
        if ($this->is_append) {
            return RateAppend::where('rate_id', $this->id)->first() ?: null;
        }
        return null;
    }

    public function getRatePicAttribute($ratePic)
    {
        return $ratePic ? json_decode($ratePic, true) : $ratePic;
    }

    public function getAppealContentAttribute()
    {
        if ($this->appeal_status != 'NO_APPEAL') {
            $appeal =  RateAppeal::select('content')->where('rate_id', $this->id)->first();
            return isset($appeal['content']) ? $appeal['content'] : null;
        }
        return null;
    }

    public function getRejectReasonAttribute()
    {
        if ($this->appeal_status != 'NO_APPEAL') {
            $appeal =  RateAppeal::select('reject_reason')->where('rate_id', $this->id)->first();
            return isset($appeal['reject_reason']) ? $appeal['reject_reason'] : null;
        }
        return null;
    }

    /**
     * 追加项目名称
     * @Author swl
     * @return string
     */
    public function getGmNameAttribute()
    {
        $shop_info = GmPlatform::where('gm_id', '=', $this->gm_id)->select('platform_name')->first();

        return isset($shop_info['platform_name']) ? $shop_info['platform_name'] : '';
    }
}
