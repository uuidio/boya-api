<?php
/**
 * @Filename        Live.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */
namespace ShopEM\Services\Live;
use ShopEM\Models\LiveLog;
use ShopEM\Models\Coupon;
use ShopEM\Services\Marketing\Coupon as CouponService;
use ShopEM\Models\UserAccount;
use ShopEM\Jobs\InvalidateCoupon;
use ShopEM\Services\Live\LiveService;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Lives;
use ShopEM\Models\LiveRebroadcast;
use ShopEM\Models\SecKillApplie;
use Carbon\Carbon;

class LiveAnchorService
{

    /**
     * 直播记录
     *
     * @Author linzhe
     * @param $data
     * @return
     */
    public static function liveBegin($data)
    {

    }


    /**
     * 获取推流地址
     * 如果不传key和过期时间，将返回不含防盗链的url
     * @param domain 您用来推流的域名
     *        streamName 您用来区别不同推流地址的唯一流名称
     *        key 安全密钥
     *        time 过期时间 sample 2016-11-12 12:00:00
     * @return String url
     */
    public static function getPushUrl($data)
    {
        self::checkBegin($data);

        $domain = '86756.livepush.myqcloud.com';
        $key = '629cc6d13da192f2e01036d3d4bac7ae';
        $streamName = $data['streamname'];
        $time = date('Y-m-d H:i:s',strtotime("+2 day"));
        if($key && $time){
            $txTime = strtoupper(base_convert(strtotime($time),10,16));
            //txSecret = MD5( KEY + streamName + txTime )
            $txSecret = md5($key.$streamName.$txTime);
            $ext_str = "?".http_build_query(array(
                    "txSecret"=> $txSecret,
                    "txTime"=> $txTime
                ));
        }

        $url = "rtmp://".$domain."/live/".$streamName . (isset($ext_str) ? $ext_str : "");
        return $url;
    }


    /**
     * 验证开播数据
     *
     * @Author linzhe
     * @param $data
     * @return
     */
    public static function checkBegin($data)
    {
        if(!$data['title'])
        {
            throw new \LogicException('请设置直播间标题！');
        }

        if(!$data['img_url'])
        {
            throw new \LogicException('请设置直播间封面图!');
        }

        if(!$data['username'] || !$data['user_img']) {
            throw new \LogicException('请先设置头像和昵称!');
        }

        return true;
    }

    /**
     * 直播间分享送券（websock通知）
     *
     * @Author linzhe
     * @param $data
     * @return
     */
    public function shareCoupon($parameter)
    {
        $couponService = new CouponService();
        $shareCoupon = $couponService->shareCoupon($parameter);

        $shareUser = userAccount::where('accid',$parameter['accid'])->select('id')->first();
        if(empty($shareUser)) {
            return false;
        }
        $coupon = Coupon::where('id', $shareCoupon['id'])->where('is_hand_push', 0)->first();

        $userCoupon = \ShopEM\Models\CouponStockOnline::where(['coupon_id'=>$coupon['id'],'user_id'=>$shareUser['id']])->get();

        if (count($userCoupon) >= $coupon['user_num']) {
            return false;
        }

        if ($coupon->issue_num > 0) {
            $coupon->rec_num = $coupon->rec_num + 1;
            $coupon->save();
        }
        $data['coupon_code'] = $this->getCode($shareUser->id);
        $data['coupon_fee'] = $coupon->denominations;
        $data['user_id'] = $shareUser->id;
        $data['coupon_id'] = $coupon['id'];
        $res = \ShopEM\Models\CouponStockOnline::create($data);
        InvalidateCoupon::dispatch($res->id)->delay(now()->parse($coupon->end_at));
        if($res) {
            $LiveService = new LiveService();
            $swoole['type'] = 'share';
            $swoole['live_id'] = $parameter['live_id'];
            $swoole['user_id'] = $shareUser->id;
            $swoole['coupon_name'] = $coupon['name'];
            $swoole['denominations'] = $coupon['denominations'];
            $swoole['origin_condition'] = $coupon['origin_condition'];
            $LiveService->swooleSend($swoole);
        }
        return true;
    }

    /**
     * [getCode 获取优惠券唯一码]
     * @Author mssjxzw
     * @param  [type]  $user_id [description]
     * @return [type]           [description]
     */
    private function getCode($user_id)
    {
        $u = 'U'.$user_id;
        $length = strlen($u);
        $limit = 5;
        if ($length < $limit) {
            $u .= getRandStr($limit-$length);
            $length = $limit;
        }
        $res[] = getRandStr($length);
        $res[] = $u;
        $res[] = getRandStr($length-4).date('is');
        $res[] = getRandStr($length);
        return implode('-',$res);
    }

    public function rebroadcast($id)
    {
        DB::beginTransaction();
        try {
            $title = Lives::where('id',$id)->select('title')->first();
            $live = Lives::where('status','1')->where('id','<>',$id)->select('id','shop_id')->get();
            foreach($live as $key => $value) {
                $data = [
                    'live_id' => $value['id'],
                    'shop_id' => $value['shop_id'],
                    'rebroadcasts_live' => $id,
                    'rebroadcasts' => '1',
                    'title' => $title['title'],
                ];
                $flag = LiveRebroadcast::create($data);
            }
            Lives::where('id',$id)->update(['rebroadcast'=>1]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }

        return true;
    }

    /**
     * 直播间秒杀活动检查
     *
     * @Author linzhe
     * @param $data
     * @return
     */
    public function secKillCheck($shop_id)
    {
        $sec = SecKillApplie::where('shop_id',$shop_id)->where('start_time' ,'<',  Carbon::now()->toDateTimeString())->where('end_time' ,'>',  Carbon::now()->toDateTimeString())->count();
        return $sec ? true : false;
    }
}
