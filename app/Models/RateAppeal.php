<?php
/**
 * @Filename        RateAppeal.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class RateAppeal extends Model
{
    protected $table = 'rate_appeal';
    protected $guarded = [];

    protected $appends = ['status_text', 'appeal_type_text', 'goods_name', 'tid'];

    public static $statusMap = [
        'WAIT' => '等待批准',
        'REJECT' => '申诉驳回',
        'SUCCESS' => '申诉成功',
        'CLOSE' => '申诉关闭',
    ];

    public static $appealTypeMap = [
        'APPLY_DELETE' => '申请删除评论',
        'APPLY_UPDATE' => '申请修改评论',
    ];

    public function getStatusTextAttribute()
    {
        return self::$statusMap[$this->status];
    }

    public function getAppealTypeTextAttribute()
    {
        return self::$appealTypeMap[$this->appeal_type];
    }

    public function getEvidencePicAttribute($evidencePic)
    {
        return $evidencePic ? explode(',',$evidencePic) : $evidencePic;
    }

    public function getAppealLogAttribute($appealLog)
    {
        $appealLog = $appealLog ? json_decode($appealLog,true) : $appealLog;
        if( isset($appealLog['evidence_pic']) )
        {
            $appealLog['evidence_pic'] = explode(',', $appealLog['evidence_pic']);
        }
        return $appealLog;
    }

    public function getGoodsNameAttribute()
    {
        $rate =  RateTraderate::select('goods_name')->where('id', $this->rate_id)->first();
        return isset($rate['goods_name']) ? $rate['goods_name'] : null;
    }

    public function getTidAttribute()
    {
        $rate =  RateTraderate::select('tid')->where('id', $this->rate_id)->first();
        return isset($rate['tid']) ? $rate['tid'] : null;
    }
}
